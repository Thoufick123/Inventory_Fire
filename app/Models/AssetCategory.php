<?php
/**
 * Asset Category Model
 */

require_once BASE_PATH . '/app/Helpers/Database.php';

class AssetCategory {
    public static function getAll(): array {
        $db = Database::getInstance();
        self::ensureDefaults();
        return $db->query("SELECT * FROM asset_categories WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM asset_categories WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function ensureDefaults(): void {
        $db = Database::getInstance();
        $count = (int)$db->query("SELECT COUNT(*) FROM asset_categories")->fetchColumn();
        if ($count > 0) {
            return;
        }

        $defaults = [
            ['Cutting Machinery', 'CUT_MACH', 'bi-scissors', 'Fabric spreaders, straight knife, and band knife cutting machines', 15.00],
            ['Stitching & Sewing Machines', 'STITCH_SEW', 'bi-gear', 'Single needle lockstitch, overlock, flatlock, button hole machines', 15.00],
            ['Finishing & Steam Press', 'FINISH_PRESS', 'bi-fire', 'Steam ironing vacuum tables, boilers, stain removing guns', 15.00],
            ['Packaging & Barcode Equipment', 'PKG_BC', 'bi-upc-scan', 'Thermal label barcode printers, scanners, carton sealers', 20.00],
            ['Generators & Power Systems', 'GEN_PWR', 'bi-lightning-charge', 'Diesel generator sets, heavy-duty UPS units, servo stabilizers', 10.00],
            ['IT & Office Hardware', 'IT_OFFICE', 'bi-pc-display', 'Billing desktop PCs, network switches, CCTV DVRs, biometric attendance', 25.00],
        ];

        $stmt = $db->prepare("INSERT INTO asset_categories (name, code, icon, description, depreciation_rate_annual, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
        foreach ($defaults as $cat) {
            $stmt->execute($cat);
        }
    }

    public static function createQuick(string $name, float $depreciationRate = 15.00): array {
        $db = Database::getInstance();
        $cleanName = trim($name);
        $stmt = $db->prepare("SELECT * FROM asset_categories WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) LIMIT 1");
        $stmt->execute([$cleanName]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            if (empty($existing['is_active'])) {
                $db->prepare("UPDATE asset_categories SET is_active = 1 WHERE id = ?")->execute([(int)$existing['id']]);
                $existing['is_active'] = 1;
            }
            return $existing;
        }

        $baseCode = strtoupper(preg_replace('/[^A-Z0-9]+/', '_', strtoupper($cleanName)));
        $baseCode = trim((string)$baseCode, '_');
        if ($baseCode === '') {
            $baseCode = 'CAT';
        }
        $code = mb_substr($baseCode, 0, 16);
        $chk = $db->prepare("SELECT id FROM asset_categories WHERE code = ? LIMIT 1");
        $chk->execute([$code]);
        if ($chk->fetch()) {
            $code = mb_substr($baseCode, 0, 12) . '_' . random_int(100, 999);
        }

        $ins = $db->prepare("INSERT INTO asset_categories (name, code, icon, description, depreciation_rate_annual, is_active, created_at) VALUES (?, ?, 'bi-tag', '', ?, 1, NOW())");
        $ins->execute([$cleanName, $code, $depreciationRate]);
        $newId = (int)$db->lastInsertId();
        return self::find($newId) ?: ['id' => $newId, 'name' => $cleanName, 'code' => $code];
    }
}
