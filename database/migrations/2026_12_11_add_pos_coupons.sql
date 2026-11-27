-- -------------------------------------------------------------
-- Migration: Add POS Coupons system (pos_coupons + production_bills columns)
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `pos_coupons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `concern_id` int(10) unsigned DEFAULT NULL,
  `coupon_code` varchar(40) NOT NULL,
  `issued_bill_id` int(10) unsigned NOT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_name` varchar(150) DEFAULT NULL,
  `discount_type` enum('FIXED','PERCENT') NOT NULL DEFAULT 'FIXED',
  `discount_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `min_redemption_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('ACTIVE','REDEEMED','EXPIRED','CANCELLED') NOT NULL DEFAULT 'ACTIVE',
  `redeemed_bill_id` int(10) unsigned DEFAULT NULL,
  `redeemed_at` datetime DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_pos_coupons_code` (`coupon_code`),
  KEY `idx_pos_coupons_issued_bill` (`issued_bill_id`),
  KEY `idx_pos_coupons_redeemed_bill` (`redeemed_bill_id`),
  KEY `idx_pos_coupons_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_bills' AND COLUMN_NAME = 'applied_coupon_id'),
  'SELECT 1',
  'ALTER TABLE `production_bills` ADD COLUMN `applied_coupon_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_bills' AND COLUMN_NAME = 'applied_coupon_code'),
  'SELECT 1',
  'ALTER TABLE `production_bills` ADD COLUMN `applied_coupon_code` varchar(40) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_bills' AND COLUMN_NAME = 'coupon_discount_amount'),
  'SELECT 1',
  'ALTER TABLE `production_bills` ADD COLUMN `coupon_discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
