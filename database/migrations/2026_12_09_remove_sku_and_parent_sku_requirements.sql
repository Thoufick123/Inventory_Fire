-- Remove NOT NULL / UNIQUE constraints on SKU and Parent SKU (products.code)
-- while preserving variant_code (e.g. V0001) and qr_code intact.

ALTER TABLE `products`
    MODIFY COLUMN `code` VARCHAR(50) DEFAULT NULL;

ALTER TABLE `product_variants`
    MODIFY COLUMN `sku` VARCHAR(100) DEFAULT NULL;

-- Drop unique constraint on product_variants.sku if present
SET @idx_exists := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variants' AND INDEX_NAME = 'sku'
);
SET @sql_drop_idx := IF(@idx_exists > 0, 'ALTER TABLE `product_variants` DROP INDEX `sku`', 'SELECT 1');
PREPARE stmt FROM @sql_drop_idx;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE `product_price_logs`
    MODIFY COLUMN `sku` VARCHAR(100) DEFAULT NULL;

ALTER TABLE `third_party_product_variants`
    MODIFY COLUMN `sku` VARCHAR(100) DEFAULT NULL;
