-- ============================================================================
-- LIVE DATABASE UPDATE MIGRATION SCRIPT
-- Safe for existing live databases: Uses IF NOT EXISTS / checks before creating
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Ensure concern_id column on core production & purchase tables
ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `variants_deferred` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `concern_id` INT UNSIGNED NULL AFTER `id`;

ALTER TABLE `product_variants` ADD COLUMN IF NOT EXISTS `variant_code` VARCHAR(50) NULL AFTER `id`;
ALTER TABLE `product_variants` ADD COLUMN IF NOT EXISTS `qr_code` VARCHAR(100) NULL AFTER `sku`;
ALTER TABLE `product_variants` ADD COLUMN IF NOT EXISTS `attr_hash` VARCHAR(100) NULL AFTER `design_id`;

ALTER TABLE `seller_rolls` ADD COLUMN IF NOT EXISTS `concern_id` INT DEFAULT 1 AFTER `id`;
ALTER TABLE `seller_rolls` ADD COLUMN IF NOT EXISTS `deleted_count` INT NOT NULL DEFAULT 0;

ALTER TABLE `production_programs` ADD COLUMN IF NOT EXISTS `concern_id` INT UNSIGNED NULL AFTER `id`;
ALTER TABLE `stitching_batches` ADD COLUMN IF NOT EXISTS `concern_id` INT UNSIGNED NULL AFTER `id`;

ALTER TABLE `qc_batches` ADD COLUMN IF NOT EXISTS `concern_id` INT UNSIGNED NULL AFTER `id`;
ALTER TABLE `qc_batches` ADD COLUMN IF NOT EXISTS `stitching_batch_id` INT UNSIGNED NULL AFTER `program_id`;
ALTER TABLE `qc_batches` ADD COLUMN IF NOT EXISTS `qr_generation_id` INT UNSIGNED NULL AFTER `stitching_batch_id`;

ALTER TABLE `qc_items` ADD COLUMN IF NOT EXISTS `variant_id` INT UNSIGNED NULL AFTER `qc_batch_id`;
ALTER TABLE `qc_items` ADD COLUMN IF NOT EXISTS `category_id` INT UNSIGNED NULL AFTER `variant_id`;
ALTER TABLE `qc_items` ADD COLUMN IF NOT EXISTS `subcategory_id` INT UNSIGNED NULL AFTER `category_id`;
ALTER TABLE `qc_items` ADD COLUMN IF NOT EXISTS `sub_subcategory_id` INT UNSIGNED NULL AFTER `subcategory_id`;
ALTER TABLE `qc_items` ADD COLUMN IF NOT EXISTS `catalog_hierarchy` VARCHAR(255) NULL AFTER `sub_subcategory_id`;

ALTER TABLE `production_purchases` ADD COLUMN IF NOT EXISTS `concern_id` INT UNSIGNED NULL AFTER `id`;
ALTER TABLE `production_bills` ADD COLUMN IF NOT EXISTS `concern_id` INT UNSIGNED NULL AFTER `id`;
ALTER TABLE `production_bills` ADD COLUMN IF NOT EXISTS `production_unit_id` INT UNSIGNED NULL AFTER `concern_id`;

-- 2. New table: third_party_mapping_bills
CREATE TABLE IF NOT EXISTS `third_party_mapping_bills` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `concern_id` INT UNSIGNED NULL,
  `supplier_id` INT UNSIGNED NOT NULL,
  `invoice_number` VARCHAR(100) NOT NULL,
  `status` ENUM('DRAFT','MAPPED','SENT_TO_PURCHASE') NOT NULL DEFAULT 'DRAFT',
  `invoice_date` DATE NULL,
  `notes` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tpmb_supplier` (`supplier_id`),
  KEY `idx_tpmb_concern` (`concern_id`),
  KEY `idx_tpmb_invoice` (`invoice_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. New table: third_party_mapping_items
CREATE TABLE IF NOT EXISTS `third_party_mapping_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `mapping_bill_id` INT UNSIGNED NOT NULL,
  `concern_id` INT UNSIGNED NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `style_code` VARCHAR(100) NULL,
  `unit` VARCHAR(50) NULL DEFAULT 'PCS',
  `size` VARCHAR(100) NULL,
  `color` VARCHAR(100) NULL,
  `sku` VARCHAR(100) NULL,
  `quantity` DECIMAL(12,2) NOT NULL DEFAULT 1.00,
  `purchase_rate` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `mrp` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `selling_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `gst_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `hsn_code` VARCHAR(50) NULL,
  `category_id` INT UNSIGNED NULL,
  `subcategory_id` INT UNSIGNED NULL,
  `sub_subcategory_id` INT UNSIGNED NULL,
  `is_roll` TINYINT(1) NOT NULL DEFAULT 0,
  `roll_color` VARCHAR(100) NULL,
  `roll_pattern` VARCHAR(100) NULL,
  `roll_meters` DECIMAL(12,2) NULL DEFAULT 0.00,
  `roll_weight` DECIMAL(12,2) NULL DEFAULT 0.00,
  `seller_roll_id` INT UNSIGNED NULL,
  `product_variant_id` INT UNSIGNED NULL,
  `qr_batch_code` VARCHAR(100) NULL,
  `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tpmi_bill` (`mapping_bill_id`),
  KEY `idx_tpmi_concern` (`concern_id`),
  KEY `idx_tpmi_variant` (`product_variant_id`),
  KEY `idx_tpmi_roll` (`seller_roll_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. New table: production_deleted_label_codes
