<?php
/**
 * QC Batch Model
 * Handles Quality Control inspection, defect classification, rework and approval.
 */

class QCBatch {

    /**
     * Find QC batch by ID.
     */
    public static function find(int $id): ?array {
        return Database::fetchOne(
            "SELECT qb.*, 
                    pp.program_number, pp.target_date, pp.name as program_name,
                    pp.category_id as program_category_id, pp.subcategory_id as program_subcategory_id, pp.sub_subcategory_id as program_sub_subcategory_id,
                    p.id as product_id, p.name as product_name, p.code as product_code,
                    COALESCE(p.qr_code_prefix, CONCAT(COALESCE(p.category_code,''), COALESCE(p.subcategory_code,''), COALESCE(p.sub_subcategory_code,''))) as qr_code_prefix,
                    p.category_code, p.subcategory_code, p.sub_subcategory_code,
                    COALESCE(c.name, c_pp.name) as category_name,
                    COALESCE(sc.name, sc_pp.name) as subcategory_name,
                    COALESCE(ssc.name, ssc_pp.name) as sub_subcategory_name,
                    sb.id as stitching_batch_id, sb.batch_number as stitching_batch_number,
                    qg.id as qr_generation_id, qg.batch_code as qr_batch_code,
                    u.full_name as inspector_name
             FROM qc_batches qb
             LEFT JOIN production_programs pp ON qb.program_id = pp.id
             LEFT JOIN qr_generations qg ON qb.qr_generation_id = qg.id
             LEFT JOIN products p ON COALESCE(pp.product_id, qg.product_id) = p.id
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN categories c_pp ON pp.category_id = c_pp.id
             LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
             LEFT JOIN subcategories sc_pp ON pp.subcategory_id = sc_pp.id
             LEFT JOIN sub_subcategories ssc ON p.sub_subcategory_id = ssc.id
             LEFT JOIN sub_subcategories ssc_pp ON pp.sub_subcategory_id = ssc_pp.id
             LEFT JOIN stitching_batches sb ON qb.stitching_batch_id = sb.id
             LEFT JOIN users u ON qb.inspector_id = u.id
             WHERE qb.id = ?",
            [$id]
        );
    }

