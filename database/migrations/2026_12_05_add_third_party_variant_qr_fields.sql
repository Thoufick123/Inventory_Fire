-- Add QR linkage fields used by third-party product variants.
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'third_party_product_variants' AND COLUMN_NAME = 'qr_generation_id'),
  'SELECT 1',
  'ALTER TABLE `third_party_product_variants` ADD COLUMN `qr_generation_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'third_party_product_variants' AND COLUMN_NAME = 'qr_batch_code'),
  'SELECT 1',
  'ALTER TABLE `third_party_product_variants` ADD COLUMN `qr_batch_code` varchar(100) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'third_party_product_variants' AND INDEX_NAME = 'idx_tppv_qr_generation_id'),
  'SELECT 1',
  'ALTER TABLE `third_party_product_variants` ADD INDEX `idx_tppv_qr_generation_id` (`qr_generation_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
