-- Migration: Add 3 Purchase Types and Third-Party Product Mapping System
-- Date: 2026-11-17

-- 1. Add purchase_type to production_purchases
ALTER TABLE `production_purchases`
  ADD COLUMN `purchase_type` ENUM('FABRIC_ROLL', 'THIRD_PARTY', 'OWN_PRODUCT') NOT NULL DEFAULT 'FABRIC_ROLL' AFTER `purchase_number`,
  ADD KEY `idx_purchases_type_date` (`purchase_type`, `purchase_date`, `seller_id`);

-- 2. Enhance production_purchase_items with item types, sizes, MRP, and mapping columns
ALTER TABLE `production_purchase_items`
  MODIFY COLUMN `item_type` ENUM('FABRIC_ROLL', 'THIRD_PARTY', 'OWN_PRODUCT') NOT NULL DEFAULT 'FABRIC_ROLL',
  ADD COLUMN `size` varchar(50) DEFAULT NULL AFTER `color`,
  ADD COLUMN `mrp` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `unit_price`,
  ADD COLUMN `selling_price` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `mrp`,
  ADD COLUMN `vendor_sku` varchar(100) DEFAULT NULL AFTER `product_code`,
  ADD COLUMN `is_mapped` tinyint(1) NOT NULL DEFAULT 0 AFTER `is_inwarded`,
  ADD COLUMN `mapped_category_id` int(10) unsigned DEFAULT NULL AFTER `is_mapped`,
  ADD COLUMN `mapped_subcategory_id` int(10) unsigned DEFAULT NULL AFTER `mapped_category_id`,
  ADD COLUMN `mapped_sub_subcategory_id` int(10) unsigned DEFAULT NULL AFTER `mapped_subcategory_id`,
  ADD COLUMN `mapped_attributes` text DEFAULT NULL AFTER `mapped_sub_subcategory_id`,
  ADD COLUMN `mapped_at` datetime DEFAULT NULL AFTER `mapped_attributes`,
  ADD COLUMN `mapped_by` int(10) unsigned DEFAULT NULL AFTER `mapped_at`,
  ADD KEY `idx_purch_items_type_mapped` (`item_type`, `is_mapped`, `created_at`),
  ADD KEY `idx_purch_items_prod_var` (`product_id`, `variant_id`),
  ADD KEY `idx_purch_items_mapped_cat` (`mapped_category_id`, `mapped_subcategory_id`);

-- 3. Composite Covering Index on production_purchases for index-only scans
ALTER TABLE `production_purchases`
  ADD KEY `idx_purchases_covering_list` (`id`, `purchase_type`, `seller_id`, `purchase_date`, `total_amount`);
