-- -------------------------------------------------------------
-- Migration: Add COD payment status, paid_amount, remaining_amount to production_bills
-- -------------------------------------------------------------

ALTER TABLE `production_bills`
  MODIFY COLUMN `payment_status` ENUM('PENDING','PAID','COD') NOT NULL DEFAULT 'PAID';

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_bills' AND COLUMN_NAME = 'paid_amount'),
  'SELECT 1',
  'ALTER TABLE `production_bills` ADD COLUMN `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `payment_status`'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_bills' AND COLUMN_NAME = 'remaining_amount'),
  'SELECT 1',
  'ALTER TABLE `production_bills` ADD COLUMN `remaining_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `paid_amount`'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

UPDATE `production_bills`
SET `paid_amount` = CASE WHEN `payment_status` = 'PAID' THEN `total_amount` ELSE 0.00 END,
    `remaining_amount` = CASE WHEN `payment_status` = 'PAID' THEN 0.00 ELSE `total_amount` END
WHERE `paid_amount` = 0.00 AND `remaining_amount` = 0.00 AND `total_amount` > 0;
