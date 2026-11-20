-- =========================================================================
-- Multi-Concern Architecture Migration
-- Based on Multi-Concern Implementation Blueprint (concern.pdf)
-- =========================================================================

-- 1. Create concerns Master Table
CREATE TABLE IF NOT EXISTS `concerns` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL COMMENT 'Display Name (e.g. GRM Apparels Unit 1)',
  `code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Short unique code (e.g. CN-01, GRM-MFG)',
  `concern_type` ENUM('HEAD_OFFICE','PRODUCTION','WAREHOUSE','RETAIL') NOT NULL DEFAULT 'HEAD_OFFICE',
  `legal_name` VARCHAR(255) NULL COMMENT 'Registered Company Name',
  `gst_number` VARCHAR(15) NULL COMMENT '15-digit GSTIN',
  `pan_number` VARCHAR(10) NULL COMMENT '10-digit PAN',
  `address` TEXT NULL,
  `city` VARCHAR(100) NULL,
  `state` VARCHAR(100) NULL,
  `pincode` VARCHAR(10) NULL,
  `phone` VARCHAR(20) NULL,
  `email` VARCHAR(150) NULL,
  `logo_url` VARCHAR(255) NULL COMMENT 'Brand logo printed on invoices/labels',
  `invoice_prefix` VARCHAR(20) DEFAULT 'INV' COMMENT 'Prefix for POS & bills (e.g. GRM-A-)',
  `po_prefix` VARCHAR(20) DEFAULT 'PO' COMMENT 'Prefix for purchases (e.g. PO-A-)',
  `dispatch_prefix` VARCHAR(20) DEFAULT 'DSP' COMMENT 'Prefix for gate passes',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_by` INT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_concerns_code` (`code`),
  INDEX `idx_concerns_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create concern_modules Entitlement Matrix Table
CREATE TABLE IF NOT EXISTS `concern_modules` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `concern_id` INT NOT NULL,
  `module_key` VARCHAR(50) NOT NULL COMMENT 'e.g. production, purchases, warehouse, retail_pos, digital_sales, returns, expenses, assets',
  `is_enabled` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_concern_module` (`concern_id`, `module_key`),
  CONSTRAINT `fk_concern_module_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create user_concerns Access Control Table
CREATE TABLE IF NOT EXISTS `user_concerns` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `concern_id` INT NOT NULL,
  `is_default` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_user_concern` (`user_id`, `concern_id`),
  CONSTRAINT `fk_user_concerns_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_concerns_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Entity Partitioning across Masters & Core Transaction Tables
