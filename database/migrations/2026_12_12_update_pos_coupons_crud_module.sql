-- -------------------------------------------------------------
-- Migration: Update pos_coupons for standalone Coupons CRUD module
-- -------------------------------------------------------------

ALTER TABLE `pos_coupons`
  MODIFY COLUMN `issued_bill_id` int(10) unsigned DEFAULT NULL,
  MODIFY COLUMN `status` enum('ACTIVE','INACTIVE','REDEEMED','EXPIRED','CANCELLED') NOT NULL DEFAULT 'ACTIVE';

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pos_coupons' AND COLUMN_NAME = 'title'),
  'SELECT 1',
  'ALTER TABLE `pos_coupons` ADD COLUMN `title` varchar(150) DEFAULT NULL AFTER `coupon_code`'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pos_coupons' AND COLUMN_NAME = 'max_uses'),
  'SELECT 1',
  'ALTER TABLE `pos_coupons` ADD COLUMN `max_uses` int(11) NOT NULL DEFAULT 0 AFTER `min_redemption_amount`'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pos_coupons' AND COLUMN_NAME = 'used_count'),
  'SELECT 1',
  'ALTER TABLE `pos_coupons` ADD COLUMN `used_count` int(11) NOT NULL DEFAULT 0 AFTER `max_uses`'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pos_coupons' AND COLUMN_NAME = 'created_by'),
  'SELECT 1',
  'ALTER TABLE `pos_coupons` ADD COLUMN `created_by` int(10) unsigned DEFAULT NULL AFTER `expires_at`'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
