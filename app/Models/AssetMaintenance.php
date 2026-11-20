<?php
/**
 * Asset Maintenance Model
 * Maintenance tickets, breakdown repairs, preventive servicing & AMC
 */

require_once BASE_PATH . '/app/Helpers/Database.php';

class AssetMaintenance {

    public static function generateLogNumber(): string {
        $db = Database::getInstance();
        $prefix = 'MNT-' . date('Ym') . '-';
        $stmt = $db->prepare("SELECT log_number FROM asset_maintenance_logs WHERE log_number LIKE ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$prefix . '%']);
        $last = $stmt->fetchColumn();

        if ($last && preg_match('/MNT-\d{6}-(\d+)/', $last, $m)) {
            $next = (int)$m[1] + 1;
        } else {
            $next = 1;
        }

        return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }

    public static function getAll(array $filters = []): array {
        $db = Database::getInstance();
        $sql = "SELECT m.*, a.asset_code, a.name AS asset_name, a.brand AS asset_brand,
                       c.name AS category_name, u.full_name AS creator_name
                FROM asset_maintenance_logs m
                JOIN assets a ON a.id = m.asset_id
                LEFT JOIN asset_categories c ON c.id = a.category_id
                LEFT JOIN users u ON u.id = m.created_by
                WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $sql .= " AND (m.log_number LIKE ? OR a.asset_code LIKE ? OR a.name LIKE ? OR m.technician_name LIKE ? OR m.serviced_by_vendor LIKE ?)";
            $params = array_merge($params, [$term, $term, $term, $term, $term]);
        }

        if (!empty($filters['status'])) {
            $sql .= " AND m.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['service_type'])) {
            $sql .= " AND m.service_type = ?";
            $params[] = $filters['service_type'];
        }

        if (!empty($filters['asset_id'])) {
            $sql .= " AND m.asset_id = ?";
            $params[] = (int)$filters['asset_id'];
        }

        $sql .= " ORDER BY m.service_date DESC, m.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT m.*, a.asset_code, a.name AS asset_name, a.brand AS asset_brand,
                                     a.model_number AS asset_model, a.serial_number AS asset_serial,
                                     a.barcode_qr AS asset_qr,
                                     c.name AS category_name, u.full_name AS creator_name
                              FROM asset_maintenance_logs m
                              JOIN assets a ON a.id = m.asset_id
                              LEFT JOIN asset_categories c ON c.id = a.category_id
                              LEFT JOIN users u ON u.id = m.created_by
                              WHERE m.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create(array $data): int {
        $db = Database::getInstance();
        $sql = "INSERT INTO asset_maintenance_logs (
            log_number, asset_id, service_type, issue_description, action_taken,
            serviced_by_vendor, technician_name, technician_contact, service_cost,
            parts_replaced, service_date, next_due_date, downtime_hours, status,
            created_by, created_at
        ) VALUES (
            :log_number, :asset_id, :service_type, :issue_description, :action_taken,
            :serviced_by_vendor, :technician_name, :technician_contact, :service_cost,
            :parts_replaced, :service_date, :next_due_date, :downtime_hours, :status,
            :created_by, NOW()
        )";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':log_number'          => $data['log_number'],
            ':asset_id'            => $data['asset_id'],
            ':service_type'        => $data['service_type'] ?? 'ROUTINE_PREVENTIVE',
            ':issue_description'   => $data['issue_description'] ?? null,
            ':action_taken'        => $data['action_taken'] ?? null,
            ':serviced_by_vendor'  => $data['serviced_by_vendor'] ?? null,
            ':technician_name'     => $data['technician_name'] ?? null,
            ':technician_contact'  => $data['technician_contact'] ?? null,
            ':service_cost'        => (float)($data['service_cost'] ?? 0),
            ':parts_replaced'      => $data['parts_replaced'] ?? null,
            ':service_date'        => !empty($data['service_date']) ? $data['service_date'] : date('Y-m-d'),
            ':next_due_date'       => !empty($data['next_due_date']) ? $data['next_due_date'] : null,
            ':downtime_hours'      => (float)($data['downtime_hours'] ?? 0),
            ':status'              => $data['status'] ?? 'COMPLETED',
            ':created_by'          => $data['created_by'] ?? null,
        ]);

        $logId = (int)$db->lastInsertId();

        // Update the asset status based on maintenance ticket
        if (($data['status'] ?? '') === 'IN_PROGRESS' || ($data['status'] ?? '') === 'PENDING') {
            $db->prepare("UPDATE assets SET status = 'IN_MAINTENANCE' WHERE id = ?")->execute([$data['asset_id']]);
        } elseif (($data['status'] ?? '') === 'COMPLETED') {
            $db->prepare("UPDATE assets SET status = 'IN_USE' WHERE id = ? AND status = 'IN_MAINTENANCE'")->execute([$data['asset_id']]);
        }

        return $logId;
    }
}
