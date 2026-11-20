<?php
/**
 * Stock Adjustment Model
 * Adjusts inventory counts based on physical audit, damage, found stock, etc.
 */

require_once BASE_PATH . '/app/Helpers/Database.php';

class StockAdjustment {

    public static function generateAdjustmentNumber(): string {
        $db = Database::getInstance();
        $prefix = 'ADJ-' . date('Ym') . '-';
        $stmt = $db->prepare("SELECT adjustment_number FROM stock_adjustments WHERE adjustment_number LIKE ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$prefix . '%']);
        $last = $stmt->fetchColumn();

        if ($last && preg_match('/ADJ-\d{6}-(\d+)/', $last, $m)) {
            $next = (int)$m[1] + 1;
        } else {
            $next = 1;
        }

        return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }

    public static function getAll(array $filters = []): array {
        $db = Database::getInstance();
        $sql = "SELECT sa.*, 
                       pv.sku AS variant_sku, pv.variant_code, pv.qr_code AS variant_qr,
                       p.name AS product_name, p.code AS parent_sku,
                       c.name AS color_name, d.name AS design_name, s.name AS size_name,
                       cat.name AS category_name,
                       w.name AS warehouse_name,
                       u.full_name AS requested_by_name
                FROM stock_adjustments sa
                JOIN product_variants pv ON pv.id = sa.variant_id
                JOIN products p ON p.id = pv.product_id
                LEFT JOIN categories cat ON cat.id = p.category_id
                LEFT JOIN colors c ON c.id = pv.color_id
                LEFT JOIN designs d ON d.id = pv.design_id
                LEFT JOIN sizes s ON s.id = pv.size_id
                LEFT JOIN warehouses w ON w.id = sa.warehouse_id
                LEFT JOIN users u ON u.id = sa.requested_by
                WHERE 1=1";
        $params = [];

        $cid = !empty($filters['concern_id']) ? (int)$filters['concern_id'] : (function_exists('currentConcernId') ? currentConcernId() : null);
        if ($cid) {
            if ($cid === 1) {
                $sql .= " AND (sa.concern_id = ? OR sa.concern_id IS NULL)";
            } else {
                $sql .= " AND sa.concern_id = ?";
            }
            $params[] = $cid;
        }

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $sql .= " AND (sa.adjustment_number LIKE ? OR pv.sku LIKE ? OR pv.variant_code LIKE ? OR p.name LIKE ? OR p.code LIKE ?)";
            $params = array_merge($params, [$term, $term, $term, $term, $term]);
        }

        if (!empty($filters['adjustment_type'])) {
            $sql .= " AND sa.adjustment_type = ?";
            $params[] = $filters['adjustment_type'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND sa.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(sa.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(sa.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= " ORDER BY sa.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT sa.*, 
                                     pv.sku AS variant_sku, pv.variant_code, pv.qr_code AS variant_qr,
                                     p.name AS product_name, p.code AS parent_sku,
                                     c.name AS color_name, d.name AS design_name, s.name AS size_name,
                                     cat.name AS category_name,
                                     w.name AS warehouse_name,
                                     u.full_name AS requested_by_name,
                                     u2.full_name AS approved_by_name
                              FROM stock_adjustments sa
                              JOIN product_variants pv ON pv.id = sa.variant_id
                              JOIN products p ON p.id = pv.product_id
                              LEFT JOIN categories cat ON cat.id = p.category_id
                              LEFT JOIN colors c ON c.id = pv.color_id
                              LEFT JOIN designs d ON d.id = pv.design_id
                              LEFT JOIN sizes s ON s.id = pv.size_id
                              LEFT JOIN warehouses w ON w.id = sa.warehouse_id
                              LEFT JOIN users u ON u.id = sa.requested_by
                              LEFT JOIN users u2 ON u2.id = sa.approved_by
                              WHERE sa.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create(array $data): int {
        $db = Database::getInstance();
        $concernId = !empty($data['concern_id']) ? (int)$data['concern_id'] : (function_exists('currentConcernId') ? currentConcernId() : null);

        $sql = "INSERT INTO stock_adjustments (
            concern_id, adjustment_number, adj_number, warehouse_id, variant_id,
            adjustment_type, quantity, reason, requested_by, approved_by,
            status, notes, qr_codes, approved_at, created_at
        ) VALUES (
            :concern_id, :adjustment_number, :adj_number, :warehouse_id, :variant_id,
            :adjustment_type, :quantity, :reason, :requested_by, :approved_by,
            :status, :notes, :qr_codes, :approved_at, NOW()
        )";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':concern_id'        => $concernId,
            ':adjustment_number' => $data['adjustment_number'],
            ':adj_number'        => $data['adj_number'] ?? $data['adjustment_number'],
            ':warehouse_id'      => $data['warehouse_id'] ?? 1,
            ':variant_id'        => $data['variant_id'],
            ':adjustment_type'   => $data['adjustment_type'],
            ':quantity'          => $data['quantity'],
            ':reason'            => $data['reason'],
            ':requested_by'      => $data['requested_by'] ?? null,
            ':approved_by'       => $data['approved_by'] ?? null,
            ':status'            => $data['status'] ?? 'APPROVED',
            ':notes'             => $data['notes'] ?? null,
            ':qr_codes'          => !empty($data['qr_codes']) ? (is_array($data['qr_codes']) ? json_encode($data['qr_codes']) : $data['qr_codes']) : null,
            ':approved_at'       => ($data['status'] ?? 'APPROVED') === 'APPROVED' ? date('Y-m-d H:i:s') : null,
        ]);

        return (int)$db->lastInsertId();
    }
}
