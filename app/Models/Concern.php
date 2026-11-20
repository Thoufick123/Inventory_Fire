<?php
/**
 * Concern Model
 *
 * Owns the multi-concern master tables and the concern_id scoping columns used
 * across the application. The management UI is optional, but concern records
 * and scoping are used for access control, branding and module entitlements.
 */

class Concern {

    private static bool $schemaChecked = false;

    /**
     * Tables carrying a nullable concern_id for data scoping.
     * Legacy rows with NULL remain visible to every concern.
     */
    private const SCOPED_TABLES = [
        'cutting_batches',
        'production_batches',
        'production_bills',
        'production_expenses',
        'production_outwards',
        'production_programs',
        'production_purchases',
        'production_stock_requests',
        'purchase_inwards',
        'qc_batches',
        'qr_generations',
        'roll_damages',
        'sellers',
        'stitching_batches',
        'stock_adjustments',
        'stock_balances',
        'stock_transactions',
        'warehouses',
    ];

    /**
     * Ensure the concern master tables and scoping columns exist.
     * Idempotent and safe to call on every request.
     */
    public static function ensureSchema(): void {
        if (self::$schemaChecked) return;
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['_concern_schema_checked'])) {
            self::$schemaChecked = true;
            return;
        }
        self::$schemaChecked = true;

        try {
            Database::execute("CREATE TABLE IF NOT EXISTS `concerns` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(150) NOT NULL,
                `code` VARCHAR(50) NOT NULL UNIQUE,
                `concern_type` ENUM('HEAD_OFFICE','PRODUCTION','WAREHOUSE','RETAIL') NOT NULL DEFAULT 'HEAD_OFFICE',
                `legal_name` VARCHAR(255) NULL,
                `gst_number` VARCHAR(15) NULL,
                `pan_number` VARCHAR(10) NULL,
                `address` TEXT NULL,
                `city` VARCHAR(100) NULL,
                `state` VARCHAR(100) NULL,
                `pincode` VARCHAR(10) NULL,
                `phone` VARCHAR(20) NULL,
                `email` VARCHAR(150) NULL,
                `logo_url` VARCHAR(255) NULL,
                `invoice_prefix` VARCHAR(20) DEFAULT 'INV',
                `po_prefix` VARCHAR(20) DEFAULT 'PO',
                `dispatch_prefix` VARCHAR(20) DEFAULT 'DSP',
                `is_active` TINYINT(1) DEFAULT 1,
                `created_by` INT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_concerns_code` (`code`),
                INDEX `idx_concerns_active` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            Database::execute("CREATE TABLE IF NOT EXISTS `concern_modules` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `concern_id` INT NOT NULL,
                `module_key` VARCHAR(50) NOT NULL,
                `is_enabled` TINYINT(1) DEFAULT 1,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_concern_module` (`concern_id`, `module_key`),
                CONSTRAINT `fk_concern_module_concern` FOREIGN KEY (`concern_id`)
                    REFERENCES `concerns`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Ensure concern_type exists on concerns
            Database::ensureColumn('concerns', 'concern_type', "ENUM('HEAD_OFFICE','PRODUCTION','WAREHOUSE','RETAIL') NOT NULL DEFAULT 'HEAD_OFFICE' AFTER `code`");

            // Referenced by isProductionConcern(); absent on older installs.
            Database::ensureColumn('production_units', 'concern_id', 'INT NULL AFTER `id`');

            // Scoping columns referenced by the production/stock models and controllers.
            foreach (self::SCOPED_TABLES as $table) {
                Database::ensureColumn($table, 'concern_id', 'INT NULL');

                $indexName = 'idx_' . $table . '_concern_id';
                $hasIndex = Database::fetchOne(
                    "SELECT 1 FROM information_schema.STATISTICS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1",
                    [$table, $indexName]
                );
                if (!$hasIndex) {
                    try {
                        Database::execute("CREATE INDEX `$indexName` ON `$table` (`concern_id`)");
                    } catch (Throwable $e) {
                        // Column presence is what matters; ignore index creation races.
                    }
                }
            }

            // Keep a single usable concern so scoping/branding helpers never see an empty master.
            if (!Database::fetchOne("SELECT id FROM concerns LIMIT 1")) {
                Database::execute(
                    "INSERT INTO concerns (id, name, code, invoice_prefix, po_prefix, dispatch_prefix, is_active, created_at)
                     VALUES (1, 'GRM Apparels', 'CN-01', 'INV', 'PO', 'DSP', 1, NOW())"
                );
            }

            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['_concern_schema_checked'] = 1;
            }
        } catch (Throwable $e) {
            error_log("Concern schema bootstrap failed: " . $e->getMessage());
        }
    }

    /**
     * Get all active concerns.
     */
    public static function getAllActive(): array {
        self::ensureSchema();
        return Database::fetchAll("SELECT * FROM concerns WHERE is_active = 1 ORDER BY id ASC") ?: [];
    }

    /**
     * Find a concern by ID.
     */
    public static function find(int $id): ?array {
        self::ensureSchema();
        return Database::fetchOne("SELECT * FROM concerns WHERE id = ?", [$id]);
    }
}
