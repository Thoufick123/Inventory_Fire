-- Live Schema Sync Migration (2026-11-25)
-- Adds columns required by Purchase Mapping Fulfillment, Accounts Verification,
-- Extra/Original Purchase Item Tracking, and Staff Sidebar Ordering.
-- Safe & idempotent for both MySQL and MariaDB.

SET @db_name = DATABASE();

-- 1. production_purchase_items.original_quantity
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'original_quantity'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `production_purchase_items` ADD COLUMN `original_quantity` decimal(12,3) DEFAULT NULL AFTER `quantity`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. production_purchase_items.extra_added_qty
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'extra_added_qty'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `production_purchase_items` ADD COLUMN `extra_added_qty` decimal(12,3) NOT NULL DEFAULT 0.000 AFTER `original_quantity`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. production_purchase_items.is_extra_purchase_item
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'is_extra_purchase_item'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `production_purchase_items` ADD COLUMN `is_extra_purchase_item` tinyint(1) NOT NULL DEFAULT 0 AFTER `is_complementary`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. production_purchases.accounts_status
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'production_purchases' AND COLUMN_NAME = 'accounts_status'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `production_purchases` ADD COLUMN `accounts_status` varchar(30) NOT NULL DEFAULT ''VERIFIED'' AFTER `concern_id`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. production_purchases.accounts_verified_by
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'production_purchases' AND COLUMN_NAME = 'accounts_verified_by'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `production_purchases` ADD COLUMN `accounts_verified_by` int(10) unsigned DEFAULT NULL AFTER `accounts_status`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 6. production_purchases.accounts_verified_at
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'production_purchases' AND COLUMN_NAME = 'accounts_verified_at'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `production_purchases` ADD COLUMN `accounts_verified_at` datetime DEFAULT NULL AFTER `accounts_verified_by`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7. users.sidebar_order
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'users' AND COLUMN_NAME = 'sidebar_order'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `users` ADD COLUMN `sidebar_order` text DEFAULT NULL AFTER `avatar`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
