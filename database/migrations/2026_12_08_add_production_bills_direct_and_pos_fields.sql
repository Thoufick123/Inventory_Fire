-- -------------------------------------------------------------
-- Migration: Add is_direct_bill and POS fields to production_bills
-- -------------------------------------------------------------

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_bills' AND COLUMN_NAME = 'is_direct_bill'),
  'SELECT 1',
  'ALTER TABLE `production_bills` ADD COLUMN `is_direct_bill` tinyint(1) NOT NULL DEFAULT 0'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_bills' AND COLUMN_NAME = 'upi_payment_screenshot_url'),
  'SELECT 1',
  'ALTER TABLE `production_bills` ADD COLUMN `upi_payment_screenshot_url` varchar(500) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_bills' AND COLUMN_NAME = 'cash_given'),
  'SELECT 1',
  'ALTER TABLE `production_bills` ADD COLUMN `cash_given` decimal(12,2) DEFAULT 0.00'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_bills' AND COLUMN_NAME = 'change_returned'),
  'SELECT 1',
  'ALTER TABLE `production_bills` ADD COLUMN `change_returned` decimal(12,2) DEFAULT 0.00'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_bills' AND COLUMN_NAME = 'staff_id'),
  'SELECT 1',
  'ALTER TABLE `production_bills` ADD COLUMN `staff_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_bills' AND COLUMN_NAME = 'staff_name'),
  'SELECT 1',
  'ALTER TABLE `production_bills` ADD COLUMN `staff_name` varchar(150) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_bills' AND INDEX_NAME = 'idx_production_bills_direct'),
  'SELECT 1',
  'ALTER TABLE `production_bills` ADD INDEX `idx_production_bills_direct` (`is_direct_bill`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