CREATE TABLE IF NOT EXISTS `production_deleted_label_codes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(100) NOT NULL,
  `deleted_by` INT UNSIGNED NULL,
  `deleted_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `reason` VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_pdlc_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. New table: seller_roll_deleted_labels
CREATE TABLE IF NOT EXISTS `seller_roll_deleted_labels` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `seller_roll_id` INT UNSIGNED NOT NULL,
  `qr_code` VARCHAR(100) NOT NULL,
  `sequence_num` INT NOT NULL DEFAULT 1,
  `reason` VARCHAR(255) NULL,
  `deleted_by` INT UNSIGNED NULL,
  `deleted_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_srdl_roll` (`seller_roll_id`),
  KEY `idx_srdl_code` (`qr_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. New table: production_hsn_config
CREATE TABLE IF NOT EXISTS `production_hsn_config` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `concern_id` INT UNSIGNED NULL,
  `hsn_code` VARCHAR(50) NOT NULL,
  `description` VARCHAR(255) NULL,
  `gst_percentage` DECIMAL(5,2) NOT NULL DEFAULT 5.00,
  `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_phc_hsn` (`hsn_code`),
  KEY `idx_phc_concern` (`concern_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. New table: production_bills
CREATE TABLE IF NOT EXISTS `production_bills` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `concern_id` INT UNSIGNED NULL,
  `production_unit_id` INT UNSIGNED NULL,
  `bill_number` VARCHAR(50) NOT NULL,
  `bill_date` DATE NOT NULL,
  `buyer_type` ENUM('INDIVIDUAL','COMPANY') NOT NULL DEFAULT 'COMPANY',
  `company_name` VARCHAR(255) NULL,
  `company_address` TEXT NULL,
  `company_phone` VARCHAR(50) NULL,
  `company_email` VARCHAR(100) NULL,
  `company_gstin` VARCHAR(50) NULL,
  `customer_name` VARCHAR(255) NULL,
  `customer_phone` VARCHAR(50) NULL,
  `customer_address` TEXT NULL,
  `customer_city` VARCHAR(100) NULL,
  `customer_state` VARCHAR(100) NULL,
  `customer_gstin` VARCHAR(50) NULL,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `tax_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `grand_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('CASH','UPI','BANK_TRANSFER','CHEQUE','CREDIT') NOT NULL DEFAULT 'CASH',
  `payment_status` ENUM('PAID','PENDING','PARTIAL') NOT NULL DEFAULT 'PAID',
  `notes` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_pb_number` (`bill_number`),
  KEY `idx_pb_concern` (`concern_id`),
  KEY `idx_pb_unit` (`production_unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. New table: production_bill_items
CREATE TABLE IF NOT EXISTS `production_bill_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bill_id` INT UNSIGNED NOT NULL,
  `qc_item_id` INT UNSIGNED NOT NULL,
  `variant_id` INT UNSIGNED NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `sku` VARCHAR(100) NULL,
  `hsn_code` VARCHAR(20) NULL,
  `size_name` VARCHAR(100) NULL,
  `color_name` VARCHAR(100) NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `mrp` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `unit_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `is_complementary` TINYINT(1) NOT NULL DEFAULT 0,
  `discount_percentage` DECIMAL(7,3) NOT NULL DEFAULT 0.000,
  `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `gst_percentage` DECIMAL(7,3) NOT NULL DEFAULT 0.000,
  `gst_type` ENUM('SGST','IGST','CGST','BOTH','EXEMPTED') NOT NULL DEFAULT 'BOTH',
  `tax_input_type` ENUM('PERCENT','FIXED') NOT NULL DEFAULT 'PERCENT',
  `sgst_rate` DECIMAL(7,3) NOT NULL DEFAULT 0.000,
  `cgst_rate` DECIMAL(7,3) NOT NULL DEFAULT 0.000,
  `igst_rate` DECIMAL(7,3) NOT NULL DEFAULT 0.000,
  `taxable_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `cgst_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `sgst_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `igst_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pbi_bill` (`bill_id`),
  KEY `idx_pbi_variant` (`variant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. New table: production_bill_item_tags
CREATE TABLE IF NOT EXISTS `production_bill_item_tags` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bill_id` INT UNSIGNED NOT NULL,
  `bill_item_id` INT UNSIGNED NOT NULL,
  `variant_id` INT UNSIGNED NULL,
  `tag_code` VARCHAR(100) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pbit_bill` (`bill_id`),
  KEY `idx_pbit_item` (`bill_item_id`),
  KEY `idx_pbit_tag` (`tag_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Self-heal any existing product_variants missing qr_code
UPDATE `product_variants` 
SET `qr_code` = COALESCE(NULLIF(`variant_code`, ''), CONCAT('V', LPAD(`id`, 4, '0')))
WHERE `qr_code` IS NULL OR `qr_code` = '';

SET FOREIGN_KEY_CHECKS = 1;
