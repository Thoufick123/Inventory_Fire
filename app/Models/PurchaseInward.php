<?php
/**
 * Purchase Inward / GRN Model
 * Records receipt of goods, inspection, acceptance, and stock inwarding
 */

require_once BASE_PATH . '/app/Helpers/Database.php';

class PurchaseInward {

    public static function generateGrnNumber(): string {
        $db = Database::getInstance();
        $prefix = 'GRN-' . date('Ym') . '-';
        $stmt = $db->prepare("SELECT grn_number FROM purchase_inwards WHERE grn_number LIKE ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$prefix . '%']);
        $last = $stmt->fetchColumn();

        if ($last && preg_match('/GRN-\d{6}-(\d+)/', $last, $m)) {
            $next = (int)$m[1] + 1;
        } else {
            $next = 1;
        }

        return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }

    public static function getAll(array $filters = []): array {
        $db = Database::getInstance();
        $sql = "SELECT pi.*, 
                       po.po_number,
                       w.name AS warehouse_name,
                       u.full_name AS received_by_name,
                       u2.full_name AS verified_by_name,
                       c_con.name AS concern_name, c_con.code AS concern_code
                FROM purchase_inwards pi
                LEFT JOIN concerns c_con ON c_con.id = COALESCE(pi.concern_id, 1)
                LEFT JOIN purchase_orders po ON po.id = pi.purchase_order_id
                LEFT JOIN warehouses w ON w.id = pi.warehouse_id
                LEFT JOIN users u ON u.id = pi.received_by
                LEFT JOIN users u2 ON u2.id = pi.verified_by
                WHERE 1=1";
        $params = [];

        $cid = !empty($filters['concern_id']) ? (int)$filters['concern_id'] : (function_exists('currentConcernId') ? currentConcernId() : null);
        if ($cid) {
            if ($cid === 1) {
                $sql .= " AND (pi.concern_id = ? OR pi.concern_id IS NULL OR w.concern_id = ?)";
                $params[] = $cid;
                $params[] = $cid;
            } else {
                $sql .= " AND (pi.concern_id = ? OR w.concern_id = ?)";
                $params[] = $cid;
                $params[] = $cid;
            }
        }

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $sql .= " AND (pi.grn_number LIKE ? OR pi.invoice_number LIKE ? OR po.po_number LIKE ? OR pi.vehicle_number LIKE ?)";
            $params = array_merge($params, [$term, $term, $term, $term]);
        }

        if (!empty($filters['status'])) {
            $sql .= " AND pi.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND pi.received_date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND pi.received_date <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= " ORDER BY pi.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT pi.*, 
                                     po.po_number, po.order_date AS po_order_date, po.total_amount AS po_total_amount,
                                     w.name AS warehouse_name,
                                     u.full_name AS received_by_name,
                                     u2.full_name AS verified_by_name
                              FROM purchase_inwards pi
                              LEFT JOIN purchase_orders po ON po.id = pi.purchase_order_id
                              LEFT JOIN warehouses w ON w.id = pi.warehouse_id
                              LEFT JOIN users u ON u.id = pi.received_by
                              LEFT JOIN users u2 ON u2.id = pi.verified_by
                              WHERE pi.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function getItems(int $inwardId): array {
        $db = Database::getInstance();
        $sql = "SELECT pii.*, 
                       pv.variant_code, pv.qr_code AS variant_qr,
                       p.name AS product_name,
                       c.name AS color_name, d.name AS design_name, s.name AS size_name
                FROM purchase_inward_items pii
                JOIN product_variants pv ON pv.id = pii.variant_id
                JOIN products p ON p.id = pv.product_id
                LEFT JOIN colors c ON c.id = pv.color_id
                LEFT JOIN designs d ON d.id = pv.design_id
                LEFT JOIN sizes s ON s.id = pv.size_id
                WHERE pii.purchase_inward_id = ?
                ORDER BY pii.id ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$inwardId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(array $inwardData, array $items): int {
        $db = Database::getInstance();

        $concernId = !empty($inwardData['concern_id']) ? (int)$inwardData['concern_id'] : (function_exists('currentConcernId') ? currentConcernId() : null);

        $sql = "INSERT INTO purchase_inwards (
            concern_id, purchase_order_id, grn_number, warehouse_id, received_date,
            received_by, verified_by, notes, invoice_number, invoice_date,
            status, total_received_qty, total_accepted_qty, total_rejected_qty,
            vehicle_number, driver_name, driver_contact, gate_pass_number,
            created_at
        ) VALUES (
            :concern_id, :purchase_order_id, :grn_number, :warehouse_id, :received_date,
            :received_by, :verified_by, :notes, :invoice_number, :invoice_date,
            :status, :total_received_qty, :total_accepted_qty, :total_rejected_qty,
            :vehicle_number, :driver_name, :driver_contact, :gate_pass_number,
            NOW()
        )";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':concern_id'          => $concernId,
            ':purchase_order_id'   => !empty($inwardData['purchase_order_id']) ? (int)$inwardData['purchase_order_id'] : null,
            ':grn_number'          => $inwardData['grn_number'],
            ':warehouse_id'        => $inwardData['warehouse_id'] ?? 1,
            ':received_date'       => $inwardData['received_date'] ?? date('Y-m-d'),
            ':received_by'         => $inwardData['received_by'] ?? null,
            ':verified_by'         => $inwardData['verified_by'] ?? null,
            ':notes'               => $inwardData['notes'] ?? null,
            ':invoice_number'      => $inwardData['invoice_number'] ?? null,
            ':invoice_date'        => !empty($inwardData['invoice_date']) ? $inwardData['invoice_date'] : null,
            ':status'              => $inwardData['status'] ?? 'STOCKED',
            ':total_received_qty'  => (int)($inwardData['total_received_qty'] ?? 0),
            ':total_accepted_qty'  => (int)($inwardData['total_accepted_qty'] ?? 0),
            ':total_rejected_qty'  => (int)($inwardData['total_rejected_qty'] ?? 0),
            ':vehicle_number'      => $inwardData['vehicle_number'] ?? null,
            ':driver_name'         => $inwardData['driver_name'] ?? null,
            ':driver_contact'      => $inwardData['driver_contact'] ?? null,
            ':gate_pass_number'    => $inwardData['gate_pass_number'] ?? null,
        ]);

        $inwardId = (int)$db->lastInsertId();

        $itemStmt = $db->prepare("INSERT INTO purchase_inward_items (
            purchase_inward_id, variant_id, ordered_qty, received_qty,
            damaged_qty, accepted_qty, received_quantity, accepted_quantity,
            rejected_quantity, rejection_reason, mrp, batch_lot_no, notes
        ) VALUES (
            :purchase_inward_id, :variant_id, :ordered_qty, :received_qty,
            :damaged_qty, :accepted_qty, :received_quantity, :accepted_quantity,
            :rejected_quantity, :rejection_reason, :mrp, :batch_lot_no, :notes
        )");

        foreach ($items as $item) {
            $orderedQty  = (int)($item['ordered_qty'] ?? 0);
            $receivedQty = (int)($item['received_qty'] ?? 0);
            $damagedQty  = (int)($item['damaged_qty'] ?? 0);
            $acceptedQty = (int)($item['accepted_qty'] ?? ($receivedQty - $damagedQty));

            $itemStmt->execute([
                ':purchase_inward_id' => $inwardId,
                ':variant_id'         => $item['variant_id'],
                ':ordered_qty'        => $orderedQty,
                ':received_qty'       => $receivedQty,
                ':damaged_qty'        => $damagedQty,
                ':accepted_qty'       => $acceptedQty,
                ':received_quantity'  => $receivedQty,
                ':accepted_quantity'  => $acceptedQty,
                ':rejected_quantity'  => $damagedQty,
                ':rejection_reason'   => $item['rejection_reason'] ?? null,
                ':mrp'                => (float)($item['mrp'] ?? 0),
                ':batch_lot_no'       => $item['batch_lot_no'] ?? null,
                ':notes'              => $item['notes'] ?? null,
            ]);
        }

        return $inwardId;
    }
}
