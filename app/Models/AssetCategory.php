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
}