    /**
     * Get all QC batches with filters.
     */
    public static function getAll(array $filters = []): array {
        $sql = "SELECT qb.*, 
                       pp.program_number, pp.name as program_name,
                       p.name as product_name, p.code as product_code,
                       qg.batch_code as qr_batch_code,
                       u.full_name as inspector_name,
                       COALESCE((
                           SELECT SUM(pbi.quantity)
                           FROM production_batch_items pbi
                           JOIN production_batches pb ON pbi.production_batch_id = pb.id
                           WHERE pb.qc_batch_id = qb.id AND pb.status != 'CANCELLED'
                       ), 0) as already_packed_qty,
                       (SELECT pb.id FROM production_batches pb WHERE pb.qc_batch_id = qb.id AND pb.status != 'CANCELLED' ORDER BY pb.id DESC LIMIT 1) as packed_batch_id,
                       (SELECT pb.batch_number FROM production_batches pb WHERE pb.qc_batch_id = qb.id AND pb.status != 'CANCELLED' ORDER BY pb.id DESC LIMIT 1) as packed_batch_number,
                       (SELECT pb.status FROM production_batches pb WHERE pb.qc_batch_id = qb.id AND pb.status != 'CANCELLED' ORDER BY pb.id DESC LIMIT 1) as packed_batch_status
                FROM qc_batches qb
                LEFT JOIN production_programs pp ON qb.program_id = pp.id
                LEFT JOIN qr_generations qg ON qb.qr_generation_id = qg.id
                LEFT JOIN products p ON COALESCE(pp.product_id, qg.product_id) = p.id
                LEFT JOIN users u ON qb.inspector_id = u.id
                WHERE 1=1";
        $params = [];

        $cid = !empty($filters['concern_id']) ? (int)$filters['concern_id'] : (function_exists('currentConcernId') ? currentConcernId() : null);
        if ($cid) {
            if ($cid === 1) {
                $sql .= " AND (qb.concern_id = ? OR qb.concern_id IS NULL)";
            } else {
                $sql .= " AND qb.concern_id = ?";
            }
            $params[] = $cid;
        }

        if (!empty($filters['status'])) {
            $st = strtoupper(trim($filters['status']));
            if ($st === 'PENDING') {
                $sql .= " AND (qb.status = 'PENDING' OR (qb.passed_qty = 0 AND qb.rejected_qty = 0 AND qb.rework_qty = 0))";
            } else {
                $sql .= " AND qb.status = ?";
                $params[] = $st;
            }
        }

        if (!empty($filters['program_id'])) {
            $sql .= " AND qb.program_id = ?";
            $params[] = $filters['program_id'];
        }

        if (!empty($filters['production_unit_id'])) {
            $sql .= " AND pp.production_unit_id = ?";
            $params[] = (int)$filters['production_unit_id'];
        }

        if (!empty($filters['search'])) {
            $rawSearch = trim($filters['search']);
            $cleanNum = preg_replace('/^(qc\s*#?|batch\s*#?)/i', '', $rawSearch);
            $s = '%' . $rawSearch . '%';
            $sn = '%' . trim($cleanNum) . '%';
            $sql .= " AND (CAST(qb.id AS CHAR) LIKE ? OR CAST(qb.id AS CHAR) LIKE ? OR pp.program_number LIKE ? OR pp.name LIKE ? OR p.name LIKE ? OR p.code LIKE ? OR u.full_name LIKE ? OR qg.batch_code LIKE ?)";
            $params = array_merge($params, [$s, $sn, $s, $s, $s, $s, $s, $s]);
        }

        $sql .= " ORDER BY qb.created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        $rows = Database::fetchAll($sql, $params);
        foreach ($rows as &$r) {
            $passed = (int)($r['passed_qty'] ?? 0);
            $packed = (int)($r['already_packed_qty'] ?? 0);
            $r['remaining_qty'] = max(0, $passed - $packed);
        }
        unset($r);

        return $rows;
    }