ALTER TABLE `production_units` ADD COLUMN IF NOT EXISTS `concern_id` INT NULL DEFAULT 1 AFTER `id`, ADD INDEX IF NOT EXISTS `idx_production_units_concern` (`concern_id`);
ALTER TABLE `warehouses` ADD COLUMN IF NOT EXISTS `concern_id` INT NULL DEFAULT 1 AFTER `id`, ADD INDEX IF NOT EXISTS `idx_warehouses_concern` (`concern_id`);
ALTER TABLE `shops` ADD COLUMN IF NOT EXISTS `concern_id` INT NULL DEFAULT 1 AFTER `id`, ADD INDEX IF NOT EXISTS `idx_shops_concern` (`concern_id`);
ALTER TABLE `production_purchases` ADD COLUMN IF NOT EXISTS `concern_id` INT NULL DEFAULT 1 AFTER `id`, ADD INDEX IF NOT EXISTS `idx_production_purchases_concern` (`concern_id`);
ALTER TABLE `production_programs` ADD COLUMN IF NOT EXISTS `concern_id` INT NULL DEFAULT 1 AFTER `id`, ADD INDEX IF NOT EXISTS `idx_production_programs_concern` (`concern_id`);
ALTER TABLE `cutting_batches` ADD COLUMN IF NOT EXISTS `concern_id` INT NULL DEFAULT 1 AFTER `id`, ADD INDEX IF NOT EXISTS `idx_cutting_batches_concern` (`concern_id`);
ALTER TABLE `stitching_batches` ADD COLUMN IF NOT EXISTS `concern_id` INT NULL DEFAULT 1 AFTER `id`, ADD INDEX IF NOT EXISTS `idx_stitching_batches_concern` (`concern_id`);
ALTER TABLE `qc_batches` ADD COLUMN IF NOT EXISTS `concern_id` INT NULL DEFAULT 1 AFTER `id`, ADD INDEX IF NOT EXISTS `idx_qc_batches_concern` (`concern_id`);
ALTER TABLE `production_batches` ADD COLUMN IF NOT EXISTS `concern_id` INT NULL DEFAULT 1 AFTER `id`, ADD INDEX IF NOT EXISTS `idx_production_batches_concern` (`concern_id`);
ALTER TABLE `production_outwards` ADD COLUMN IF NOT EXISTS `concern_id` INT NULL DEFAULT 1 AFTER `id`, ADD INDEX IF NOT EXISTS `idx_production_outwards_concern` (`concern_id`);
ALTER TABLE `production_bills` ADD COLUMN IF NOT EXISTS `concern_id` INT NULL DEFAULT 1 AFTER `id`, ADD INDEX IF NOT EXISTS `idx_production_bills_concern` (`concern_id`);
ALTER TABLE `production_returns` ADD COLUMN IF NOT EXISTS `concern_id` INT NULL DEFAULT 1 AFTER `id`, ADD INDEX IF NOT EXISTS `idx_production_returns_concern` (`concern_id`);
ALTER TABLE `production_expenses` ADD COLUMN IF NOT EXISTS `concern_id` INT NULL DEFAULT 1 AFTER `id`, ADD INDEX IF NOT EXISTS `idx_production_expenses_concern` (`concern_id`);
ALTER TABLE `wastage_sales` ADD COLUMN IF NOT EXISTS `concern_id` INT NULL DEFAULT 1 AFTER `id`, ADD INDEX IF NOT EXISTS `idx_wastage_sales_concern` (`concern_id`);
ALTER TABLE `stitching_wastage_sales` ADD COLUMN IF NOT EXISTS `concern_id` INT NULL DEFAULT 1 AFTER `id`, ADD INDEX IF NOT EXISTS `idx_stitching_wastage_sales_concern` (`concern_id`);
ALTER TABLE `stock_balances` ADD COLUMN IF NOT EXISTS `concern_id` INT NULL DEFAULT 1 AFTER `id`, ADD INDEX IF NOT EXISTS `idx_stock_balances_concern` (`concern_id`);
ALTER TABLE `stock_transactions` ADD COLUMN IF NOT EXISTS `concern_id` INT NULL DEFAULT 1 AFTER `id`, ADD INDEX IF NOT EXISTS `idx_stock_transactions_concern` (`concern_id`);

-- 5. Default Concern Seed
INSERT IGNORE INTO `concerns` (`id`, `name`, `code`, `legal_name`, `invoice_prefix`, `po_prefix`, `dispatch_prefix`, `is_active`)
VALUES (1, 'GRM Apparels Unit 1', 'CN-01', 'GRM Apparels Private Limited', 'INV-', 'PO-', 'DSP-', 1);

INSERT IGNORE INTO `concern_modules` (`concern_id`, `module_key`, `is_enabled`) VALUES
(1, 'production', 1),
(1, 'purchases', 1),
(1, 'warehouse', 1),
(1, 'retail_pos', 1),
(1, 'digital_sales', 1),
(1, 'returns', 1),
(1, 'expenses', 1),
(1, 'assets', 1);

UPDATE `users` SET `default_concern_id` = 1 WHERE `default_concern_id` IS NULL;
INSERT IGNORE INTO `user_concerns` (`user_id`, `concern_id`, `is_default`) SELECT `id`, 1, 1 FROM `users`;
