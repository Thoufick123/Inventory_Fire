<?php
/**
 * Asset Model
 * Fixed Assets, Plant Machinery, Tools and IT Equipment
 */

require_once BASE_PATH . '/app/Helpers/Database.php';

class Asset {

    public static function generateAssetCode(): string {
        $db = Database::getInstance();
        $prefix = 'AST-' . date('Y') . '-';
        $stmt = $db->prepare("SELECT asset_code FROM assets WHERE asset_code LIKE ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$prefix . '%']);
        $lastCode = $stmt->fetchColumn();

        if ($lastCode && preg_match('/AST-\d{4}-(\d+)/', $lastCode, $m)) {
            $nextNum = (int)$m[1] + 1;
        } else {
            $nextNum = 1;
        }

        return $prefix . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
    }

    public static function getMetrics(): array {
        $db = Database::getInstance();
        $total = (int)$db->query("SELECT COUNT(*) FROM assets")->fetchColumn();
        $inUse = (int)$db->query("SELECT COUNT(*) FROM assets WHERE status = 'IN_USE'")->fetchColumn();
        $maintenance = (int)$db->query("SELECT COUNT(*) FROM assets WHERE status = 'IN_MAINTENANCE'")->fetchColumn();
        $totalBookVal = (float)$db->query("SELECT COALESCE(SUM(current_book_value), 0) FROM assets WHERE status NOT IN ('DISPOSED','WRITTEN_OFF')")->fetchColumn();

        return [
            'total'          => $total,
            'in_use'         => $inUse,
            'in_maintenance' => $maintenance,
            'total_book_val' => $totalBookVal,
        ];
    }

    public static function getAll(array $filters = []): array {
        $db = Database::getInstance();
        $sql = "SELECT a.*, c.name AS category_name, c.icon AS category_icon, c.code AS category_code
                FROM assets a
                LEFT JOIN asset_categories c ON c.id = a.category_id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $sql .= " AND (a.asset_code LIKE ? OR a.name LIKE ? OR a.brand LIKE ? OR a.serial_number LIKE ? OR a.barcode_qr LIKE ?)";
            $params = array_merge($params, [$term, $term, $term, $term, $term]);
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND a.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND a.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['condition_state'])) {
            $sql .= " AND a.condition_state = ?";
            $params[] = $filters['condition_state'];
        }

        $sql .= " ORDER BY a.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT a.*, c.name AS category_name, c.icon AS category_icon, c.code AS category_code,
                              u.full_name AS creator_name
                              FROM assets a
                              LEFT JOIN asset_categories c ON c.id = a.category_id
                              LEFT JOIN users u ON u.id = a.created_by
                              WHERE a.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByQr(string $qr): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT a.*, c.name AS category_name FROM assets a LEFT JOIN asset_categories c ON c.id = a.category_id WHERE a.barcode_qr = ? OR a.asset_code = ?");
        $stmt->execute([$qr, $qr]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create(array $data): int {
        $db = Database::getInstance();
        $sql = "INSERT INTO assets (
            asset_code, name, category_id, location_type, location_id, floor_area, brand,
            model_number, serial_number, barcode_qr, specifications, purchase_date, purchase_cost,
            vendor_name, vendor_contact, invoice_number, warranty_expiry, useful_life_years,
            salvage_value, current_book_value, status, condition_state, custodian_name,
            notes, created_by, created_at
        ) VALUES (
            :asset_code, :name, :category_id, :location_type, :location_id, :floor_area, :brand,
            :model_number, :serial_number, :barcode_qr, :specifications, :purchase_date, :purchase_cost,
            :vendor_name, :vendor_contact, :invoice_number, :warranty_expiry, :useful_life_years,
            :salvage_value, :current_book_value, :status, :condition_state, :custodian_name,
            :notes, :created_by, NOW()
        )";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':asset_code'         => $data['asset_code'],
            ':name'               => $data['name'],
            ':category_id'        => $data['category_id'],
            ':location_type'      => $data['location_type'] ?? 'PRODUCTION',
            ':location_id'        => $data['location_id'] ?? 1,
            ':floor_area'         => $data['floor_area'] ?? null,
            ':brand'              => $data['brand'] ?? null,
            ':model_number'       => $data['model_number'] ?? null,
            ':serial_number'      => $data['serial_number'] ?? null,
            ':barcode_qr'         => $data['barcode_qr'],
            ':specifications'     => $data['specifications'] ?? null,
            ':purchase_date'      => !empty($data['purchase_date']) ? $data['purchase_date'] : null,
            ':purchase_cost'      => (float)($data['purchase_cost'] ?? 0),
            ':vendor_name'        => $data['vendor_name'] ?? null,
            ':vendor_contact'     => $data['vendor_contact'] ?? null,
            ':invoice_number'     => $data['invoice_number'] ?? null,
            ':warranty_expiry'    => !empty($data['warranty_expiry']) ? $data['warranty_expiry'] : null,
            ':useful_life_years'  => (int)($data['useful_life_years'] ?? 5),
            ':salvage_value'      => (float)($data['salvage_value'] ?? 0),
            ':current_book_value' => (float)($data['current_book_value'] ?? ($data['purchase_cost'] ?? 0)),
            ':status'             => $data['status'] ?? 'IN_USE',
            ':condition_state'    => $data['condition_state'] ?? 'GOOD',
            ':custodian_name'     => $data['custodian_name'] ?? null,
            ':notes'              => $data['notes'] ?? null,
            ':created_by'         => $data['created_by'] ?? null,
        ]);

        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $db = Database::getInstance();
        $fields = [];
        $params = [];

        $allowed = [
            'name', 'category_id', 'location_type', 'location_id', 'floor_area', 'brand',
            'model_number', 'serial_number', 'specifications', 'purchase_date', 'purchase_cost',
            'vendor_name', 'vendor_contact', 'invoice_number', 'warranty_expiry', 'useful_life_years',
            'salvage_value', 'current_book_value', 'status', 'condition_state', 'custodian_name',
            'notes'
        ];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "`$col` = ?";
                $params[] = $data[$col];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "`updated_at` = NOW()";
        $params[] = $id;

        $sql = "UPDATE assets SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public static function getMaintenanceHistory(int $assetId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM asset_maintenance_logs WHERE asset_id = ? ORDER BY service_date DESC, id DESC");
        $stmt->execute([$assetId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