    /**
     * Create QC batch.
     */
    public static function create(array $data): int {
        if (empty($data['batch_number'])) {
            if (class_exists('ProductionService')) {
                $data['batch_number'] = ProductionService::generateSequenceNumber('qc_batches', 'batch_number', 'QC');
            } else {
                $data['batch_number'] = 'QC-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));
            }
        }
        $concernId = !empty($data['concern_id']) ? (int)$data['concern_id'] : (function_exists('currentConcernId') ? currentConcernId() : null);

        Database::execute(
            "INSERT INTO qc_batches 
             (concern_id, batch_number, program_id, stitching_batch_id, qr_generation_id, inspector_id, total_qty, passed_qty, rejected_qty, rework_qty, status, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $concernId,
                $data['batch_number'],
                $data['program_id'],
                $data['stitching_batch_id'] ?? null,
                $data['qr_generation_id'] ?? null,
                $data['inspector_id'] ?? null,
                $data['total_qty'] ?? 0,
                $data['passed_qty'] ?? 0,
                $data['rejected_qty'] ?? 0,
                $data['rework_qty'] ?? 0,
                $data['status'] ?? 'PENDING',
                $data['notes'] ?? null,
            ]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Update QC batch.
     */
    public static function update(int $id, array $data): bool {
        $fields = [];
        $params = [];

        $allowed = ['inspector_id', 'total_qty', 'passed_qty', 'rejected_qty', 'rework_qty', 'status', 'notes', 'pieces_data'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`$field` = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE qc_batches SET " . implode(', ', $fields) . " WHERE id = ?";
        return Database::execute($sql, $params) >= 0;
    }

    /**
     * Recalculate totals from QC items.
     */
    public static function recalculateTotals(int $id): array {
        $totals = Database::fetchOne(
            "SELECT COALESCE(SUM(inspected_qty), 0) as total_inspected,
                    COALESCE(SUM(passed_qty), 0) as total_passed,
                    COALESCE(SUM(rejected_qty), 0) as total_rejected,
                    COALESCE(SUM(rework_qty), 0) as total_rework
             FROM qc_items
             WHERE qc_batch_id = ?",
            [$id]
        );

        $passed = (int)($totals['total_passed'] ?? 0);
        $rejected = (int)($totals['total_rejected'] ?? 0);
        $rework = (int)($totals['total_rework'] ?? 0);
        $inspected = (int)($totals['total_inspected'] ?? 0);

        $status = 'PENDING';
        if ($inspected > 0) {
            if ($passed === $inspected) {
                $status = 'PASSED';
            } elseif ($rejected === $inspected) {
                $status = 'REJECTED';
            } elseif ($rework > 0 && ($passed + $rejected + $rework >= $inspected)) {
                $status = 'REWORK';
            } elseif ($passed > 0) {
                $status = 'PARTIAL';
            }
        }

        Database::execute(
            "UPDATE qc_batches 
             SET total_qty = ?, passed_qty = ?, rejected_qty = ?, rework_qty = ?, status = ?
             WHERE id = ?",
            [
                $inspected,
                $passed,
                $rejected,
                $rework,
                $status,
                $id
            ]
        );

        return array_merge($totals, ['status' => $status]);
    }

    /**
     * Get variant items for this QC batch.
     */
    public static function getItems(int $batchId): array {
        $items = Database::fetchAll(
            "SELECT qi.*, 
                    pv.qr_code as pv_qr,
                    pv.variant_code, pv.id as pv_id,
                    COALESCE(pv.variant_code, IF(qi.variant_id > 0, CONCAT('V', LPAD(qi.variant_id, 4, '0')), NULL)) as display_variant_code,
                    COALESCE(qi.size_name, s.name, s.code, 'Standard') as display_size,
                    COALESCE(qi.color_name, c.name, 'Assorted') as display_color,
                    cat.name as category_name, cat.code as category_code,
                    subcat.name as subcategory_name, subcat.code as subcategory_code,
                    subsubcat.name as sub_subcategory_name, subsubcat.code as sub_subcategory_code
             FROM qc_items qi
             JOIN qc_batches qb ON qi.qc_batch_id = qb.id
             LEFT JOIN product_variants pv ON qi.variant_id = pv.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             LEFT JOIN categories cat ON qi.category_id = cat.id
             LEFT JOIN subcategories subcat ON qi.subcategory_id = subcat.id
             LEFT JOIN sub_subcategories subsubcat ON qi.sub_subcategory_id = subsubcat.id
             WHERE qi.qc_batch_id = ?
             ORDER BY qi.id ASC",
            [$batchId]
        );

        // Self-heal: ensure variant_id and display_variant_code are populated for all items
        foreach ($items as &$item) {
            if (empty($item['display_variant_code'])) {
                $matchedVar = null;
                if ($matchedVar) {
                    $item['variant_id'] = (int)$matchedVar['id'];
                    $item['variant_code'] = $matchedVar['variant_code'];
                    $item['display_variant_code'] = $matchedVar['variant_code'] ?: ('V' . str_pad((string)$matchedVar['id'], 4, '0', STR_PAD_LEFT));
                    try {
                        Database::execute("UPDATE qc_items SET variant_id = ? WHERE id = ?", [(int)$matchedVar['id'], (int)$item['id']]);
                    } catch (\Throwable $e) {}
                } elseif (!empty($item['variant_id'])) {
                    $item['display_variant_code'] = 'V' . str_pad((string)$item['variant_id'], 4, '0', STR_PAD_LEFT);
                }
            }
        }
        unset($item);

        return $items;
    }

    /**
     * Delete QC batch and its items.
     */
    public static function delete(int $id): bool {
        Database::execute("DELETE FROM qc_items WHERE qc_batch_id = ?", [$id]);
        return Database::execute("DELETE FROM qc_batches WHERE id = ?", [$id]) > 0;
    }
}
