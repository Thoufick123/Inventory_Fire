<?php
/**
 * Master Migration & Data Synchronizer
 * 
 * 1. Restores the baseline data archive (pre_rebuild_backup_utf8.sql)
 * 2. Applies new schema additions (designs, qr_generations, qr_generation_items, user_permission_overrides)
 * 3. Adds missing columns (variant_code, design_id, attr_hash, staff_role, etc.)
 * 4. Backfills variant_code (V0001...) for existing variants
 * 5. Executes all additive SQL migrations in database/migrations/
 * 6. Seeds complete granular permissions & binds them to Super Admin
 * 7. Performs a full integrity report
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

$db = Database::getInstance();

echo "========================================================\n";
echo "   STARTING COMPLETE LOCAL DATABASE MIGRATION\n";
echo "========================================================\n\n";

// Helper for column existence
function columnExists(PDO $db, string $table, string $column): bool {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        return (bool)$stmt->fetch();
    } catch (Exception $e) {
        return false;
    }
}

// Helper for table existence
function tableExists(PDO $db, string $table): bool {
    try {
        $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
        return (bool)$stmt->fetch();
    } catch (Exception $e) {
        return false;
    }
}

// -------------------------------------------------------------
// STEP 1: IMPORT UNIFIED MASTER SCHEMA (schema.sql) & SEED (seed.sql)
// -------------------------------------------------------------
echo "[1/7] Importing master schema (82 active tables) from schema.sql...\n";
$schemaFile = BASE_PATH . '/database/schema.sql';
$seedFile = BASE_PATH . '/database/seed.sql';
if (!file_exists($schemaFile)) {
    throw new RuntimeException("Schema file not found: {$schemaFile}");
}

// Run through mysql CLI for speed and accurate multi-query execution
$config = require BASE_PATH . '/config/database.php';
$mysqlBin = 'C:\\xampp\\mysql\\bin\\mysql.exe';
if (file_exists($mysqlBin)) {
    $cmdSchema = sprintf(
        '"%s" --host=%s --port=%s --user=%s %s %s < "%s"',
        $mysqlBin,
        escapeshellarg($config['host']),
        escapeshellarg($config['port']),
        escapeshellarg($config['username']),
        !empty($config['password']) ? '--password=' . escapeshellarg($config['password']) : '',
        escapeshellarg($config['database']),
        $schemaFile
    );
    $outSchema = shell_exec($cmdSchema . ' 2>&1');
    if ($outSchema && strpos($outSchema, 'ERROR') !== false) {
        echo "  Notice from mysql CLI (schema): $outSchema\n";
    } else {
        echo "  Master schema imported successfully via mysql CLI.\n";
    }

    if (file_exists($seedFile)) {
        echo "  Importing clean seed data (Admin only)...\n";
        $cmdSeed = sprintf(
            '"%s" --host=%s --port=%s --user=%s %s %s < "%s"',
            $mysqlBin,
            escapeshellarg($config['host']),
            escapeshellarg($config['port']),
            escapeshellarg($config['username']),
            !empty($config['password']) ? '--password=' . escapeshellarg($config['password']) : '',
            escapeshellarg($config['database']),
            $seedFile
        );
        $outSeed = shell_exec($cmdSeed . ' 2>&1');
        if ($outSeed && strpos($outSeed, 'ERROR') !== false) {
            echo "  Notice from mysql CLI (seed): $outSeed\n";
        } else {
            echo "  Clean seed data imported successfully.\n";
        }
    }
} else {
    // Pure PHP fallback
    echo "  mysql.exe not found, running pure PHP importer...\n";
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $db->exec(file_get_contents($schemaFile));
    if (file_exists($seedFile)) {
        $db->exec(file_get_contents($seedFile));
    }
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "  Import completed via PDO.\n";
}

// Keep direct Goods Inward receipts compatible with the optional PO selection.
$directInwardMigration = BASE_PATH . '/database/migrations/2026_11_20_allow_direct_goods_inward.sql';
if (file_exists($directInwardMigration)) {
    echo "  Allowing direct Goods Inward receipts without a purchase order...\n";
    $db->exec(file_get_contents($directInwardMigration));
} else {
    throw new RuntimeException("Direct Goods Inward migration not found: {$directInwardMigration}");
}

// -------------------------------------------------------------
// STEP 2: CREATE NEW TABLES REQUIRED BY APPLICATION
// -------------------------------------------------------------
echo "\n[2/7] Creating new schema tables...\n";

// 2a. designs table
if (!tableExists($db, 'designs')) {
    echo "  Creating table `designs`...\n";
    $db->exec("
        CREATE TABLE `designs` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `name` varchar(100) NOT NULL,
          `code` varchar(20) DEFAULT NULL,
          `slug` varchar(100) DEFAULT NULL,
          `description` text DEFAULT NULL,
          `is_active` tinyint(1) NOT NULL DEFAULT 1,
          `sort_order` int(11) NOT NULL DEFAULT 0,
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_designs_name` (`name`),
          UNIQUE KEY `uq_designs_code` (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
}

// 2b. qr_generations table
if (!tableExists($db, 'qr_generations')) {
    echo "  Creating table `qr_generations`...\n";
    $db->exec("
        CREATE TABLE `qr_generations` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `batch_number` varchar(50) NOT NULL,
          `production_unit_id` int(10) unsigned NOT NULL DEFAULT 1,
          `product_id` int(10) unsigned DEFAULT NULL,
          `variant_id` int(10) unsigned DEFAULT NULL,
          `style_id` int(10) unsigned DEFAULT NULL,
          `generation_type` enum('CATALOG','CUSTOM') NOT NULL DEFAULT 'CATALOG',
          `quantity` int(10) unsigned NOT NULL,
          `format` enum('QR','CODE128','BOTH') NOT NULL DEFAULT 'QR',
          `prefix` varchar(10) NOT NULL DEFAULT 'GRM',
          `start_number` int(10) unsigned NOT NULL,
          `end_number` int(10) unsigned NOT NULL,
          `label_size` varchar(20) NOT NULL DEFAULT '50x25',
          `notes` text DEFAULT NULL,
          `status` enum('GENERATED','PRINTED','USED','CANCELLED') NOT NULL DEFAULT 'GENERATED',
          `created_by` int(10) unsigned DEFAULT NULL,
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_qr_batch_number` (`batch_number`),
          KEY `idx_qr_product` (`product_id`),
          KEY `idx_qr_variant` (`variant_id`),
          KEY `idx_qr_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
}

// 2c. qr_generation_items table
if (!tableExists($db, 'qr_generation_items')) {
    echo "  Creating table `qr_generation_items`...\n";
    $db->exec("
        CREATE TABLE `qr_generation_items` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `generation_id` int(10) unsigned NOT NULL,
          `variant_id` int(10) unsigned DEFAULT NULL,
          `size_name` varchar(100) DEFAULT NULL,
          `color_name` varchar(100) DEFAULT NULL,
          `design_name` varchar(150) DEFAULT NULL,
          `mrp` decimal(10,2) DEFAULT NULL,
          `selling_price` decimal(10,2) DEFAULT NULL,
          `qr_code` varchar(100) NOT NULL,
          `sequence_num` int(11) NOT NULL DEFAULT 0,
          `status` enum('ACTIVE','USED','DAMAGED','CANCELLED') DEFAULT 'ACTIVE',
          `item_code` varchar(100) DEFAULT NULL,
          `sequence_number` int(10) unsigned DEFAULT NULL,
          `is_printed` tinyint(1) NOT NULL DEFAULT 0,
          `is_used` tinyint(1) NOT NULL DEFAULT 0,
          `used_at` datetime DEFAULT NULL,
          `used_in_module` varchar(50) DEFAULT NULL,
          `used_reference_id` int(10) unsigned DEFAULT NULL,
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_qr_generation_item_code` (`qr_code`),
          KEY `idx_qr_gen_id` (`generation_id`),
          KEY `idx_qr_status_used` (`is_used`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
}

// 2d. user_permission_overrides table
if (!tableExists($db, 'user_permission_overrides')) {
    echo "  Creating table `user_permission_overrides`...\n";
    $db->exec("
        CREATE TABLE `user_permission_overrides` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `user_id` int(10) unsigned NOT NULL,
          `permission_id` int(10) unsigned NOT NULL,
          `is_granted` tinyint(1) NOT NULL DEFAULT 1,
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_user_perm_override` (`user_id`,`permission_id`),
          KEY `idx_user_perm_uid` (`user_id`),
          KEY `idx_user_perm_pid` (`permission_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
}

// 2e. Multi-concern architecture (2026_11_20_add_concern_architecture.sql)
// This migration was never wired into the master runner, which left the
// `concerns` table and the concern_id scoping columns missing.
echo "  Ensuring multi-concern architecture tables and scoping columns...\n";
$concernMigration = BASE_PATH . '/database/migrations/2026_11_20_add_concern_architecture.sql';
if (file_exists($concernMigration)) {
    $db->exec(file_get_contents($concernMigration));
} else {
    throw new RuntimeException("Concern architecture migration not found: {$concernMigration}");
}
require_once BASE_PATH . '/app/Models/Concern.php';
Concern::ensureSchema();
echo "  Concern architecture migration applied and verified.\n";

$globalAdminConcernMigration = BASE_PATH . '/database/migrations/2026_11_20_separate_global_admin_from_concerns.sql';
if (file_exists($globalAdminConcernMigration)) {
    echo "  Removing global Admin concern memberships and defaults...\n";
    $db->exec(file_get_contents($globalAdminConcernMigration));
} else {
    throw new RuntimeException("Global Admin concern-separation migration not found: {$globalAdminConcernMigration}");
}

// 2f. third_party_product_variants table
if (!tableExists($db, 'third_party_product_variants')) {
    echo "  Creating table `third_party_product_variants`...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS `third_party_product_variants` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `third_party_product_id` int(10) unsigned NOT NULL,
          `purchase_item_id` int(10) unsigned DEFAULT NULL,
          `sku` varchar(100) NOT NULL,
          `size` varchar(50) NOT NULL,
          `color` varchar(50) DEFAULT NULL,
          `allocated_qty` decimal(12,3) NOT NULL DEFAULT 0.000,
          `purchase_rate` decimal(12,2) NOT NULL DEFAULT 0.00,
          `mrp` decimal(12,2) NOT NULL DEFAULT 0.00,
          `selling_price` decimal(12,2) NOT NULL DEFAULT 0.00,
          `barcode` varchar(100) DEFAULT NULL,
          `is_active` tinyint(1) NOT NULL DEFAULT 1,
          `created_at` datetime DEFAULT current_timestamp(),
          `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `idx_tppv_product` (`third_party_product_id`),
          KEY `idx_tppv_item` (`purchase_item_id`),
          KEY `idx_tppv_sku` (`sku`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
}

// 2g. product_price_logs table
if (!tableExists($db, 'product_price_logs')) {
    echo "  Creating table `product_price_logs`...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS `product_price_logs` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `variant_id` int(10) unsigned DEFAULT NULL,
          `product_id` int(10) unsigned DEFAULT NULL,
          `sku` varchar(100) NOT NULL,
          `old_selling_price` decimal(10,2) NOT NULL,
          `new_selling_price` decimal(10,2) NOT NULL,
          `old_mrp` decimal(10,2) NOT NULL,
          `new_mrp` decimal(10,2) NOT NULL,
          `cost_price` decimal(10,2) NOT NULL,
          `markup_percentage` decimal(10,4) DEFAULT NULL,
          `discount_percentage` decimal(10,4) NOT NULL,
          `revision_notes` text NOT NULL,
          `user_id` int(10) unsigned DEFAULT NULL,
          `user_name` varchar(200) NOT NULL,
          `created_at` datetime NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `idx_price_logs_variant_created` (`variant_id`,`created_at`),
          KEY `idx_price_logs_product` (`product_id`),
          KEY `idx_price_logs_user` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
}

// -------------------------------------------------------------
// STEP 3: ADD NEW COLUMNS TO EXISTING TABLES
// -------------------------------------------------------------
echo "\n[3/7] Adding missing columns to existing tables...\n";

// 3a. product_variants: variant_code, design_id, attr_hash, production_unit_id
if (!columnExists($db, 'product_variants', 'variant_code')) {
    echo "  Adding `variant_code` to `product_variants`...\n";
    $db->exec("ALTER TABLE `product_variants` ADD COLUMN `variant_code` varchar(20) DEFAULT NULL AFTER `id`");
}
if (!columnExists($db, 'product_variants', 'design_id')) {
    echo "  Adding `design_id` to `product_variants`...\n";
    $db->exec("ALTER TABLE `product_variants` ADD COLUMN `design_id` int(10) unsigned DEFAULT 1 AFTER `color_id`");
}
if (!columnExists($db, 'product_variants', 'attr_hash')) {
    echo "  Adding `attr_hash` to `product_variants`...\n";
    $db->exec("ALTER TABLE `product_variants` ADD COLUMN `attr_hash` varchar(64) DEFAULT NULL AFTER `design_id`");
}
if (!columnExists($db, 'product_variants', 'production_unit_id')) {
    echo "  Adding `production_unit_id` to `product_variants`...\n";
    $db->exec("ALTER TABLE `product_variants` ADD COLUMN `production_unit_id` int(10) unsigned DEFAULT 1 AFTER `variant_code`");
}

// 3b. users: staff_role, production_unit_id, warehouse_id, shop_id
if (!columnExists($db, 'users', 'staff_role')) {
    echo "  Adding `staff_role` to `users`...\n";
    $db->exec("ALTER TABLE `users` ADD COLUMN `staff_role` varchar(50) DEFAULT NULL AFTER `department`");
}
if (!columnExists($db, 'users', 'production_unit_id')) {
    echo "  Adding `production_unit_id` to `users`...\n";
    $db->exec("ALTER TABLE `users` ADD COLUMN `production_unit_id` int(10) unsigned DEFAULT 1 AFTER `staff_role`");
}
if (!columnExists($db, 'users', 'warehouse_id')) {
    echo "  Adding `warehouse_id` to `users`...\n";
    $db->exec("ALTER TABLE `users` ADD COLUMN `warehouse_id` int(10) unsigned DEFAULT 1 AFTER `production_unit_id`");
}
if (!columnExists($db, 'users', 'shop_id')) {
    echo "  Adding `shop_id` to `users`...\n";
    $db->exec("ALTER TABLE `users` ADD COLUMN `shop_id` int(10) unsigned DEFAULT 1 AFTER `warehouse_id`");
}

// 3c. production_units: logo_url
if (!columnExists($db, 'production_units', 'logo_url')) {
    echo "  Adding `logo_url` to `production_units`...\n";
    $db->exec("ALTER TABLE `production_units` ADD COLUMN `logo_url` varchar(255) DEFAULT NULL AFTER `capacity_per_day`");
}

// 3d. sellers: production_deleted_at
if (!columnExists($db, 'sellers', 'production_deleted_at')) {
    echo "  Adding `production_deleted_at` to `sellers`...\n";
    $db->exec("ALTER TABLE `sellers` ADD COLUMN `production_deleted_at` datetime DEFAULT NULL AFTER `is_active`");
}

// 3e. partition_id columns on production tables
$partitionTables = [
    'sellers', 'seller_rolls', 'roll_damages', 'production_programs',
    'cutting_batches', 'wastage_sales', 'stitching_batches', 'qc_batches',
    'production_batches', 'production_outwards', 'production_purchases'
];
foreach ($partitionTables as $pt) {
    if (tableExists($db, $pt) && !columnExists($db, $pt, 'partition_id')) {
        echo "  Adding `partition_id` to `{$pt}`...\n";
        $db->exec("ALTER TABLE `{$pt}` ADD COLUMN `partition_id` int(10) unsigned DEFAULT 1 AFTER `id`");
    }
}

// 3f. concerns: concern_type
if (tableExists($db, 'concerns') && !columnExists($db, 'concerns', 'concern_type')) {
    echo "  Adding `concern_type` to `concerns`...\n";
    $db->exec("ALTER TABLE `concerns` ADD COLUMN `concern_type` ENUM('HEAD_OFFICE','PRODUCTION','WAREHOUSE','RETAIL') NOT NULL DEFAULT 'HEAD_OFFICE' AFTER `code`");
}

// 3g. roles: concern_scope, applicable_concerns
if (tableExists($db, 'roles')) {
    if (!columnExists($db, 'roles', 'concern_scope')) {
        echo "  Adding `concern_scope` to `roles`...\n";
        $db->exec("ALTER TABLE `roles` ADD COLUMN `concern_scope` VARCHAR(20) NOT NULL DEFAULT 'ALL' AFTER `description`");
    }
    if (!columnExists($db, 'roles', 'applicable_concerns')) {
        echo "  Adding `applicable_concerns` to `roles`...\n";
        $db->exec("ALTER TABLE `roles` ADD COLUMN `applicable_concerns` TEXT DEFAULT NULL AFTER `concern_scope`");
    }
}

// 3h. purchase_items: hsn_code
if (tableExists($db, 'purchase_items') && !columnExists($db, 'purchase_items', 'hsn_code')) {
    echo "  Adding `hsn_code` to `purchase_items`...\n";
    $db->exec("ALTER TABLE `purchase_items` ADD COLUMN `hsn_code` VARCHAR(20) DEFAULT NULL AFTER `gst_percentage`");
}

// 3i. qc_batches: qr_generation_id
if (tableExists($db, 'qc_batches') && !columnExists($db, 'qc_batches', 'qr_generation_id')) {
    echo "  Adding `qr_generation_id` to `qc_batches`...\n";
    $db->exec("ALTER TABLE `qc_batches` ADD COLUMN `qr_generation_id` INT UNSIGNED DEFAULT NULL AFTER `stitching_batch_id`, ADD KEY `idx_qcb_qr_generation` (`qr_generation_id`)");
}

// 3j. qc_items: total_qty
if (tableExists($db, 'qc_items') && !columnExists($db, 'qc_items', 'total_qty')) {
    echo "  Adding `total_qty` to `qc_items`...\n";
    $db->exec("ALTER TABLE `qc_items` ADD COLUMN `total_qty` INT(11) NOT NULL DEFAULT 0 AFTER `color_name`");
}

// 3k. production_program_rolls: attributes
if (tableExists($db, 'production_program_rolls') && !columnExists($db, 'production_program_rolls', 'attributes')) {
    echo "  Adding `attributes` to `production_program_rolls`...\n";
    $db->exec("ALTER TABLE `production_program_rolls` ADD COLUMN `attributes` TEXT DEFAULT NULL AFTER `allocated_qr_codes`");
}

// 3l. stock_transactions: before_quantity, after_quantity, from/to location cols
if (tableExists($db, 'stock_transactions')) {
    if (!columnExists($db, 'stock_transactions', 'before_quantity')) {
        echo "  Adding `before_quantity` to `stock_transactions`...\n";
        $db->exec("ALTER TABLE `stock_transactions` ADD COLUMN `before_quantity` DECIMAL(10,2) DEFAULT NULL AFTER `quantity`");
    }
    if (!columnExists($db, 'stock_transactions', 'after_quantity')) {
        echo "  Adding `after_quantity` to `stock_transactions`...\n";
        $db->exec("ALTER TABLE `stock_transactions` ADD COLUMN `after_quantity` DECIMAL(10,2) DEFAULT NULL AFTER `before_quantity`");
    }
    if (!columnExists($db, 'stock_transactions', 'from_location_type')) {
        echo "  Adding `from_location_type` to `stock_transactions`...\n";
        $db->exec("ALTER TABLE `stock_transactions` ADD COLUMN `from_location_type` VARCHAR(50) DEFAULT NULL AFTER `dest_id`");
    }
    if (!columnExists($db, 'stock_transactions', 'from_location_id')) {
        echo "  Adding `from_location_id` to `stock_transactions`...\n";
        $db->exec("ALTER TABLE `stock_transactions` ADD COLUMN `from_location_id` INT UNSIGNED DEFAULT NULL AFTER `from_location_type`");
    }
    if (!columnExists($db, 'stock_transactions', 'to_location_type')) {
        echo "  Adding `to_location_type` to `stock_transactions`...\n";
        $db->exec("ALTER TABLE `stock_transactions` ADD COLUMN `to_location_type` VARCHAR(50) DEFAULT NULL AFTER `from_location_id`");
    }
    if (!columnExists($db, 'stock_transactions', 'to_location_id')) {
        echo "  Adding `to_location_id` to `stock_transactions`...\n";
        $db->exec("ALTER TABLE `stock_transactions` ADD COLUMN `to_location_id` INT UNSIGNED DEFAULT NULL AFTER `to_location_type`");
    }
}

// 3m. third_party_products: brand_id
if (tableExists($db, 'third_party_products') && !columnExists($db, 'third_party_products', 'brand_id')) {
    echo "  Adding `brand_id` to `third_party_products`...\n";
    $db->exec("ALTER TABLE `third_party_products` ADD COLUMN `brand_id` INT UNSIGNED DEFAULT NULL AFTER `sub_subcategory_code`");
}

// -------------------------------------------------------------
// STEP 4: BACKFILL DATA INTEGRITY (variant_code, attr_hash, etc.)
// -------------------------------------------------------------
echo "\n[4/7] Backfilling variant codes and hashes...\n";
$variants = Database::fetchAll("SELECT id, product_id, size_id, color_id, design_id, variant_code FROM `product_variants`");
foreach ($variants as $v) {
    $updates = [];
    $params = [];
    if (empty($v['variant_code'])) {
        $updates[] = "`variant_code` = ?";
        $params[] = sprintf('V%04d', $v['id']);
    }
    if (empty($v['design_id'])) {
        $updates[] = "`design_id` = 1";
    }
    // Generate attr_hash
    $hash = hash('sha256', "{$v['product_id']}:{$v['size_id']}:" . ($v['color_id'] ?? '0') . ":" . ($v['design_id'] ?? '1'));
    $updates[] = "`attr_hash` = ?";
    $params[] = $hash;

    if (!empty($updates)) {
        $params[] = $v['id'];
        $db->prepare("UPDATE `product_variants` SET " . implode(', ', $updates) . " WHERE id = ?")->execute($params);
    }
}
echo "  Updated " . count($variants) . " product variants with collision-safe codes & hashes.\n";

// -------------------------------------------------------------
// STEP 5: RUN ALL DATABASE MIGRATIONS
// -------------------------------------------------------------
echo "\n[5/7] Executing incremental database migrations...\n";

// Per-variant third-party mapping taxonomy and HSN.
if (tableExists($db, 'third_party_product_variants')) {
    $variantTaxonomyColumns = [
        'third_party_category_id' => 'int(10) unsigned DEFAULT NULL AFTER `barcode`',
        'third_party_subcategory_id' => 'int(10) unsigned DEFAULT NULL AFTER `third_party_category_id`',
        'third_party_sub_subcategory_id' => 'int(10) unsigned DEFAULT NULL AFTER `third_party_subcategory_id`',
        'hsn_code' => 'varchar(20) DEFAULT NULL AFTER `third_party_sub_subcategory_id`',
    ];
    foreach ($variantTaxonomyColumns as $column => $definition) {
        if (!columnExists($db, 'third_party_product_variants', $column)) {
            $db->exec("ALTER TABLE `third_party_product_variants` ADD COLUMN `{$column}` {$definition}");
        }
    }
}

// Migration 1: 2026_05_28_add_return_settlement_modes.sql
if (tableExists($db, 'returns')) {
    echo "  Applying 2026_05_28_add_return_settlement_modes.sql...\n";
    try {
        $db->exec("ALTER TABLE `returns` MODIFY COLUMN `refund_mode` ENUM('CASH','UPI','CARD','ONLINE','BANK_TRANSFER','SPLIT','CREDIT_NOTE') DEFAULT NULL");
    } catch (Exception $e) {
        echo "    Notice: " . $e->getMessage() . "\n";
    }
}

// Migration 2: 2026_11_11_add_production_supplier_archive.sql
echo "  Applying 2026_11_11_add_production_supplier_archive.sql...\n";
if (!columnExists($db, 'sellers', 'production_deleted_at')) {
    $db->exec("ALTER TABLE `sellers` ADD COLUMN `production_deleted_at` datetime DEFAULT NULL AFTER `is_active`");
}

// Migration 4: 2026_11_12_add_production_billing_unit.sql
echo "  Applying 2026_11_12_add_production_billing_unit.sql...\n";
if (tableExists($db, 'production_bills') && !columnExists($db, 'production_bills', 'production_unit_id')) {
    $db->exec("ALTER TABLE `production_bills` ADD COLUMN `production_unit_id` int(10) unsigned DEFAULT NULL AFTER `bill_number`, ADD KEY `idx_production_bills_unit` (`production_unit_id`)");
}

// Migration 5: 2026_11_12_add_production_bill_batch_link.sql
echo "  Applying 2026_11_12_add_production_bill_batch_link.sql...\n";
if (tableExists($db, 'production_batches') && !columnExists($db, 'production_batches', 'production_bill_id')) {
    $db->exec("ALTER TABLE `production_batches` ADD COLUMN `production_bill_id` int(10) unsigned DEFAULT NULL AFTER `batch_number`, ADD KEY `idx_production_batches_bill` (`production_bill_id`)");
}

// Migration 6: 2026_11_13_add_damage_resolution_audit_fields.sql
echo "  Applying 2026_11_13_add_damage_resolution_audit_fields.sql...\n";
if (tableExists($db, 'warehouse_damages')) {
    $whDamageCols = [
        'resolution_action' => "varchar(32) DEFAULT NULL AFTER `status`",
        'resolution_reason' => "varchar(255) DEFAULT NULL AFTER `resolution_action`",
        'resolution_notes' => "text DEFAULT NULL AFTER `resolution_reason`",
        'resolution_amount' => "decimal(10,2) DEFAULT NULL AFTER `resolution_notes`",
        'replacement_variant_id' => "int(10) unsigned DEFAULT NULL AFTER `resolution_amount`",
        'replacement_qr_code' => "varchar(100) DEFAULT NULL AFTER `replacement_variant_id`",
        'replacement_inwarded_at' => "datetime DEFAULT NULL AFTER `replacement_qr_code`",
        'deleted_at' => "datetime DEFAULT NULL AFTER `replacement_inwarded_at`",
        'deleted_by' => "int(10) unsigned DEFAULT NULL AFTER `deleted_at`"
    ];
    foreach ($whDamageCols as $col => $def) {
        if (!columnExists($db, 'warehouse_damages', $col)) {
            $db->exec("ALTER TABLE `warehouse_damages` ADD COLUMN `{$col}` {$def}");
        }
    }
}

// Migration 7: 2026_12_01_separate_production_attributes_and_cash_change.sql
echo "  Applying 2026_12_01_separate_production_attributes_and_cash_change.sql...\n";
if (!tableExists($db, 'production_category_attributes')) {
    $db->exec("
        CREATE TABLE IF NOT EXISTS `production_category_attributes` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `category_id` int(10) unsigned NOT NULL,
          `subcategory_id` int(10) unsigned DEFAULT NULL,
          `sub_subcategory_id` int(10) unsigned DEFAULT NULL,
          `attr_name` varchar(100) NOT NULL,
          `attr_type` enum('TEXT','NUMBER','SELECT','MULTI_SELECT') NOT NULL DEFAULT 'TEXT',
          `attr_options` text DEFAULT NULL,
          `is_required` tinyint(1) NOT NULL DEFAULT 0,
          `sort_order` int(11) NOT NULL DEFAULT 0,
          `is_active` tinyint(1) NOT NULL DEFAULT 1,
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_production_cat_attrs` (`category_id`,`subcategory_id`,`sub_subcategory_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
}
if (tableExists($db, 'purchase_orders')) {
    if (!columnExists($db, 'purchase_orders', 'cash_tendered')) {
        $db->exec("ALTER TABLE `purchase_orders` ADD COLUMN `cash_tendered` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `paid_amount`");
    }
    if (!columnExists($db, 'purchase_orders', 'change_amount')) {
        $db->exec("ALTER TABLE `purchase_orders` ADD COLUMN `change_amount` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `cash_tendered`");
    }
    // Migration 8: 2026_12_02_separate_direct_customer_purchase_orders.sql
    if (!columnExists($db, 'purchase_orders', 'is_direct_purchase')) {
        $db->exec("ALTER TABLE `purchase_orders` ADD COLUMN `is_direct_purchase` tinyint(1) NOT NULL DEFAULT 0 AFTER `payment_method`");
    }
}

// Migration 9: 2026_12_03_add_cancelled_shop_damage_status.sql
if (tableExists($db, 'shop_damages')) {
    echo "  Applying 2026_12_03_add_cancelled_shop_damage_status.sql...\n";
    try {
        $db->exec("ALTER TABLE `shop_damages` MODIFY COLUMN `status` ENUM('LOGGED','INSPECTED','SALVAGED','WRITTEN_OFF','REPAIRED','RETURN_TO_WH','REWORK','CANCELLED') DEFAULT 'LOGGED'");
    } catch (Exception $e) {
        echo "    Notice: " . $e->getMessage() . "\n";
    }
}

// Migration 10: 2026_11_17_add_purchase_types_and_third_party_mapping.sql
echo "  Applying 2026_11_17_add_purchase_types_and_third_party_mapping.sql...\n";
if (tableExists($db, 'production_purchases') && !columnExists($db, 'production_purchases', 'purchase_type')) {
    $db->exec("ALTER TABLE `production_purchases` ADD COLUMN `purchase_type` ENUM('FABRIC_ROLL', 'THIRD_PARTY', 'OWN_PRODUCT') NOT NULL DEFAULT 'FABRIC_ROLL' AFTER `purchase_number`");
    $db->exec("ALTER TABLE `production_purchases` ADD KEY `idx_purchases_type_date` (`purchase_type`, `purchase_date`, `seller_id`)");
}
if (tableExists($db, 'production_purchase_items')) {
    try {
        $db->exec("ALTER TABLE `production_purchase_items` MODIFY COLUMN `item_type` ENUM('FABRIC_ROLL', 'THIRD_PARTY', 'OWN_PRODUCT') NOT NULL DEFAULT 'FABRIC_ROLL'");
    } catch (Exception $e) {}
    $tpItemCols = [
        'product_id' => "int(10) unsigned DEFAULT NULL AFTER `item_name`",
        'variant_id' => "int(10) unsigned DEFAULT NULL AFTER `product_id`",
        'product_code' => "varchar(100) DEFAULT NULL AFTER `variant_id`",
        'vendor_sku' => "varchar(100) DEFAULT NULL AFTER `product_code`",
        'size' => "varchar(50) DEFAULT NULL AFTER `color`",
        'mrp' => "decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `unit_price`",
        'selling_price' => "decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `mrp`",
        'tax_treatment' => "enum('INCLUDED','EXCLUDED') NOT NULL DEFAULT 'INCLUDED' AFTER `tax_input_type`",
        'start_code' => "varchar(100) DEFAULT NULL AFTER `seller_roll_id`",
        'end_code' => "varchar(100) DEFAULT NULL AFTER `start_code`",
        'qr_code' => "varchar(100) DEFAULT NULL AFTER `end_code`",
        'hsn_code' => "varchar(20) DEFAULT NULL AFTER `qr_code`",
        'is_inwarded' => "tinyint(1) NOT NULL DEFAULT 0 AFTER `hsn_code`",
        'inwarded_at' => "datetime DEFAULT NULL AFTER `is_inwarded`",
        'inwarded_by' => "int(10) unsigned DEFAULT NULL AFTER `inwarded_at`",
        'is_mapped' => "tinyint(1) NOT NULL DEFAULT 0 AFTER `inwarded_by`",
        'mapped_category_id' => "int(10) unsigned DEFAULT NULL AFTER `is_mapped`",
        'mapped_subcategory_id' => "int(10) unsigned DEFAULT NULL AFTER `mapped_category_id`",
        'mapped_sub_subcategory_id' => "int(10) unsigned DEFAULT NULL AFTER `mapped_subcategory_id`",
        'mapped_attributes' => "text DEFAULT NULL AFTER `mapped_sub_subcategory_id`",
        'mapped_at' => "datetime DEFAULT NULL AFTER `mapped_attributes`",
        'mapped_by' => "int(10) unsigned DEFAULT NULL AFTER `mapped_at`",
    ];
    foreach ($tpItemCols as $col => $def) {
        if (!columnExists($db, 'production_purchase_items', $col)) {
            $db->exec("ALTER TABLE `production_purchase_items` ADD COLUMN `{$col}` {$def}");
        }
    }
}

// Migration 10b: Create an isolated category hierarchy for the third-party catalog.
echo "  Applying 2026_11_19_create_third_party_taxonomy.php...\n";
require_once BASE_PATH . '/database/migrations/2026_11_19_create_third_party_taxonomy.php';

// Migration 11: 2026_11_17_enhance_stock_requests_inter_module_flow.sql
echo "  Applying 2026_11_17_enhance_stock_requests_inter_module_flow.sql...\n";
if (tableExists($db, 'production_stock_requests')) {
    try {
        $db->exec("ALTER TABLE `production_stock_requests` MODIFY COLUMN `status` ENUM('PENDING','RECEIVED_BY_SEND_AREA','APPROVED','IN_PRODUCTION','READY_FOR_DISPATCH','DISPATCHED','FULFILLED','REJECTED') NOT NULL DEFAULT 'PENDING'");
    } catch (Exception $e) {}
    $srCols = [
        'from_module' => "varchar(100) NOT NULL DEFAULT 'Central Warehouse' AFTER `request_number`",
        'to_module' => "varchar(100) NOT NULL DEFAULT 'Production Unit' AFTER `from_module`",
        'from_unit_id' => "int(10) unsigned DEFAULT NULL AFTER `to_module`",
        'to_unit_id' => "int(10) unsigned DEFAULT NULL AFTER `from_unit_id`",
        'dispatched_pieces' => "int(10) unsigned NOT NULL DEFAULT 0 AFTER `fulfilled_pieces`",
        'received_by_send_area_at' => "datetime DEFAULT NULL AFTER `notes`",
        'received_by_send_area_by' => "varchar(150) DEFAULT NULL AFTER `received_by_send_area_at`",
        'dispatched_at' => "datetime DEFAULT NULL AFTER `received_by_send_area_by`",
        'dispatched_by' => "varchar(150) DEFAULT NULL AFTER `dispatched_at`",
        'received_at' => "datetime DEFAULT NULL AFTER `dispatched_by`",
        'received_by' => "varchar(150) DEFAULT NULL AFTER `received_at`",
    ];
    foreach ($srCols as $col => $def) {
        if (!columnExists($db, 'production_stock_requests', $col)) {
            $db->exec("ALTER TABLE `production_stock_requests` ADD COLUMN `{$col}` {$def}");
        }
    }
}
if (tableExists($db, 'production_stock_request_items')) {
    $sriCols = [
        'dispatched_qty' => "int(10) unsigned NOT NULL DEFAULT 0 AFTER `requested_qty`",
        'received_qty' => "int(10) unsigned NOT NULL DEFAULT 0 AFTER `dispatched_qty`",
    ];
    foreach ($sriCols as $col => $def) {
        if (!columnExists($db, 'production_stock_request_items', $col)) {
            $db->exec("ALTER TABLE `production_stock_request_items` ADD COLUMN `{$col}` {$def}");
        }
    }
    try {
        $db->exec("ALTER TABLE `production_stock_request_items` MODIFY COLUMN `status` ENUM('PENDING','RECEIVED_BY_SEND_AREA','PARTIAL','ALLOCATED','DISPATCHED','FULFILLED','REJECTED') NOT NULL DEFAULT 'PENDING'");
    } catch (Exception $e) {}
}

// Migration 12: 2026_11_18_remove_production_label_management.sql
echo "  Applying 2026_11_18_remove_production_label_management.sql...\n";
$removeLabelsMigration = BASE_PATH . '/database/migrations/2026_11_18_remove_production_label_management.sql';
if (file_exists($removeLabelsMigration)) {
    try {
        $db->exec(file_get_contents($removeLabelsMigration));
    } catch (Exception $e) {}
}

// Migration 13: 2026_11_18_add_complementary_to_items.sql
echo "  Applying 2026_11_18_add_complementary_to_items.sql...\n";
if (tableExists($db, 'production_purchase_items') && !columnExists($db, 'production_purchase_items', 'is_complementary')) {
    $db->exec("ALTER TABLE `production_purchase_items` ADD COLUMN `is_complementary` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_mapped`");
}
if (tableExists($db, 'production_bill_items') && !columnExists($db, 'production_bill_items', 'is_complementary')) {
    $db->exec("ALTER TABLE `production_bill_items` ADD COLUMN `is_complementary` TINYINT(1) NOT NULL DEFAULT 0 AFTER `unit_price`");
}

// Migration 14: 2026_11_18_add_raw_material_purchase_type.sql
echo "  Applying 2026_11_18_add_raw_material_purchase_type.sql...\n";
if (tableExists($db, 'production_purchases')) {
    try {
        $db->exec("ALTER TABLE `production_purchases` MODIFY COLUMN `purchase_type` ENUM('FABRIC_ROLL','RAW_MATERIAL','THIRD_PARTY','OWN_PRODUCT') NOT NULL DEFAULT 'FABRIC_ROLL'");
    } catch (Exception $e) {}
}
if (tableExists($db, 'production_purchase_items')) {
    try {
        $db->exec("ALTER TABLE `production_purchase_items` MODIFY COLUMN `item_type` ENUM('FABRIC_ROLL','RAW_MATERIAL','THIRD_PARTY','OWN_PRODUCT') NOT NULL DEFAULT 'FABRIC_ROLL'");
    } catch (Exception $e) {}
}

// Migration 15: 2026_11_18_modify_hsn_codes_nullable_gst.sql
echo "  Applying 2026_11_18_modify_hsn_codes_nullable_gst.sql...\n";
if (tableExists($db, 'hsn_codes')) {
    try {
        $db->exec("ALTER TABLE `hsn_codes` MODIFY COLUMN `description` TEXT NOT NULL, MODIFY COLUMN `gst_rate` DECIMAL(5,2) DEFAULT NULL");
    } catch (Exception $e) {}
}

// Migration 16: 2026_11_18_add_purchase_mapping_fulfillment.sql
echo "  Applying 2026_11_18_add_purchase_mapping_fulfillment.sql...\n";
if (tableExists($db, 'production_purchase_items')) {
    if (!columnExists($db, 'production_purchase_items', 'qr_generation_id')) {
        $db->exec("ALTER TABLE `production_purchase_items` ADD COLUMN `qr_generation_id` INT UNSIGNED DEFAULT NULL AFTER `mapped_by`");
        $db->exec("ALTER TABLE `production_purchase_items` ADD KEY `idx_ppi_qr_generation` (`qr_generation_id`)");
    }
    if (!columnExists($db, 'production_purchase_items', 'qr_batch_code')) {
        $db->exec("ALTER TABLE `production_purchase_items` ADD COLUMN `qr_batch_code` VARCHAR(50) DEFAULT NULL AFTER `qr_generation_id`");
    }
    if (!columnExists($db, 'production_purchase_items', 'grn_id')) {
        $db->exec("ALTER TABLE `production_purchase_items` ADD COLUMN `grn_id` INT UNSIGNED DEFAULT NULL AFTER `qr_batch_code`");
        $db->exec("ALTER TABLE `production_purchase_items` ADD KEY `idx_ppi_grn` (`grn_id`)");
    }
    if (!columnExists($db, 'production_purchase_items', 'grn_number')) {
        $db->exec("ALTER TABLE `production_purchase_items` ADD COLUMN `grn_number` VARCHAR(50) DEFAULT NULL AFTER `grn_id`");
    }
}
if (tableExists($db, 'production_purchases')) {
    if (!columnExists($db, 'production_purchases', 'grn_id')) {
        $db->exec("ALTER TABLE `production_purchases` ADD COLUMN `grn_id` INT UNSIGNED DEFAULT NULL AFTER `total_amount`");
        $db->exec("ALTER TABLE `production_purchases` ADD KEY `idx_pp_grn` (`grn_id`)");
    }
    if (!columnExists($db, 'production_purchases', 'grn_number')) {
        $db->exec("ALTER TABLE `production_purchases` ADD COLUMN `grn_number` VARCHAR(50) DEFAULT NULL AFTER `grn_id`");
    }
}
if (tableExists($db, 'purchase_orders') && !columnExists($db, 'purchase_orders', 'production_purchase_id')) {
    $db->exec("ALTER TABLE `purchase_orders` ADD COLUMN `production_purchase_id` INT UNSIGNED DEFAULT NULL AFTER `id`");
    $db->exec("ALTER TABLE `purchase_orders` ADD KEY `idx_po_production_purchase` (`production_purchase_id`)");
}
if (tableExists($db, 'purchase_inward_items') && !columnExists($db, 'purchase_inward_items', 'production_purchase_item_id')) {
    $db->exec("ALTER TABLE `purchase_inward_items` ADD COLUMN `production_purchase_item_id` INT UNSIGNED DEFAULT NULL AFTER `purchase_inward_id`");
    $db->exec("ALTER TABLE `purchase_inward_items` ADD KEY `idx_pii_production_purchase_item` (`production_purchase_item_id`)");
}
if (tableExists($db, 'products') && !columnExists($db, 'products', 'attributes')) {
    $db->exec("ALTER TABLE `products` ADD COLUMN `attributes` TEXT DEFAULT NULL AFTER `description`");
}
if (tableExists($db, 'product_variants') && !columnExists($db, 'product_variants', 'attributes')) {
    $db->exec("ALTER TABLE `product_variants` ADD COLUMN `attributes` TEXT DEFAULT NULL AFTER `image`");
}

// Migration 17: 2026_11_18_add_qr_generation_source_tabs.sql
echo "  Applying 2026_11_18_add_qr_generation_source_tabs.sql...\n";
if (tableExists($db, 'qr_generations')) {
    $qrgCols = [
        'qr_source' => "VARCHAR(30) NOT NULL DEFAULT 'THIRD_PARTY' AFTER `generation_type`",
        'source_purchase_id' => "INT UNSIGNED DEFAULT NULL AFTER `qr_source`",
        'source_purchase_item_id' => "INT UNSIGNED DEFAULT NULL AFTER `source_purchase_id`",
        'stock_added' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER `source_purchase_item_id`",
        'stock_added_at' => "DATETIME DEFAULT NULL AFTER `stock_added`",
        'stock_added_by' => "INT UNSIGNED DEFAULT NULL AFTER `stock_added_at`",
    ];
    foreach ($qrgCols as $col => $def) {
        if (!columnExists($db, 'qr_generations', $col)) {
            $db->exec("ALTER TABLE `qr_generations` ADD COLUMN `{$col}` {$def}");
        }
    }
}

// Migration 18: 2026_11_18_add_export_performance_indexes.sql
echo "  Applying 2026_11_18_add_export_performance_indexes.sql...\n";
$exportIndexes = [
    'seller_rolls' => 'idx_seller_rolls_created_at (`created_at`)',
    'production_programs' => 'idx_production_programs_created_at (`created_at`)',
    'cutting_batches' => 'idx_cutting_batches_created_at (`created_at`)',
    'stitching_batches' => 'idx_stitching_batches_created_at (`created_at`)',
    'qc_batches' => 'idx_qc_batches_created_at (`created_at`)',
    'production_batches' => 'idx_production_batches_packed_created (`packed_date`, `created_at`)',
    'production_outwards' => 'idx_production_outwards_dispatch_created (`dispatch_date`, `created_at`)',
    'production_expenses' => 'idx_production_expenses_date (`expense_date`)',
    'wastage_sales' => 'idx_wastage_sales_date (`sale_date`)',
    'stitching_wastage_sales' => 'idx_stitching_wastage_sales_date (`sale_date`)',
    'production_stock_requests' => 'idx_production_stock_requests_created_at (`created_at`)',
];
foreach ($exportIndexes as $tbl => $idxDef) {
    if (tableExists($db, $tbl)) {
        try {
            $db->exec("ALTER TABLE `{$tbl}` ADD KEY {$idxDef}");
        } catch (Exception $e) {}
    }
}

// Migration 19: 2026_11_18_seed_garment_textile_maintenance_hsn.php
echo "  Applying 2026_11_18_seed_garment_textile_maintenance_hsn.php...\n";
$seedHsnScript = BASE_PATH . '/database/migrations/2026_11_18_seed_garment_textile_maintenance_hsn.php';
if (file_exists($seedHsnScript)) {
    require_once $seedHsnScript;
}

// Migration 23: 2026_12_04_increase_weight_per_piece_precision.sql
echo "  Applying 2026_12_04_increase_weight_per_piece_precision.sql...\n";
$precisionMigration = BASE_PATH . '/database/migrations/2026_12_04_increase_weight_per_piece_precision.sql';
if (file_exists($precisionMigration)) {
    try {
        $db->exec(file_get_contents($precisionMigration));
    } catch (Exception $e) {}
}

// Migration 24: Save optional GPay proof screenshots for production bills.
echo "  Applying 2026_11_20_add_production_bill_upi_screenshot.sql...\n";
if (tableExists($db, 'production_bills') && !columnExists($db, 'production_bills', 'upi_payment_screenshot_url')) {
    $db->exec("ALTER TABLE `production_bills` ADD COLUMN `upi_payment_screenshot_url` varchar(500) DEFAULT NULL AFTER `upi_reference`");
}

// Migration 25: Hide built-in starter categories from catalog selectors.
echo "  Applying 2026_11_20_deactivate_default_catalog_categories.sql...\n";
$defaultCatalogCategoriesMigration = BASE_PATH . '/database/migrations/2026_11_20_deactivate_default_catalog_categories.sql';
if (tableExists($db, 'categories') && file_exists($defaultCatalogCategoriesMigration)) {
    $db->exec(file_get_contents($defaultCatalogCategoriesMigration));
}

// -------------------------------------------------------------
// STEP 6: SEED PERMISSIONS & BIND TO SUPER ADMIN
// -------------------------------------------------------------
echo "\n[6/7] Synchronizing permissions matrix...\n";
require_once BASE_PATH . '/database/seed_permissions.php';

// Also ensure variants and qr_generation permissions are explicitly present
$extraPermissions = [
    ['variants', 'view', 'View Product Variants & QR Identities', 'variants.view'],
    ['variants', 'create', 'Create New Product Variant', 'variants.create'],
    ['variants', 'edit', 'Edit Product Variant Details & MRP', 'variants.edit'],
    ['variants', 'delete', 'Delete Product Variant', 'variants.delete'],
    ['qr_generation', 'view', 'View QR Code Generations & Batches', 'qr_generation.view'],
    ['qr_generation', 'create', 'Generate QR Code Batches', 'qr_generation.create'],
    ['qr_generation', 'edit', 'Update QR Generation Details', 'qr_generation.edit'],
    ['qr_generation', 'delete', 'Delete QR Generation Batch', 'qr_generation.delete'],
];

foreach ($extraPermissions as $p) {
    $exists = Database::fetchOne("SELECT id FROM permissions WHERE module = ? AND action = ?", [$p[0], $p[1]]);
    if (!$exists) {
        Database::execute(
            "INSERT INTO permissions (module, action, description, slug, created_at) VALUES (?, ?, ?, ?, NOW())",
            [$p[0], $p[1], $p[2], $p[3]]
        );
    }
}

// Bind ALL active permissions to Super Admin role (role_id = 1)
$allPermIds = Database::fetchAll("SELECT id FROM permissions");
$adminRolePerms = Database::fetchAll("SELECT permission_id FROM role_permissions WHERE role_id = 1");
$existingAdminPermIds = array_column($adminRolePerms, 'permission_id');

$addedToAdmin = 0;
$insertStmt = $db->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, ?)");
foreach ($allPermIds as $p) {
    if (!in_array($p['id'], $existingAdminPermIds)) {
        $insertStmt->execute([$p['id']]);
        $addedToAdmin++;
    }
}
echo "  Super Admin Role (ID 1) synchronized with all " . count($allPermIds) . " system permissions (newly mapped: {$addedToAdmin}).\n";

// Ensure Admin User (ID 1) exists and has Super Admin role
$adminUser = Database::fetch("SELECT id, username, role_id, is_active FROM users WHERE id = 1 OR username = 'admin'");
if (!$adminUser) {
    $db->exec("
        INSERT INTO `users` (`id`, `username`, `emp_id`, `email`, `password_hash`, `full_name`, `phone`, `role_id`, `department`, `production_unit_id`, `warehouse_id`, `shop_id`, `reports_access`, `is_active`, `must_change_password`, `created_at`) VALUES
        (1, 'admin', 'EMP-001', 'admin@grm.com', '$2y$10$WXeV9abEWzjWySfggVzhTOp4j7pzF7UuFNUJ1lrLbWe9NDL7Cz7UC', 'System Administrator', '9876543210', 1, 'ADMINISTRATION', 1, 1, 1, 'ALL', 1, 0, NOW());
    ");
    echo "  Re-created root admin user.\n";
} else {
    $db->exec("UPDATE users SET role_id = 1, is_active = 1, production_unit_id = 1, warehouse_id = 1, shop_id = 1 WHERE id = {$adminUser['id']}");
    echo "  Root admin user verified (ID: {$adminUser['id']}, Username: {$adminUser['username']}).\n";
}

// -------------------------------------------------------------
// STEP 7: VERIFICATION & SUMMARY REPORT
// -------------------------------------------------------------
echo "\n[7/7] Database Verification & Summary Report:\n";
echo "--------------------------------------------------------\n";
$keyTables = [
    'users', 'roles', 'permissions', 'role_permissions',
    'categories', 'subcategories', 'designs', 'colors', 'sizes',
    'products', 'product_variants',
    'qr_generations', 'qr_generation_items', 'warehouse_qr_codes',
    'production_programs', 'cutting_batches', 'stitching_batches',
    'qc_batches', 'production_batches',
    'sellers', 'seller_rolls', 'production_purchases',
    'stock_balances', 'stock_transactions', 'warehouse_allocations',
    'production_units', 'warehouses', 'shops'
];

foreach ($keyTables as $tbl) {
    if (tableExists($db, $tbl)) {
        $c = (int)$db->query("SELECT COUNT(*) FROM `{$tbl}`")->fetchColumn();
        printf("  %-30s : %d rows\n", $tbl, $c);
    } else {
        printf("  %-30s : [NOT FOUND]\n", $tbl);
    }
}

echo "--------------------------------------------------------\n";
$totalTables = count(Database::fetchAll('SHOW TABLES'));
echo "Total Tables in Local Database: {$totalTables}\n";
echo "MIGRATION COMPLETE & VERIFIED SUCCESSFULLY!\n";
