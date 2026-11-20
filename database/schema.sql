-- ============================================================
-- GRM INVENTORY SOFTWARE — COMPLETE MASTER DATABASE SCHEMA
-- Generated: 2026-11-19 15:08:56
-- Total Tables: 89
-- Includes: Multi-Concern Architecture, Staff & Scoping Permissions,
--           Production Programs, Cutting & Stitching QR Generations,
--           Quality Control (QC), Finished Goods & Purchased Stock,
--           Third-Party Taxonomy & Catalog, Warehouse, POS/Billing
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+05:30';

-- ------------------------------------------------------------
-- [1/89] Table structure for `allocation_outwards`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `allocation_outwards`;
CREATE TABLE `allocation_outwards` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `outward_number` varchar(50) NOT NULL,
  `allocation_id` int(10) unsigned NOT NULL,
  `warehouse_id` int(10) unsigned NOT NULL,
  `destination_type` varchar(50) NOT NULL,
  `destination_id` int(10) unsigned DEFAULT 0,
  `shop_id` int(10) unsigned DEFAULT NULL,
  `channel_id` int(10) unsigned DEFAULT NULL,
  `total_boxes` int(11) DEFAULT 1,
  `total_quantity` int(11) DEFAULT 0,
  `vehicle_number` varchar(100) DEFAULT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  `driver_contact` varchar(20) DEFAULT NULL,
  `transporter_name` varchar(100) DEFAULT NULL,
  `lr_number` varchar(100) DEFAULT NULL,
  `dispatched_by` int(10) unsigned DEFAULT NULL,
  `dispatch_date` datetime DEFAULT current_timestamp(),
  `status` enum('DISPATCHED','DELIVERED','CANCELLED') DEFAULT 'DISPATCHED',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_pieces` int(11) NOT NULL DEFAULT 0,
  `transport_mode` varchar(50) DEFAULT NULL,
  `dispatched_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `outward_number` (`outward_number`),
  KEY `idx_outward_alloc` (`allocation_id`),
  KEY `idx_outward_wh` (`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [2/89] Table structure for `asset_categories`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `asset_categories`;
CREATE TABLE `asset_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `code` varchar(50) NOT NULL,
  `icon` varchar(100) NOT NULL DEFAULT 'bi-box-seam',
  `description` text DEFAULT NULL,
  `depreciation_rate_annual` decimal(5,2) NOT NULL DEFAULT 15.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_code` (`code`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [3/89] Table structure for `asset_maintenance_logs`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `asset_maintenance_logs`;
CREATE TABLE `asset_maintenance_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `log_number` varchar(50) NOT NULL,
  `asset_id` int(10) unsigned NOT NULL,
  `service_type` enum('ROUTINE_PREVENTIVE','BREAKDOWN_REPAIR','CALIBRATION','PART_REPLACEMENT','ANNUAL_AMC') NOT NULL DEFAULT 'ROUTINE_PREVENTIVE',
  `issue_description` text DEFAULT NULL,
  `action_taken` text DEFAULT NULL,
  `serviced_by_vendor` varchar(150) DEFAULT NULL,
  `technician_name` varchar(100) DEFAULT NULL,
  `technician_contact` varchar(50) DEFAULT NULL,
  `service_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `parts_replaced` text DEFAULT NULL,
  `service_date` date NOT NULL,
  `next_due_date` date DEFAULT NULL,
  `downtime_hours` decimal(5,1) NOT NULL DEFAULT 0.0,
  `status` enum('PENDING','IN_PROGRESS','COMPLETED') NOT NULL DEFAULT 'COMPLETED',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `log_number` (`log_number`),
  KEY `idx_asset` (`asset_id`),
  KEY `idx_service_type` (`service_type`),
  KEY `idx_service_date` (`service_date`),
  KEY `idx_next_due` (`next_due_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [4/89] Table structure for `assets`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `assets`;
CREATE TABLE `assets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `asset_code` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `location_type` enum('PRODUCTION','WAREHOUSE','SHOP','OFFICE') NOT NULL DEFAULT 'PRODUCTION',
  `location_id` int(10) unsigned NOT NULL DEFAULT 1,
  `floor_area` varchar(150) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model_number` varchar(100) DEFAULT NULL,
  `serial_number` varchar(150) DEFAULT NULL,
  `barcode_qr` varchar(100) NOT NULL,
  `specifications` text DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `vendor_name` varchar(150) DEFAULT NULL,
  `vendor_contact` varchar(100) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `useful_life_years` int(11) NOT NULL DEFAULT 5,
  `salvage_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `current_book_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `last_depreciation_date` date DEFAULT NULL,
  `status` enum('IN_USE','IDLE','IN_MAINTENANCE','DAMAGED','DISPOSED','WRITTEN_OFF') NOT NULL DEFAULT 'IN_USE',
  `condition_state` enum('EXCELLENT','GOOD','FAIR','POOR','CRITICAL') NOT NULL DEFAULT 'GOOD',
  `custodian_id` int(10) unsigned DEFAULT NULL,
  `custodian_name` varchar(150) DEFAULT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `invoice_file_url` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_code` (`asset_code`),
  UNIQUE KEY `barcode_qr` (`barcode_qr`),
  KEY `idx_category` (`category_id`),
  KEY `idx_location` (`location_type`,`location_id`),
  KEY `idx_status` (`status`),
  KEY `idx_condition` (`condition_state`),
  KEY `idx_qr` (`barcode_qr`),
  KEY `idx_code` (`asset_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [5/89] Table structure for `audit_logs`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `module` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(10) unsigned DEFAULT NULL,
  `previous_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `new_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `record_id` int(10) unsigned DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_audit_module` (`module`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_reference` (`reference_type`,`reference_id`),
  KEY `idx_audit_date` (`created_at`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `audit_logs_chk_1` CHECK (json_valid(`previous_value`)),
  CONSTRAINT `audit_logs_chk_2` CHECK (json_valid(`new_value`))
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [6/89] Table structure for `brands`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `brands`;
CREATE TABLE `brands` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `logo_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `code` varchar(20) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [7/89] Table structure for `categories`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `hsn_code` varchar(20) DEFAULT NULL,
  `gst_rate` decimal(5,2) DEFAULT 5.00,
  `image_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [8/89] Table structure for `colors`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `colors`;
CREATE TABLE `colors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `hex_code` varchar(7) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [9/89] Table structure for `concern_modules`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `concern_modules`;
CREATE TABLE `concern_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `concern_id` int(11) NOT NULL,
  `module_key` varchar(50) NOT NULL,
  `is_enabled` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_concern_module` (`concern_id`,`module_key`),
  CONSTRAINT `fk_concern_module_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [10/89] Table structure for `concerns`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `concerns`;
CREATE TABLE `concerns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `code` varchar(50) NOT NULL,
  `concern_type` enum('HEAD_OFFICE','PRODUCTION','WAREHOUSE','RETAIL') NOT NULL DEFAULT 'HEAD_OFFICE',
  `legal_name` varchar(255) DEFAULT NULL,
  `gst_number` varchar(15) DEFAULT NULL,
  `pan_number` varchar(10) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `invoice_prefix` varchar(20) DEFAULT 'INV',
  `po_prefix` varchar(20) DEFAULT 'PO',
  `dispatch_prefix` varchar(20) DEFAULT 'DSP',
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_concerns_code` (`code`),
  KEY `idx_concerns_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [11/89] Table structure for `cutting_batch_rolls`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `cutting_batch_rolls`;
CREATE TABLE `cutting_batch_rolls` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cutting_batch_id` int(10) unsigned NOT NULL,
  `seller_roll_id` int(10) unsigned NOT NULL,
  `seller_id` int(10) unsigned DEFAULT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `subcategory_id` int(10) unsigned DEFAULT NULL,
  `sub_subcategory_id` int(10) unsigned DEFAULT NULL,
  `roll_weight` decimal(10,2) NOT NULL DEFAULT 0.00,
  `roll_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `wastage_weight` decimal(10,2) NOT NULL DEFAULT 0.00,
  `wastage_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `roll_color` varchar(100) DEFAULT NULL,
  `roll_design` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `used_weight` decimal(10,2) DEFAULT 0.00,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cbr_batch` (`cutting_batch_id`),
  KEY `idx_cbr_roll` (`seller_roll_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [12/89] Table structure for `cutting_batches`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `cutting_batches`;
CREATE TABLE `cutting_batches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `partition_id` int(10) unsigned DEFAULT 1,
  `program_id` int(10) unsigned NOT NULL,
  `seller_roll_id` int(10) unsigned DEFAULT NULL,
  `roll_weight` decimal(10,2) DEFAULT 0.00,
  `roll_price` decimal(12,2) DEFAULT 0.00,
  `wastage_weight` decimal(10,2) DEFAULT 0.00,
  `wastage_cost` decimal(12,2) DEFAULT 0.00,
  `used_weight` decimal(10,2) DEFAULT 0.00,
  `cost_per_piece` decimal(12,2) DEFAULT 0.00,
  `category_id` int(10) unsigned DEFAULT NULL,
  `subcategory_id` int(10) unsigned DEFAULT NULL,
  `sub_subcategory_id` int(10) unsigned DEFAULT NULL,
  `batch_number` varchar(50) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `operator_id` int(10) unsigned DEFAULT NULL,
  `production_unit_id` int(10) unsigned DEFAULT NULL,
  `material_issued` text DEFAULT NULL,
  `planned_qty` int(11) NOT NULL DEFAULT 0,
  `cut_qty` int(11) DEFAULT 0,
  `rejected_qty` int(11) DEFAULT 0,
  `wastage_qty` int(11) DEFAULT 0,
  `status` enum('PENDING','IN_PROGRESS','COMPLETED','HOLD','CANCELLED') DEFAULT 'PENDING',
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cutting_master` varchar(150) DEFAULT NULL,
  `operator_name` varchar(150) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `concern_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch_number` (`batch_number`),
  KEY `program_id` (`program_id`),
  KEY `operator_id` (`operator_id`),
  KEY `production_unit_id` (`production_unit_id`),
  KEY `idx_cutting_batches_created_at` (`created_at`),
  KEY `idx_cutting_batches_concern_id` (`concern_id`),
  KEY `idx_cutting_batches_concern` (`concern_id`),
  CONSTRAINT `cutting_batches_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `production_programs` (`id`),
  CONSTRAINT `cutting_batches_ibfk_2` FOREIGN KEY (`operator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cutting_batches_ibfk_3` FOREIGN KEY (`production_unit_id`) REFERENCES `production_units` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [13/89] Table structure for `cutting_items`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `cutting_items`;
CREATE TABLE `cutting_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cutting_batch_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `subcategory_id` int(10) unsigned DEFAULT NULL,
  `sub_subcategory_id` int(10) unsigned DEFAULT NULL,
  `catalog_hierarchy` varchar(255) DEFAULT NULL,
  `size_name` varchar(100) DEFAULT NULL,
  `color_name` varchar(100) DEFAULT NULL,
  `planned_qty` int(11) NOT NULL DEFAULT 0,
  `cut_qty` int(11) DEFAULT 0,
  `rejected_qty` int(11) DEFAULT 0,
  `wastage_qty` int(11) DEFAULT 0,
  `weight_per_piece` decimal(10,4) DEFAULT 0.0000,
  `weight` decimal(10,2) DEFAULT 0.00,
  `base_price` decimal(10,2) DEFAULT 0.00,
  `discount_price` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `cutting_batch_id` (`cutting_batch_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `cutting_items_ibfk_1` FOREIGN KEY (`cutting_batch_id`) REFERENCES `cutting_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cutting_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [14/89] Table structure for `designs`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `designs`;
CREATE TABLE `designs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [15/89] Table structure for `expense_categories`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `expense_categories`;
CREATE TABLE `expense_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `department_scope` varchar(50) NOT NULL DEFAULT 'ALL',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [16/89] Table structure for `hsn_codes`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `hsn_codes`;
CREATE TABLE `hsn_codes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hsn_code` varchar(20) NOT NULL,
  `description` text NOT NULL,
  `gst_rate` decimal(5,2) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_hsn_code` (`hsn_code`),
  KEY `idx_hsn_code_search` (`hsn_code`),
  KEY `idx_gst_rate` (`gst_rate`)
) ENGINE=InnoDB AUTO_INCREMENT=17466 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table structure for `product_price_logs`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `product_price_logs`;
CREATE TABLE `product_price_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `sku` varchar(100) NOT NULL,
  `old_selling_price` decimal(10,2) NOT NULL,
  `new_selling_price` decimal(10,2) NOT NULL,
  `old_mrp` decimal(10,2) NOT NULL,
  `new_mrp` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) NOT NULL,
  `markup_percentage` decimal(10,4) DEFAULT NULL,
  `discount_percentage` decimal(10,4) NOT NULL,
  `revision_notes` text NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `user_name` varchar(200) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_price_logs_variant_created` (`variant_id`,`created_at`),
  KEY `idx_price_logs_product` (`product_id`),
  KEY `idx_price_logs_user` (`user_id`),
  CONSTRAINT `product_price_logs_ibfk_1` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_price_logs_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table structure for `notifications`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(10) unsigned DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `link` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`,`is_read`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [20/89] Table structure for `permissions`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `slug` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_module_action` (`module`,`action`)
) ENGINE=InnoDB AUTO_INCREMENT=448 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [21/89] Table structure for `product_variants`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `product_variants`;
CREATE TABLE `product_variants` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `variant_code` varchar(20) DEFAULT NULL,
  `production_unit_id` int(10) unsigned DEFAULT 1,
  `product_id` int(10) unsigned NOT NULL,
  `size_id` int(10) unsigned DEFAULT NULL,
  `color_id` int(10) unsigned DEFAULT NULL,
  `design_id` int(10) unsigned DEFAULT 1,
  `attr_hash` varchar(64) DEFAULT NULL,
  `sku` varchar(100) NOT NULL,
  `hsn_code` varchar(20) DEFAULT NULL,
  `gst_rate` decimal(5,2) DEFAULT 5.00,
  `qr_code` varchar(255) DEFAULT NULL,
  `qr_type` enum('INDIVIDUAL','SERIES_BATCH') DEFAULT 'INDIVIDUAL',
  `series_start_code` varchar(100) DEFAULT NULL,
  `series_end_code` varchar(100) DEFAULT NULL,
  `pack_quantity` int(11) DEFAULT 1,
  `is_complementary` tinyint(1) NOT NULL DEFAULT 0,
  `mrp` decimal(10,2) DEFAULT 0.00,
  `purchase_price` decimal(10,2) DEFAULT 0.00,
  `production_cost` decimal(10,2) DEFAULT 0.00,
  `selling_price` decimal(10,2) DEFAULT 0.00,
  `weight` decimal(10,3) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `mfg_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `attributes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  UNIQUE KEY `qr_code` (`qr_code`),
  UNIQUE KEY `uk_variant_attr_hash` (`attr_hash`),
  UNIQUE KEY `uk_variant_code` (`variant_code`),
  KEY `idx_variant_product` (`product_id`),
  KEY `idx_variant_sku` (`sku`),
  KEY `idx_variant_qr` (`qr_code`),
  KEY `size_id` (`size_id`),
  KEY `color_id` (`color_id`),
  CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_variants_ibfk_2` FOREIGN KEY (`size_id`) REFERENCES `sizes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_variants_ibfk_3` FOREIGN KEY (`color_id`) REFERENCES `colors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [22/89] Table structure for `production_batch_items`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `production_batch_items`;
CREATE TABLE `production_batch_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `production_batch_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `piece_tags` longtext DEFAULT NULL,
  `received_qty` int(11) NOT NULL DEFAULT 0,
  `damaged_qty` int(11) NOT NULL DEFAULT 0,
  `missing_qty` int(11) NOT NULL DEFAULT 0,
  `received_qr_codes` longtext DEFAULT NULL,
  `damaged_qr_codes` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `production_batch_id` (`production_batch_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `production_batch_items_ibfk_1` FOREIGN KEY (`production_batch_id`) REFERENCES `production_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `production_batch_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [23/89] Table structure for `production_batches`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `production_batches`;
CREATE TABLE `production_batches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `partition_id` int(10) unsigned DEFAULT 1,
  `batch_number` varchar(50) NOT NULL,
  `production_bill_id` int(10) unsigned DEFAULT NULL,
  `program_id` int(10) unsigned NOT NULL,
  `qc_batch_id` int(10) unsigned DEFAULT NULL,
  `additional_qc_batch_ids` text DEFAULT NULL,
  `is_multi_qc` tinyint(1) DEFAULT 0,
  `total_qty` int(11) NOT NULL DEFAULT 0,
  `box_count` int(11) DEFAULT 0,
  `destination_warehouse_id` int(10) unsigned NOT NULL,
  `production_outward_id` int(10) unsigned DEFAULT NULL,
  `status` enum('PREPARING','APPROVED','DISPATCHED','RECEIVED') DEFAULT 'PREPARING',
  `prepared_by` int(10) unsigned DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `quantity` int(11) NOT NULL DEFAULT 0,
  `received_qty` int(11) NOT NULL DEFAULT 0,
  `damaged_qty` int(11) NOT NULL DEFAULT 0,
  `missing_qty` int(11) NOT NULL DEFAULT 0,
  `cost_per_piece` decimal(10,2) DEFAULT 0.00,
  `total_cost` decimal(12,2) DEFAULT 0.00,
  `mfg_date` date DEFAULT NULL,
  `packed_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `concern_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch_number` (`batch_number`),
  KEY `program_id` (`program_id`),
  KEY `qc_batch_id` (`qc_batch_id`),
  KEY `destination_warehouse_id` (`destination_warehouse_id`),
  KEY `prepared_by` (`prepared_by`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_production_batches_bill` (`production_bill_id`),
  KEY `idx_production_batches_packed_created` (`packed_date`,`created_at`),
  KEY `idx_production_batches_concern_id` (`concern_id`),
  KEY `idx_production_batches_concern` (`concern_id`),
  CONSTRAINT `production_batches_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `production_programs` (`id`),
  CONSTRAINT `production_batches_ibfk_2` FOREIGN KEY (`qc_batch_id`) REFERENCES `qc_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_batches_ibfk_3` FOREIGN KEY (`destination_warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `production_batches_ibfk_4` FOREIGN KEY (`prepared_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_batches_ibfk_5` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [24/89] Table structure for `production_bill_item_tags`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `production_bill_item_tags`;
CREATE TABLE `production_bill_item_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bill_id` int(10) unsigned NOT NULL,
  `bill_item_id` int(10) unsigned NOT NULL,
  `qc_item_id` int(10) unsigned NOT NULL,
  `tag_code` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_production_bill_tag` (`tag_code`),
  KEY `idx_production_bill_tags_bill` (`bill_id`),
  KEY `idx_production_bill_tags_item` (`bill_item_id`),
  KEY `idx_production_bill_tags_qc` (`qc_item_id`),
  CONSTRAINT `production_bill_tags_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `production_bills` (`id`) ON DELETE CASCADE,
  CONSTRAINT `production_bill_tags_ibfk_2` FOREIGN KEY (`bill_item_id`) REFERENCES `production_bill_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [25/89] Table structure for `production_bill_items`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `production_bill_items`;
CREATE TABLE `production_bill_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bill_id` int(10) unsigned NOT NULL,
  `qc_item_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `hsn_code` varchar(20) DEFAULT NULL,
  `size_name` varchar(100) DEFAULT NULL,
  `color_name` varchar(100) DEFAULT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `mrp` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `is_complementary` tinyint(1) NOT NULL DEFAULT 0,
  `discount_percentage` decimal(7,3) NOT NULL DEFAULT 0.000,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gst_percentage` decimal(7,3) NOT NULL DEFAULT 0.000,
  `gst_type` enum('SGST','IGST','CGST','BOTH','EXEMPTED') NOT NULL DEFAULT 'BOTH',
  `tax_input_type` enum('PERCENT','FIXED') NOT NULL DEFAULT 'PERCENT',
  `sgst_rate` decimal(7,3) NOT NULL DEFAULT 0.000,
  `cgst_rate` decimal(7,3) NOT NULL DEFAULT 0.000,
  `igst_rate` decimal(7,3) NOT NULL DEFAULT 0.000,
  `taxable_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cgst_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sgst_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `igst_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_production_bill_items_bill` (`bill_id`),
  KEY `idx_production_bill_items_qc` (`qc_item_id`),
  CONSTRAINT `production_bill_items_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `production_bills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [26/89] Table structure for `production_bills`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `production_bills`;
CREATE TABLE `production_bills` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bill_number` varchar(50) NOT NULL,
  `production_unit_id` int(10) unsigned DEFAULT NULL,
  `buyer_type` enum('COMPANY','CUSTOMER') NOT NULL DEFAULT 'COMPANY',
  `company_name` varchar(200) DEFAULT NULL,
  `company_address` text DEFAULT NULL,
  `company_phone` varchar(30) DEFAULT NULL,
  `company_email` varchar(150) DEFAULT NULL,
  `company_gstin` varchar(30) DEFAULT NULL,
  `customer_name` varchar(150) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `customer_city` varchar(100) DEFAULT NULL,
  `customer_state` varchar(100) DEFAULT NULL,
  `customer_gstin` varchar(30) DEFAULT NULL,
  `bill_date` date NOT NULL,
  `payment_method` varchar(30) NOT NULL DEFAULT 'CASH',
  `payment_status` enum('PENDING','PAID') NOT NULL DEFAULT 'PAID',
  `tax_type` enum('CGST_SGST','IGST') NOT NULL DEFAULT 'CGST_SGST',
  `cash_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `upi_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `upi_reference` varchar(150) DEFAULT NULL,
  `gross_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cgst_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sgst_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `igst_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `concern_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_production_bills_number` (`bill_number`),
  KEY `idx_production_bills_date` (`bill_date`),
  KEY `idx_production_bills_company` (`company_name`),
  KEY `idx_production_bills_customer_phone` (`customer_phone`),
  KEY `production_bills_ibfk_1` (`created_by`),
  KEY `idx_production_bills_unit` (`production_unit_id`),
  KEY `idx_production_bills_concern_id` (`concern_id`),
  KEY `idx_production_bills_concern` (`concern_id`),
  CONSTRAINT `production_bills_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [27/89] Table structure for `production_category_attributes`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `production_category_attributes`;
CREATE TABLE `production_category_attributes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `subcategory_id` int(10) unsigned DEFAULT NULL,
  `sub_subcategory_id` int(10) unsigned DEFAULT NULL,
  `attr_name` varchar(100) NOT NULL,
  `attr_type` enum('TEXT','NUMBER','SELECT','MULTI_SELECT') NOT NULL DEFAULT 'TEXT',
  `attr_options` text DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_production_cat_attrs` (`category_id`,`subcategory_id`,`sub_subcategory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- [28/89] Table structure for `production_deleted_label_codes`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `production_deleted_label_codes`;
CREATE TABLE `production_deleted_label_codes` (
  `code` varchar(50) NOT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [29/89] Table structure for `production_expense_items`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `production_expense_items`;
CREATE TABLE `production_expense_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'GENERAL',
  `unit_type` varchar(50) NOT NULL DEFAULT 'PCS',
  `default_rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_production_expense_items_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [30/89] Table structure for `production_expenses`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `production_expenses`;
CREATE TABLE `production_expenses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `expense_number` varchar(50) NOT NULL,
  `production_unit` varchar(100) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `item_type` varchar(100) DEFAULT NULL,
  `asset_tag_or_serial` varchar(100) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_type` varchar(50) NOT NULL DEFAULT 'PCS',
  `unit_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('PENDING','PARTIAL','PAID','CANCELLED') NOT NULL DEFAULT 'PAID',
  `payment_method` varchar(50) NOT NULL DEFAULT 'CASH',
  `payment_reference` varchar(100) DEFAULT NULL,
  `vendor_name` varchar(200) NOT NULL,
  `vendor_phone` varchar(20) DEFAULT NULL,
  `vendor_company` varchar(200) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `expense_date` date NOT NULL,
  `program_id` int(10) unsigned DEFAULT NULL,
  `photo_url` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `concern_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_production_expenses_number` (`expense_number`),
  KEY `idx_production_expenses_program` (`program_id`),
  KEY `idx_production_expenses_date` (`expense_date`),
  KEY `idx_production_expenses_concern_id` (`concern_id`),
  KEY `idx_production_expenses_concern` (`concern_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [31/89] Table structure for `production_outward_batches`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `production_outward_batches`;
CREATE TABLE `production_outward_batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `production_outward_id` int(10) unsigned NOT NULL,
  `production_batch_id` int(10) unsigned NOT NULL,
  `total_qty` int(11) NOT NULL DEFAULT 0,
  `box_count` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pob_outward` (`production_outward_id`),
  KEY `idx_pob_batch` (`production_batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- [32/89] Table structure for `production_outwards`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `production_outwards`;
CREATE TABLE `production_outwards` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `partition_id` int(10) unsigned DEFAULT 1,
  `production_batch_id` int(10) unsigned NOT NULL,
  `batch_ids` text DEFAULT NULL,
  `is_multi_batch` tinyint(1) DEFAULT 0,
  `production_unit_id` int(10) unsigned DEFAULT NULL,
  `warehouse_id` int(10) unsigned DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `total_boxes` int(11) DEFAULT 0,
  `total_quantity` int(11) DEFAULT 0,
  `outward_number` varchar(50) NOT NULL,
  `dispatch_date` datetime DEFAULT NULL,
  `received_date` datetime DEFAULT NULL,
  `status` enum('DISPATCHED','RECEIVED','DISCREPANCY') DEFAULT 'DISPATCHED',
  `vehicle_number` varchar(50) DEFAULT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  `transporter_name` varchar(100) DEFAULT NULL,
  `lr_number` varchar(100) DEFAULT NULL,
  `dispatched_by` int(10) unsigned DEFAULT NULL,
  `received_by` int(10) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `driver_contact` varchar(20) DEFAULT NULL,
  `concern_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `outward_number` (`outward_number`),
  KEY `production_batch_id` (`production_batch_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_production_outwards_dispatch_created` (`dispatch_date`,`created_at`),
  KEY `idx_production_outwards_concern_id` (`concern_id`),
  KEY `idx_production_outwards_concern` (`concern_id`),
  CONSTRAINT `production_outwards_ibfk_1` FOREIGN KEY (`production_batch_id`) REFERENCES `production_batches` (`id`),
  CONSTRAINT `production_outwards_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [33/89] Table structure for `production_program_items`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `production_program_items`;
CREATE TABLE `production_program_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned NOT NULL,
  `planned_quantity` int(11) NOT NULL,
  `completed_quantity` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `produced_quantity` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `program_id` (`program_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `production_program_items_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `production_programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `production_program_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [34/89] Table structure for `production_program_rolls`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `production_program_rolls`;
CREATE TABLE `production_program_rolls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `production_program_id` int(11) NOT NULL,
  `program_id` int(10) unsigned DEFAULT NULL,
  `seller_roll_id` int(11) NOT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `subcategory_id` int(10) unsigned DEFAULT NULL,
  `sub_subcategory_id` int(10) unsigned DEFAULT NULL,
  `roll_qty` int(11) DEFAULT 0,
  `roll_weight` decimal(10,2) DEFAULT 0.00,
  `roll_price` decimal(12,2) DEFAULT 0.00,
  `roll_color` varchar(100) DEFAULT NULL,
  `roll_design` varchar(100) DEFAULT NULL,
  `allocated_qr_codes` text DEFAULT NULL,
  `attributes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `color` varchar(100) DEFAULT NULL,
  `design_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ppr_program` (`production_program_id`),
  KEY `idx_ppr_roll` (`seller_roll_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- [35/89] Table structure for `production_programs`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `production_programs`;
CREATE TABLE `production_programs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `partition_id` int(10) unsigned DEFAULT 1,
  `program_number` varchar(50) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `person_name` varchar(150) DEFAULT NULL,
  `production_unit_id` int(10) unsigned DEFAULT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `seller_roll_id` int(10) unsigned DEFAULT NULL,
  `seller_id` int(10) unsigned DEFAULT NULL,
  `roll_weight` decimal(10,2) DEFAULT 0.00,
  `roll_price` decimal(12,2) DEFAULT 0.00,
  `category_id` int(10) unsigned DEFAULT NULL,
  `subcategory_id` int(10) unsigned DEFAULT NULL,
  `sub_subcategory_id` int(10) unsigned DEFAULT NULL,
  `planned_quantity` int(11) DEFAULT 0,
  `completed_quantity` int(11) DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `priority` enum('LOW','MEDIUM','HIGH','URGENT') DEFAULT 'MEDIUM',
  `assigned_to` int(10) unsigned DEFAULT NULL,
  `status` enum('DRAFT','APPROVED','SCHEDULED','IN_PROGRESS','COMPLETED','HOLD','CANCELLED') DEFAULT 'DRAFT',
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `produced_quantity` int(11) DEFAULT 0,
  `target_completion_date` date DEFAULT NULL,
  `actual_completion_date` date DEFAULT NULL,
  `concern_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `program_number` (`program_number`),
  KEY `idx_program_unit` (`production_unit_id`),
  KEY `idx_program_status` (`status`),
  KEY `idx_program_date` (`target_date`),
  KEY `product_id` (`product_id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `approved_by` (`approved_by`),
  KEY `created_by` (`created_by`),
  KEY `idx_production_programs_created_at` (`created_at`),
  KEY `idx_production_programs_concern_id` (`concern_id`),
  KEY `idx_production_programs_concern` (`concern_id`),
  CONSTRAINT `production_programs_ibfk_1` FOREIGN KEY (`production_unit_id`) REFERENCES `production_units` (`id`),
  CONSTRAINT `production_programs_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `production_programs_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_programs_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_programs_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [36/89] Table structure for `production_purchase_items`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `production_purchase_items`;
CREATE TABLE `production_purchase_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` int(10) unsigned NOT NULL,
  `item_type` enum('FABRIC_ROLL','RAW_MATERIAL','THIRD_PARTY','OWN_PRODUCT') NOT NULL DEFAULT 'FABRIC_ROLL',
  `item_name` varchar(255) NOT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `third_party_product_id` int(10) unsigned DEFAULT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `product_code` varchar(100) DEFAULT NULL,
  `vendor_sku` varchar(100) DEFAULT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `inwarded_qty` decimal(12,3) NOT NULL DEFAULT 0.000,
  `unit` varchar(30) NOT NULL,
  `weight_kg` decimal(10,3) DEFAULT NULL,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `mrp` decimal(12,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gst_type` varchar(10) NOT NULL DEFAULT 'EXEMPTED',
  `tax_input_type` varchar(10) NOT NULL DEFAULT 'PERCENT',
  `tax_treatment` enum('INCLUDED','EXCLUDED') NOT NULL DEFAULT 'INCLUDED',
  `sgst_value` decimal(12,3) NOT NULL DEFAULT 0.000,
  `cgst_value` decimal(12,3) NOT NULL DEFAULT 0.000,
  `igst_value` decimal(12,3) NOT NULL DEFAULT 0.000,
  `sgst_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cgst_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `igst_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `roll_number` varchar(50) DEFAULT NULL,
  `color` varchar(100) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `design_name` varchar(150) DEFAULT NULL,
  `design_count` int(11) DEFAULT NULL,
  `seller_roll_id` int(10) unsigned DEFAULT NULL,
  `start_code` varchar(100) DEFAULT NULL,
  `end_code` varchar(100) DEFAULT NULL,
  `qr_code` varchar(100) DEFAULT NULL,
  `hsn_code` varchar(20) DEFAULT NULL,
  `is_inwarded` tinyint(1) NOT NULL DEFAULT 0,
  `inwarded_at` datetime DEFAULT NULL,
  `inwarded_by` int(10) unsigned DEFAULT NULL,
  `is_mapped` tinyint(1) NOT NULL DEFAULT 0,
  `is_complementary` tinyint(1) NOT NULL DEFAULT 0,
  `mapped_category_id` int(10) unsigned DEFAULT NULL,
  `mapped_subcategory_id` int(10) unsigned DEFAULT NULL,
  `mapped_sub_subcategory_id` int(10) unsigned DEFAULT NULL,
  `mapped_third_party_category_id` int(10) unsigned DEFAULT NULL,
  `mapped_third_party_subcategory_id` int(10) unsigned DEFAULT NULL,
  `mapped_third_party_sub_subcategory_id` int(10) unsigned DEFAULT NULL,
  `mapped_attributes` text DEFAULT NULL,
  `mapped_at` datetime DEFAULT NULL,
  `mapped_by` int(10) unsigned DEFAULT NULL,
  `qr_generation_id` int(10) unsigned DEFAULT NULL,
  `qr_batch_code` varchar(50) DEFAULT NULL,
  `grn_id` int(10) unsigned DEFAULT NULL,
  `grn_number` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_production_purchase_seller_roll` (`seller_roll_id`),
  KEY `idx_production_purchase_items_purchase` (`purchase_id`),
  KEY `idx_production_purchase_items_stock` (`item_type`,`item_name`,`unit`),
  KEY `idx_ppi_variant_id` (`variant_id`),
  KEY `idx_ppi_type_mapped_qrg` (`item_type`,`is_mapped`,`qr_generation_id`),
  KEY `idx_ppi_qr_generation` (`qr_generation_id`),
  KEY `idx_ppi_grn` (`grn_id`),
  CONSTRAINT `fk_ppi_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `production_purchase_items_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `production_purchases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `production_purchase_items_ibfk_2` FOREIGN KEY (`seller_roll_id`) REFERENCES `seller_rolls` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [37/89] Table structure for `production_purchases`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `production_purchases`;
CREATE TABLE `production_purchases` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `partition_id` int(10) unsigned DEFAULT 1,
  `purchase_number` varchar(50) NOT NULL,
  `purchase_type` enum('FABRIC_ROLL','RAW_MATERIAL','THIRD_PARTY','OWN_PRODUCT') NOT NULL DEFAULT 'FABRIC_ROLL',
  `seller_id` int(10) unsigned NOT NULL,
  `purchase_date` date NOT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `document_url` varchar(500) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `grn_id` int(10) unsigned DEFAULT NULL,
  `grn_number` varchar(50) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_inwarded` tinyint(1) DEFAULT 0,
  `inwarded_at` datetime DEFAULT NULL,
  `concern_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_production_purchase_number` (`purchase_number`),
  KEY `idx_production_purchases_seller_date` (`seller_id`,`purchase_date`),
  KEY `idx_purchases_type_date` (`purchase_type`,`purchase_date`,`seller_id`),
  KEY `idx_pp_grn` (`grn_id`),
  KEY `idx_production_purchases_concern` (`concern_id`),
  KEY `idx_production_purchases_concern_id` (`concern_id`),
  KEY `idx_pp_concern_date` (`concern_id`,`purchase_date`),
  CONSTRAINT `production_purchases_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [38/89] Table structure for `production_return_items`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `production_return_items`;
CREATE TABLE `production_return_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `return_id` int(10) unsigned NOT NULL,
  `bill_item_id` int(10) unsigned DEFAULT NULL,
  `qc_item_id` int(10) unsigned DEFAULT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `hsn_code` varchar(20) DEFAULT NULL,
  `tag_code` varchar(100) DEFAULT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gst_percentage` decimal(7,3) NOT NULL DEFAULT 0.000,
  `taxable_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(255) DEFAULT 'Customer return',
  `item_condition` enum('GOOD','MINOR_DEFECT','DAMAGED') NOT NULL DEFAULT 'GOOD',
  `action_taken` enum('RESTOCKED','SENT_TO_REWORK','SCRAPPED') NOT NULL DEFAULT 'RESTOCKED',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_prod_ret_items_return` (`return_id`),
  KEY `idx_prod_ret_items_tag` (`tag_code`),
  CONSTRAINT `fk_prod_ret_items_return` FOREIGN KEY (`return_id`) REFERENCES `production_returns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [39/89] Table structure for `production_returns`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `production_returns`;
CREATE TABLE `production_returns` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `concern_id` int(11) DEFAULT 1,
  `return_number` varchar(50) NOT NULL,
  `bill_id` int(10) unsigned DEFAULT NULL,
  `bill_number` varchar(50) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(30) DEFAULT NULL,
  `return_type` enum('REFUND','EXCHANGE','CREDIT_NOTE') NOT NULL DEFAULT 'CREDIT_NOTE',
  `disposition` enum('RESTOCK_INVENTORY','REWORK_STITCHING','SCRAP_DAMAGE','MIXED') NOT NULL DEFAULT 'RESTOCK_INVENTORY',
  `total_returned_qty` int(10) unsigned NOT NULL DEFAULT 0,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gst_reversal_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('RECEIVED','INSPECTED','RESTOCKED','COMPLETED','CANCELLED') NOT NULL DEFAULT 'COMPLETED',
  `refund_mode` enum('CASH','UPI','BANK_TRANSFER','STORE_CREDIT','NONE') NOT NULL DEFAULT 'STORE_CREDIT',
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_prod_return_number` (`return_number`),
  KEY `idx_prod_returns_bill` (`bill_id`),
  KEY `idx_prod_returns_date` (`created_at`),
  KEY `idx_production_returns_concern` (`concern_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [40/89] Table structure for `production_stock_request_items`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `production_stock_request_items`;
CREATE TABLE `production_stock_request_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `request_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `size_name` varchar(50) DEFAULT NULL,
  `color_name` varchar(50) DEFAULT NULL,
  `requested_qty` int(10) unsigned NOT NULL DEFAULT 0,
  `dispatched_qty` int(10) unsigned NOT NULL DEFAULT 0,
  `received_qty` int(10) unsigned NOT NULL DEFAULT 0,
  `fulfilled_qty` int(10) unsigned NOT NULL DEFAULT 0,
  `status` enum('PENDING','RECEIVED_BY_SEND_AREA','PARTIAL','ALLOCATED','DISPATCHED','FULFILLED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_prod_stock_req_items_req` (`request_id`),
  CONSTRAINT `fk_prod_stock_req_items_parent` FOREIGN KEY (`request_id`) REFERENCES `production_stock_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [41/89] Table structure for `production_stock_requests`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `production_stock_requests`;
CREATE TABLE `production_stock_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `request_number` varchar(50) NOT NULL,
  `from_module` varchar(100) NOT NULL DEFAULT 'Central Warehouse',
  `to_module` varchar(100) NOT NULL DEFAULT 'Production Unit',
  `from_unit_id` int(10) unsigned DEFAULT NULL,
  `to_unit_id` int(10) unsigned DEFAULT NULL,
  `warehouse_name` varchar(150) NOT NULL DEFAULT 'Central Warehouse',
  `requested_by_name` varchar(150) NOT NULL DEFAULT 'Warehouse Incharge',
  `priority` enum('LOW','NORMAL','HIGH','URGENT') NOT NULL DEFAULT 'NORMAL',
  `required_by_date` date DEFAULT NULL,
  `total_requested_pieces` int(10) unsigned NOT NULL DEFAULT 0,
  `fulfilled_pieces` int(10) unsigned NOT NULL DEFAULT 0,
  `dispatched_pieces` int(10) unsigned NOT NULL DEFAULT 0,
  `status` enum('PENDING','RECEIVED_BY_SEND_AREA','APPROVED','IN_PRODUCTION','READY_FOR_DISPATCH','DISPATCHED','FULFILLED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `notes` text DEFAULT NULL,
  `received_by_send_area_at` datetime DEFAULT NULL,
  `received_by_send_area_by` varchar(150) DEFAULT NULL,
  `dispatched_at` datetime DEFAULT NULL,
  `dispatched_by` varchar(150) DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `received_by` varchar(150) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `concern_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_prod_stock_req_num` (`request_number`),
  KEY `idx_prod_stock_req_status` (`status`),
  KEY `idx_production_stock_requests_created_at` (`created_at`),
  KEY `idx_production_stock_requests_concern_id` (`concern_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [42/89] Table structure for `production_units`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `production_units`;
CREATE TABLE `production_units` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `concern_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `code` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `manager_id` int(10) unsigned DEFAULT NULL,
  `manager_name` varchar(150) DEFAULT NULL,
  `gst_number` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `location` text DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `capacity_per_day` int(11) DEFAULT 0,
  `logo_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `manager_id` (`manager_id`),
  KEY `idx_production_units_concern` (`concern_id`),
  CONSTRAINT `production_units_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [43/89] Table structure for `production_warehouse_reworks`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `production_warehouse_reworks`;
CREATE TABLE `production_warehouse_reworks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rework_number` varchar(50) NOT NULL,
  `warehouse_id` int(10) unsigned NOT NULL,
  `production_outward_id` int(10) unsigned DEFAULT NULL,
  `production_batch_id` int(10) unsigned DEFAULT NULL,
  `warehouse_damage_id` int(10) unsigned DEFAULT NULL,
  `variant_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `production_unit_id` int(10) unsigned DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `repaired_qty` int(11) NOT NULL DEFAULT 0,
  `scrapped_qty` int(11) NOT NULL DEFAULT 0,
  `defect_type` varchar(100) NOT NULL DEFAULT 'STITCHING_ERROR',
  `damage_reason` varchar(255) NOT NULL,
  `defect_notes` text DEFAULT NULL,
  `photo_url` varchar(500) DEFAULT NULL,
  `challan_number` varchar(100) DEFAULT NULL,
  `received_date` date NOT NULL,
  `received_by` int(10) unsigned DEFAULT NULL,
  `assigned_to` varchar(150) DEFAULT NULL,
  `status` enum('RECEIVED','IN_REWORK','REPAIRED','SCRAPPED','RE_DISPATCHED') NOT NULL DEFAULT 'RECEIVED',
  `repair_notes` text DEFAULT NULL,
  `repaired_at` datetime DEFAULT NULL,
  `redispatched_date` datetime DEFAULT NULL,
  `redispatched_vehicle` varchar(100) DEFAULT NULL,
  `redispatched_notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_pwr_rework_number` (`rework_number`),
  KEY `idx_pwr_warehouse` (`warehouse_id`),
  KEY `idx_pwr_outward` (`production_outward_id`),
  KEY `idx_pwr_batch` (`production_batch_id`),
  KEY `idx_pwr_variant` (`variant_id`),
  KEY `idx_pwr_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [44/89] Table structure for `products`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(300) NOT NULL,
  `code` varchar(50) NOT NULL,
  `catalog_code` varchar(100) DEFAULT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `subcategory_id` int(10) unsigned DEFAULT NULL,
  `sub_subcategory_id` int(10) unsigned DEFAULT NULL,
  `category_code` varchar(50) DEFAULT NULL,
  `subcategory_code` varchar(50) DEFAULT NULL,
  `sub_subcategory_code` varchar(50) DEFAULT NULL,
  `qr_code_prefix` varchar(100) DEFAULT NULL,
  `brand_id` int(10) unsigned DEFAULT NULL,
  `seller_id` int(10) unsigned DEFAULT NULL,
  `source_type` enum('OWN_PRODUCTION','EXTERNAL_PURCHASE') NOT NULL DEFAULT 'OWN_PRODUCTION',
  `is_complementary` tinyint(1) NOT NULL DEFAULT 0,
  `material` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `attributes` text DEFAULT NULL,
  `hsn_code` varchar(20) DEFAULT NULL,
  `gst_percentage` decimal(5,2) DEFAULT 0.00,
  `quantity` int(11) DEFAULT 0,
  `weight` decimal(10,3) DEFAULT 0.000,
  `min_stock` int(11) DEFAULT 0,
  `max_stock` int(11) DEFAULT 0,
  `image_url` varchar(500) DEFAULT NULL,
  `cloudinary_public_id` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `product_code` varchar(50) DEFAULT NULL,
  `warehouse_supplier_id` int(10) unsigned DEFAULT NULL,
  `unit_id` int(10) unsigned DEFAULT NULL,
  `warehouse_category_id` int(10) unsigned DEFAULT NULL,
  `warehouse_subcategory_id` int(10) unsigned DEFAULT NULL,
  `warehouse_sub_subcategory_id` int(10) unsigned DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `mfg_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_product_category` (`category_id`),
  KEY `idx_product_brand` (`brand_id`),
  KEY `idx_product_source` (`source_type`),
  KEY `subcategory_id` (`subcategory_id`),
  KEY `sub_subcategory_id` (`sub_subcategory_id`),
  KEY `fk_products_seller` (`seller_id`),
  CONSTRAINT `fk_products_seller` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_ibfk_3` FOREIGN KEY (`sub_subcategory_id`) REFERENCES `sub_subcategories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_ibfk_4` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [45/89] Table structure for `purchase_inward_items`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `purchase_inward_items`;
CREATE TABLE `purchase_inward_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_inward_id` int(10) unsigned NOT NULL,
  `production_purchase_item_id` int(10) unsigned DEFAULT NULL,
  `variant_id` int(10) unsigned NOT NULL,
  `ordered_qty` int(11) NOT NULL,
  `received_qty` int(11) NOT NULL DEFAULT 0,
  `damaged_qty` int(11) DEFAULT 0,
  `accepted_qty` int(11) NOT NULL DEFAULT 0,
  `location_id` int(10) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `batch_lot_no` varchar(100) DEFAULT NULL,
  `mfg_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `mrp` decimal(10,2) DEFAULT 0.00,
  `received_quantity` int(11) NOT NULL,
  `accepted_quantity` int(11) NOT NULL,
  `rejected_quantity` int(11) DEFAULT 0,
  `rejection_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_inward_id` (`purchase_inward_id`),
  KEY `idx_pii_production_purchase_item` (`production_purchase_item_id`),
  KEY `variant_id` (`variant_id`),
  KEY `location_id` (`location_id`),
  CONSTRAINT `purchase_inward_items_ibfk_1` FOREIGN KEY (`purchase_inward_id`) REFERENCES `purchase_inwards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_inward_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`),
  CONSTRAINT `purchase_inward_items_ibfk_3` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [46/89] Table structure for `purchase_inwards`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `purchase_inwards`;
CREATE TABLE `purchase_inwards` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int(10) unsigned NOT NULL,
  `grn_number` varchar(50) NOT NULL,
  `warehouse_id` int(10) unsigned NOT NULL,
  `received_date` date NOT NULL,
  `received_by` int(10) unsigned DEFAULT NULL,
  `verified_by` int(10) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `invoice_number` varchar(100) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `status` enum('PENDING','INSPECTED','STOCKED','DISCREPANCY') DEFAULT 'PENDING',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_received_qty` int(11) DEFAULT 0,
  `total_accepted_qty` int(11) DEFAULT 0,
  `total_rejected_qty` int(11) DEFAULT 0,
  `vehicle_number` varchar(50) DEFAULT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  `driver_contact` varchar(20) DEFAULT NULL,
  `gate_pass_number` varchar(50) DEFAULT NULL,
  `concern_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grn_number` (`grn_number`),
  KEY `purchase_order_id` (`purchase_order_id`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `received_by` (`received_by`),
  KEY `verified_by` (`verified_by`),
  KEY `idx_purchase_inwards_concern_id` (`concern_id`),
  CONSTRAINT `purchase_inwards_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`),
  CONSTRAINT `purchase_inwards_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `purchase_inwards_ibfk_3` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_inwards_ibfk_4` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [47/89] Table structure for `purchase_items`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `purchase_items`;
CREATE TABLE `purchase_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `gst_percentage` decimal(5,2) DEFAULT 0.00,
  `hsn_code` varchar(20) DEFAULT NULL,
  `cgst_amount` decimal(10,2) DEFAULT 0.00,
  `sgst_amount` decimal(10,2) DEFAULT 0.00,
  `igst_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `batch_lot_no` varchar(100) DEFAULT NULL,
  `mfg_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `mrp` decimal(10,2) DEFAULT 0.00,
  `received_quantity` int(11) DEFAULT 0,
  `tax_percentage` decimal(5,2) DEFAULT 0.00,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_id` (`purchase_order_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `purchase_items_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [48/89] Table structure for `purchase_orders`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE `purchase_orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `production_purchase_id` int(10) unsigned DEFAULT NULL,
  `po_number` varchar(50) NOT NULL,
  `seller_id` int(10) unsigned DEFAULT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `warehouse_id` int(10) unsigned NOT NULL,
  `order_date` date NOT NULL,
  `expected_date` date DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `cgst_amount` decimal(10,2) DEFAULT 0.00,
  `sgst_amount` decimal(10,2) DEFAULT 0.00,
  `igst_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `payment_status` enum('PENDING','PARTIAL','PAID') DEFAULT 'PENDING',
  `payment_method` varchar(50) DEFAULT 'CASH',
  `is_direct_purchase` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('DRAFT','CONFIRMED','RECEIVED','PARTIAL_RECEIVED','CANCELLED') DEFAULT 'DRAFT',
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `product_edit_count` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `payment_screenshot_url` varchar(500) DEFAULT NULL,
  `payment_screenshot_public_id` varchar(200) DEFAULT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `paid_amount` decimal(12,2) DEFAULT 0.00,
  `cash_tendered` decimal(12,2) NOT NULL DEFAULT 0.00,
  `change_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `warehouse_supplier_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `po_number` (`po_number`),
  KEY `idx_po_production_purchase` (`production_purchase_id`),
  KEY `seller_id` (`seller_id`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`),
  CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `purchase_orders_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [49/89] Table structure for `qc_batches`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `qc_batches`;
CREATE TABLE `qc_batches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `partition_id` int(10) unsigned DEFAULT 1,
  `batch_number` varchar(50) DEFAULT NULL,
  `qc_number` varchar(50) DEFAULT NULL,
  `program_id` int(10) unsigned NOT NULL,
  `stitching_batch_id` int(10) unsigned DEFAULT NULL,
  `qr_generation_id` int(10) unsigned DEFAULT NULL,
  `inspector_id` int(10) unsigned DEFAULT NULL,
  `total_qty` int(11) NOT NULL DEFAULT 0,
  `passed_qty` int(11) DEFAULT 0,
  `rejected_qty` int(11) DEFAULT 0,
  `rework_qty` int(11) DEFAULT 0,
  `status` enum('PENDING','PASSED','PARTIAL','REWORK','REJECTED','COMPLETED') DEFAULT 'PENDING',
  `notes` text DEFAULT NULL,
  `pieces_data` longtext DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `inspected_qty` int(11) NOT NULL DEFAULT 0,
  `minor_defect_qty` int(11) DEFAULT 0,
  `major_defect_qty` int(11) DEFAULT 0,
  `critical_defect_qty` int(11) DEFAULT 0,
  `qc_status` enum('PENDING','PASSED','CONDITIONALLY_PASSED','REJECTED') DEFAULT 'PENDING',
  `concern_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `program_id` (`program_id`),
  KEY `stitching_batch_id` (`stitching_batch_id`),
  KEY `inspector_id` (`inspector_id`),
  KEY `idx_qc_batches_created_at` (`created_at`),
  KEY `idx_qc_batches_concern_id` (`concern_id`),
  KEY `idx_qc_batches_concern` (`concern_id`),
  KEY `idx_qcb_stitching_status` (`stitching_batch_id`,`status`),
  KEY `idx_qcb_qr_generation` (`qr_generation_id`),
  CONSTRAINT `qc_batches_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `production_programs` (`id`),
  CONSTRAINT `qc_batches_ibfk_2` FOREIGN KEY (`stitching_batch_id`) REFERENCES `stitching_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `qc_batches_ibfk_3` FOREIGN KEY (`inspector_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [50/89] Table structure for `qc_items`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `qc_items`;
CREATE TABLE `qc_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `qc_batch_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `subcategory_id` int(10) unsigned DEFAULT NULL,
  `sub_subcategory_id` int(10) unsigned DEFAULT NULL,
  `catalog_hierarchy` varchar(255) DEFAULT NULL,
  `size_name` varchar(100) DEFAULT NULL,
  `color_name` varchar(100) DEFAULT NULL,
  `total_qty` int(11) NOT NULL DEFAULT 0,
  `tag_code` varchar(100) DEFAULT NULL,
  `base_price` decimal(10,2) DEFAULT 0.00,
  `discount_price` decimal(10,2) DEFAULT 0.00,
  `inspected_qty` int(11) NOT NULL DEFAULT 0,
  `passed_qty` int(11) DEFAULT 0,
  `rejected_qty` int(11) DEFAULT 0,
  `rework_qty` int(11) DEFAULT 0,
  `defect_reason` varchar(255) DEFAULT NULL,
  `defect_notes` text DEFAULT NULL,
  `defect_type` enum('FABRIC_FAULT','STITCHING_FAULT','SIZE_DEVIATION','STAIN_DIRT','LABEL_FAULT','OTHER') DEFAULT 'OTHER',
  `defect_severity` enum('MINOR','MAJOR','CRITICAL') DEFAULT 'MINOR',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `action_taken` enum('PASSED','REWORK','REJECTED','SCRAP') DEFAULT 'PASSED',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `qc_batch_id` (`qc_batch_id`),
  KEY `variant_id` (`variant_id`),
  KEY `idx_qci_batch_passed` (`qc_batch_id`,`passed_qty`),
  CONSTRAINT `qc_items_ibfk_1` FOREIGN KEY (`qc_batch_id`) REFERENCES `qc_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qc_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [51/89] Table structure for `qr_generation_items`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `qr_generation_items`;
CREATE TABLE `qr_generation_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `generation_id` int(10) unsigned NOT NULL,
  `qr_code` varchar(100) NOT NULL,
  `sequence_num` int(11) NOT NULL,
  `status` enum('ACTIVE','USED','DAMAGED','CANCELLED') DEFAULT 'ACTIVE',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `qr_code` (`qr_code`),
  KEY `idx_gen_id` (`generation_id`),
  KEY `idx_qr_code` (`qr_code`),
  KEY `idx_qrgi_gen_status` (`generation_id`,`status`),
  CONSTRAINT `fk_qrg_gen` FOREIGN KEY (`generation_id`) REFERENCES `qr_generations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [52/89] Table structure for `qr_generations`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `qr_generations`;
CREATE TABLE `qr_generations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `batch_code` varchar(50) NOT NULL,
  `generation_type` enum('MANUAL','CATALOG_VARIANT') NOT NULL DEFAULT 'MANUAL',
  `qr_source` varchar(30) NOT NULL DEFAULT 'THIRD_PARTY',
  `source_purchase_id` int(10) unsigned DEFAULT NULL,
  `source_purchase_item_id` int(10) unsigned DEFAULT NULL,
  `stitching_batch_id` int(10) unsigned DEFAULT NULL,
  `program_id` int(10) unsigned DEFAULT NULL,
  `qc_batch_id` int(10) unsigned DEFAULT NULL,
  `stock_added` tinyint(1) NOT NULL DEFAULT 0,
  `stock_added_at` datetime DEFAULT NULL,
  `stock_added_by` int(10) unsigned DEFAULT NULL,
  `brand_name` varchar(150) NOT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `subcategory_id` int(10) unsigned DEFAULT NULL,
  `sub_subcategory_id` int(10) unsigned DEFAULT NULL,
  `category_name` varchar(150) DEFAULT NULL,
  `subcategory_name` varchar(150) DEFAULT NULL,
  `sub_subcategory_name` varchar(150) DEFAULT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `color_id` int(10) unsigned DEFAULT NULL,
  `design_id` int(10) unsigned DEFAULT NULL,
  `size_id` int(10) unsigned DEFAULT NULL,
  `color_name` varchar(100) DEFAULT NULL,
  `design_name` varchar(100) DEFAULT NULL,
  `size_name` varchar(100) DEFAULT NULL,
  `mrp` decimal(10,2) DEFAULT 0.00,
  `selling_price` decimal(10,2) DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `qr_prefix` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `concern_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch_code` (`batch_code`),
  KEY `idx_qrg_brand` (`brand_name`),
  KEY `idx_qrg_cat` (`category_id`),
  KEY `idx_qrg_var` (`variant_id`),
  KEY `idx_qrg_source` (`qr_source`),
  KEY `idx_qrg_source_purchase` (`source_purchase_id`,`qr_source`),
  KEY `idx_qr_generations_concern_id` (`concern_id`),
  KEY `idx_qrg_source_concern` (`qr_source`,`concern_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [53/89] Table structure for `role_permissions`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(10) unsigned NOT NULL,
  `permission_id` int(10) unsigned NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_permission` (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1332 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [54/89] Table structure for `roles`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `concern_scope` varchar(20) NOT NULL DEFAULT 'ALL',
  `applicable_concerns` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_system` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [55/89] Table structure for `roll_damage_reasons`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `roll_damage_reasons`;
CREATE TABLE `roll_damage_reasons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reason` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_roll_damage_reasons_reason` (`reason`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [56/89] Table structure for `roll_damages`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `roll_damages`;
CREATE TABLE `roll_damages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `partition_id` int(10) unsigned DEFAULT 1,
  `damage_number` varchar(50) NOT NULL,
  `item_type` enum('FABRIC_ROLL','PRODUCT') NOT NULL DEFAULT 'FABRIC_ROLL',
  `seller_id` int(10) unsigned DEFAULT NULL,
  `seller_roll_id` int(10) unsigned DEFAULT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `product_name` varchar(300) DEFAULT NULL,
  `variant_info` varchar(255) DEFAULT NULL,
  `qr_code` varchar(64) DEFAULT NULL,
  `damaged_quantity` int(11) DEFAULT 0,
  `damaged_weight` decimal(10,2) DEFAULT 0.00,
  `loss_amount` decimal(12,2) DEFAULT 0.00,
  `damage_reason` varchar(255) NOT NULL,
  `defect_notes` text DEFAULT NULL,
  `photo_url` varchar(500) DEFAULT NULL,
  `status` enum('LOGGED','INVESTIGATING','CLAIMED','SETTLED','REJECTED','CLOSED','CANCELLED','REPLACEMENT_RECEIVED','RETURN_INITIATED','RETURNED_TO_SELLER','REFUND_RECEIVED') NOT NULL DEFAULT 'LOGGED',
  `resolution_type` varchar(100) DEFAULT 'NONE',
  `refund_amount` decimal(12,2) DEFAULT 0.00,
  `debit_note_number` varchar(100) DEFAULT NULL,
  `replacement_roll_number` varchar(100) DEFAULT NULL,
  `replacement_roll_id` int(10) unsigned DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `resolved_by` int(10) unsigned DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `reported_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `concern_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_roll_damages_number` (`damage_number`),
  KEY `idx_roll_damages_seller` (`seller_id`),
  KEY `idx_roll_damages_roll` (`seller_roll_id`),
  UNIQUE KEY `uniq_roll_damages_qr_code` (`qr_code`),
  KEY `idx_roll_damages_qr_code` (`qr_code`),
  KEY `idx_roll_damages_concern_id` (`concern_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [57/89] Table structure for `sales_channels`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `sales_channels`;
CREATE TABLE `sales_channels` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `code` varchar(20) NOT NULL,
  `manager_name` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `gst_number` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `channel_type` enum('WHATSAPP','WEBSITE','OTHER') DEFAULT 'OTHER',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [58/89] Table structure for `seller_roll_deleted_labels`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `seller_roll_deleted_labels`;
CREATE TABLE `seller_roll_deleted_labels` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `seller_roll_id` int(10) unsigned NOT NULL,
  `sequence` int(10) unsigned NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_seller_roll_deleted_sequence` (`seller_roll_id`,`sequence`),
  CONSTRAINT `seller_roll_deleted_labels_ibfk_1` FOREIGN KEY (`seller_roll_id`) REFERENCES `seller_rolls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [59/89] Table structure for `seller_rolls`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `seller_rolls`;
CREATE TABLE `seller_rolls` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `partition_id` int(10) unsigned DEFAULT 1,
  `seller_id` int(10) unsigned NOT NULL,
  `roll_number` varchar(50) DEFAULT NULL,
  `color` varchar(100) DEFAULT NULL,
  `design_name` varchar(150) DEFAULT NULL,
  `design_count` int(11) DEFAULT 1,
  `quantity` int(11) DEFAULT 0,
  `weight` decimal(10,2) DEFAULT 0.00,
  `price` decimal(12,2) DEFAULT 0.00,
  `material_type` varchar(150) DEFAULT NULL,
  `photo_url` varchar(500) DEFAULT NULL,
  `start_seq` int(10) unsigned DEFAULT NULL,
  `end_seq` int(10) unsigned DEFAULT NULL,
  `start_code` varchar(50) DEFAULT NULL,
  `end_code` varchar(50) DEFAULT NULL,
  `qr_code` varchar(100) DEFAULT NULL,
  `hsn_code` varchar(20) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_seller_rolls_seller` (`seller_id`),
  KEY `idx_seller_rolls_created_at` (`created_at`),
  CONSTRAINT `seller_rolls_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [60/89] Table structure for `sellers`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `sellers`;
CREATE TABLE `sellers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `partition_id` int(10) unsigned DEFAULT 1,
  `name` varchar(200) NOT NULL,
  `company` varchar(300) DEFAULT NULL,
  `gst_number` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `payment_terms` varchar(200) DEFAULT NULL,
  `material_type` varchar(150) DEFAULT NULL,
  `colors` varchar(255) DEFAULT NULL,
  `roll_photo` varchar(500) DEFAULT NULL,
  `roll_count` int(11) DEFAULT 0,
  `color_count` int(11) DEFAULT 0,
  `design_count` int(11) DEFAULT 0,
  `color_design_count` int(11) DEFAULT 0,
  `roll_weight` decimal(10,2) DEFAULT 0.00,
  `roll_price` decimal(12,2) DEFAULT 0.00,
  `document_url` varchar(500) DEFAULT NULL,
  `seller_type` enum('MATERIAL_SUPPLIER','PRODUCT_SELLER') DEFAULT 'PRODUCT_SELLER',
  `is_active` tinyint(1) DEFAULT 1,
  `production_deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `gstin` varchar(20) DEFAULT NULL,
  `company_name` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [61/89] Table structure for `settings`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'general',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_public` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [62/89] Table structure for `shops`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `shops`;
CREATE TABLE `shops` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `concern_id` int(11) DEFAULT 1,
  `name` varchar(200) NOT NULL,
  `code` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `manager_id` int(10) unsigned DEFAULT NULL,
  `manager_name` varchar(150) DEFAULT NULL,
  `gst_number` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `manager_id` (`manager_id`),
  KEY `idx_shops_concern` (`concern_id`),
  CONSTRAINT `shops_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [63/89] Table structure for `sizes`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `sizes`;
CREATE TABLE `sizes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `code` varchar(20) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [64/89] Table structure for `stitching_batches`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `stitching_batches`;
CREATE TABLE `stitching_batches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `partition_id` int(10) unsigned DEFAULT 1,
  `name` varchar(255) DEFAULT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `cutting_batch_id` int(10) unsigned DEFAULT NULL,
  `program_id` int(10) unsigned NOT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `subcategory_id` int(10) unsigned DEFAULT NULL,
  `sub_subcategory_id` int(10) unsigned DEFAULT NULL,
  `worker_id` int(10) unsigned DEFAULT NULL,
  `received_qty` int(11) NOT NULL DEFAULT 0,
  `stitched_qty` int(11) DEFAULT 0,
  `total_weight` decimal(10,2) DEFAULT 0.00,
  `rejected_qty` int(11) DEFAULT 0,
  `rework_qty` int(11) DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('PENDING','IN_PROGRESS','COMPLETED') DEFAULT 'PENDING',
  `qr_generation_id` int(10) unsigned DEFAULT NULL,
  `qr_batch_code` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `production_unit_id` int(10) unsigned DEFAULT NULL,
  `tailor_master` varchar(150) DEFAULT NULL,
  `operator_name` varchar(150) DEFAULT NULL,
  `total_cut_qty` int(11) NOT NULL DEFAULT 0,
  `total_stitched_qty` int(11) DEFAULT 0,
  `total_rejected_qty` int(11) DEFAULT 0,
  `total_wastage_qty` int(11) DEFAULT 0,
  `completion_date` date DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `concern_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cutting_batch_id` (`cutting_batch_id`),
  KEY `program_id` (`program_id`),
  KEY `worker_id` (`worker_id`),
  KEY `idx_stitching_batches_created_at` (`created_at`),
  KEY `idx_stitching_batches_concern_id` (`concern_id`),
  KEY `idx_stitching_batches_concern` (`concern_id`),
  KEY `idx_sb_qrg_concern_status` (`qr_generation_id`,`concern_id`,`status`),
  CONSTRAINT `stitching_batches_ibfk_1` FOREIGN KEY (`cutting_batch_id`) REFERENCES `cutting_batches` (`id`),
  CONSTRAINT `stitching_batches_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `production_programs` (`id`),
  CONSTRAINT `stitching_batches_ibfk_3` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [65/89] Table structure for `stitching_items`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `stitching_items`;
CREATE TABLE `stitching_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `stitching_batch_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `subcategory_id` int(10) unsigned DEFAULT NULL,
  `sub_subcategory_id` int(10) unsigned DEFAULT NULL,
  `catalog_hierarchy` varchar(255) DEFAULT NULL,
  `size_name` varchar(100) DEFAULT NULL,
  `color_name` varchar(100) DEFAULT NULL,
  `received_qty` int(11) NOT NULL DEFAULT 0,
  `stitched_qty` int(11) DEFAULT 0,
  `weight_per_piece` decimal(10,4) DEFAULT 0.0000,
  `weight` decimal(10,2) DEFAULT 0.00,
  `base_price` decimal(10,2) DEFAULT 0.00,
  `discount_price` decimal(10,2) DEFAULT 0.00,
  `rejected_qty` int(11) DEFAULT 0,
  `rework_qty` int(11) DEFAULT 0,
  `cut_quantity` int(11) NOT NULL DEFAULT 0,
  `stitched_quantity` int(11) NOT NULL DEFAULT 0,
  `rejected_quantity` int(11) DEFAULT 0,
  `wastage_quantity` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `stitching_batch_id` (`stitching_batch_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `stitching_items_ibfk_1` FOREIGN KEY (`stitching_batch_id`) REFERENCES `stitching_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stitching_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [66/89] Table structure for `stitching_wastage_sales`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `stitching_wastage_sales`;
CREATE TABLE `stitching_wastage_sales` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `concern_id` int(11) DEFAULT 1,
  `sale_number` varchar(50) NOT NULL,
  `buyer_name` varchar(200) NOT NULL,
  `buyer_phone` varchar(20) DEFAULT NULL,
  `buyer_company` varchar(200) DEFAULT NULL,
  `stitching_batch_id` int(10) unsigned DEFAULT NULL,
  `program_id` int(10) unsigned DEFAULT NULL,
  `waste_type` varchar(100) NOT NULL DEFAULT 'THREAD_SCRAP',
  `unit_type` varchar(50) NOT NULL DEFAULT 'KG',
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `rate_per_unit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('PENDING','PARTIAL','PAID','CANCELLED') NOT NULL DEFAULT 'PENDING',
  `payment_method` varchar(50) NOT NULL DEFAULT 'CASH',
  `payment_reference` varchar(100) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `sale_date` date NOT NULL,
  `photo_url` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_stitching_wastage_sales_number` (`sale_number`),
  KEY `idx_stitching_wastage_sales_batch` (`stitching_batch_id`),
  KEY `idx_stitching_wastage_sales_program` (`program_id`),
  KEY `idx_stitching_wastage_sales_date` (`sale_date`),
  KEY `idx_stitching_wastage_sales_concern` (`concern_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [67/89] Table structure for `stock_adjustments`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `stock_adjustments`;
CREATE TABLE `stock_adjustments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `adjustment_number` varchar(50) NOT NULL,
  `warehouse_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned NOT NULL,
  `adjustment_type` enum('INCREASE','DECREASE') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `reason` text NOT NULL,
  `requested_by` int(10) unsigned DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
  `notes` text DEFAULT NULL,
  `qr_codes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `adj_number` varchar(50) NOT NULL,
  `shop_id` int(10) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `concern_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `adjustment_number` (`adjustment_number`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `variant_id` (`variant_id`),
  KEY `requested_by` (`requested_by`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_stock_adjustments_concern_id` (`concern_id`),
  CONSTRAINT `stock_adjustments_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `stock_adjustments_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`),
  CONSTRAINT `stock_adjustments_ibfk_3` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_adjustments_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [68/89] Table structure for `stock_balances`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `stock_balances`;
CREATE TABLE `stock_balances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `variant_id` int(10) unsigned NOT NULL,
  `location_type` enum('WAREHOUSE','SHOP','PRODUCTION_UNIT','CHANNEL','OFFICE') NOT NULL,
  `location_id` int(10) unsigned NOT NULL,
  `stock_state` enum('AVAILABLE','RESERVED','DAMAGED','RETURNED') NOT NULL DEFAULT 'AVAILABLE',
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `concern_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_stock_balance` (`variant_id`,`location_type`,`location_id`,`stock_state`),
  KEY `idx_stock_location` (`location_type`,`location_id`),
  KEY `idx_stock_variant` (`variant_id`),
  KEY `idx_stock_balances_concern_id` (`concern_id`),
  KEY `idx_stock_balances_concern` (`concern_id`),
  CONSTRAINT `stock_balances_ibfk_1` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [69/89] Table structure for `stock_transactions`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `stock_transactions`;
CREATE TABLE `stock_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `variant_id` int(10) unsigned NOT NULL,
  `location_type` enum('WAREHOUSE','SHOP','PRODUCTION_UNIT','CHANNEL','OFFICE') NOT NULL,
  `location_id` int(10) unsigned NOT NULL,
  `stock_state` enum('AVAILABLE','RESERVED','DAMAGED','RETURNED') NOT NULL,
  `transaction_type` varchar(50) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `before_quantity` decimal(10,2) DEFAULT NULL,
  `after_quantity` decimal(10,2) DEFAULT NULL,
  `balance_after` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(10) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `source_type` enum('PRODUCTION','PURCHASE','WAREHOUSE','SHOP','CUSTOMER','SUPPLIER','ADJUSTMENT','CHANNEL','OFFICE') DEFAULT NULL,
  `source_id` int(10) unsigned DEFAULT NULL,
  `dest_type` enum('PRODUCTION','PURCHASE','WAREHOUSE','SHOP','CUSTOMER','SUPPLIER','ADJUSTMENT','CHANNEL','OFFICE') DEFAULT NULL,
  `dest_id` int(10) unsigned DEFAULT NULL,
  `from_location_type` varchar(50) DEFAULT NULL,
  `from_location_id` int(10) unsigned DEFAULT NULL,
  `to_location_type` varchar(50) DEFAULT NULL,
  `to_location_id` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `concern_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_txn_variant` (`variant_id`),
  KEY `idx_txn_location` (`location_type`,`location_id`),
  KEY `idx_txn_reference` (`reference_type`,`reference_id`),
  KEY `idx_txn_type` (`transaction_type`),
  KEY `idx_txn_date` (`created_at`),
  KEY `user_id` (`user_id`),
  KEY `idx_stock_transactions_concern_id` (`concern_id`),
  KEY `idx_stock_transactions_concern` (`concern_id`),
  CONSTRAINT `stock_transactions_ibfk_1` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`),
  CONSTRAINT `stock_transactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [70/89] Table structure for `sub_subcategories`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `sub_subcategories`;
CREATE TABLE `sub_subcategories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subcategory_id` int(10) unsigned NOT NULL,
  `name` varchar(200) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `slug` varchar(200) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image_url` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sub_subcat_slug` (`subcategory_id`,`slug`),
  CONSTRAINT `sub_subcategories_ibfk_1` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [71/89] Table structure for `subcategories`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `subcategories`;
CREATE TABLE `subcategories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `name` varchar(200) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `slug` varchar(200) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image_url` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_subcat_slug` (`category_id`,`slug`),
  CONSTRAINT `subcategories_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [72/89] Table structure for `third_party_categories`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `third_party_categories`;
CREATE TABLE `third_party_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `slug` varchar(200) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_third_party_category_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [73/89] Table structure for `third_party_products`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `third_party_products`;
CREATE TABLE `third_party_products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `concern_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(100) NOT NULL,
  `vendor_sku` varchar(100) DEFAULT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `subcategory_id` int(10) unsigned DEFAULT NULL,
  `sub_subcategory_id` int(10) unsigned DEFAULT NULL,
  `third_party_category_id` int(10) unsigned DEFAULT NULL,
  `third_party_subcategory_id` int(10) unsigned DEFAULT NULL,
  `third_party_sub_subcategory_id` int(10) unsigned DEFAULT NULL,
  `category_code` varchar(50) DEFAULT NULL,
  `subcategory_code` varchar(50) DEFAULT NULL,
  `sub_subcategory_code` varchar(50) DEFAULT NULL,
  `brand_id` int(10) unsigned DEFAULT NULL,
  `seller_id` int(10) unsigned DEFAULT NULL,
  `color` varchar(100) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `unit` varchar(30) DEFAULT 'PCS',
  `hsn_code` varchar(20) DEFAULT NULL,
  `mrp` decimal(10,2) DEFAULT 0.00,
  `selling_price` decimal(10,2) DEFAULT 0.00,
  `purchase_rate` decimal(10,2) DEFAULT 0.00,
  `attributes` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_complementary` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_tp_code` (`code`),
  KEY `idx_tp_name` (`name`),
  KEY `idx_tp_category` (`third_party_category_id`),
  KEY `idx_tp_cat` (`category_id`),
  KEY `idx_tp_subcat` (`subcategory_id`),
  KEY `idx_tp_seller` (`seller_id`),
  KEY `idx_tp_concern` (`concern_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table structure for `third_party_product_variants`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `third_party_product_variants`;
CREATE TABLE `third_party_product_variants` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `third_party_product_id` int(10) unsigned NOT NULL,
  `purchase_item_id` int(10) unsigned DEFAULT NULL,
  `sku` varchar(100) NOT NULL,
  `size` varchar(50) NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  `allocated_qty` decimal(12,3) NOT NULL DEFAULT 0.000,
  `purchase_rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `mrp` decimal(12,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `barcode` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tppv_product` (`third_party_product_id`),
  KEY `idx_tppv_item` (`purchase_item_id`),
  KEY `idx_tppv_sku` (`sku`),
  CONSTRAINT `third_party_product_variants_ibfk_1` FOREIGN KEY (`third_party_product_id`) REFERENCES `third_party_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [74/89] Table structure for `third_party_sub_subcategories`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `third_party_sub_subcategories`;
CREATE TABLE `third_party_sub_subcategories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subcategory_id` int(10) unsigned NOT NULL,
  `name` varchar(200) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `slug` varchar(200) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_third_party_sub_subcategory_slug` (`subcategory_id`,`slug`),
  CONSTRAINT `fk_third_party_sub_subcategories_subcategory` FOREIGN KEY (`subcategory_id`) REFERENCES `third_party_subcategories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [75/89] Table structure for `third_party_subcategories`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `third_party_subcategories`;
CREATE TABLE `third_party_subcategories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `name` varchar(200) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `slug` varchar(200) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_third_party_subcategory_slug` (`category_id`,`slug`),
  CONSTRAINT `fk_third_party_subcategories_category` FOREIGN KEY (`category_id`) REFERENCES `third_party_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [76/89] Table structure for `units`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `units`;
CREATE TABLE `units` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `abbreviation` varchar(10) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [77/89] Table structure for `user_concerns`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `user_concerns`;
CREATE TABLE `user_concerns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `concern_id` int(11) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_concern` (`user_id`,`concern_id`),
  KEY `idx_uc_user` (`user_id`),
  KEY `idx_uc_concern` (`concern_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- [78/89] Table structure for `user_permission_overrides`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `user_permission_overrides`;
CREATE TABLE `user_permission_overrides` (
  `user_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- [79/89] Table structure for `user_permissions`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `user_permissions`;
CREATE TABLE `user_permissions` (
  `user_id` int(10) unsigned NOT NULL,
  `permission_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`permission_id`),
  KEY `idx_user_perms` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [80/89] Table structure for `users`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `emp_id` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role_id` int(10) unsigned NOT NULL,
  `department` varchar(50) DEFAULT 'GENERAL',
  `staff_role` varchar(50) DEFAULT NULL,
  `production_unit_id` int(10) unsigned DEFAULT NULL,
  `warehouse_id` int(10) unsigned DEFAULT NULL,
  `shop_id` int(10) unsigned DEFAULT NULL,
  `channel_id` int(10) unsigned DEFAULT NULL,
  `default_concern_id` int(11) DEFAULT NULL,
  `reports_access` varchar(50) DEFAULT 'ALL',
  `is_active` tinyint(1) DEFAULT 1,
  `must_change_password` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `avatar` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [81/89] Table structure for `warehouse_allocations`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `warehouse_allocations`;
CREATE TABLE `warehouse_allocations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `allocation_number` varchar(50) NOT NULL,
  `warehouse_id` int(10) unsigned NOT NULL,
  `destination_type` varchar(50) NOT NULL DEFAULT 'SHOP',
  `destination_id` int(10) unsigned DEFAULT NULL,
  `shop_id` int(10) unsigned DEFAULT NULL,
  `channel_id` int(10) unsigned DEFAULT NULL,
  `requested_by` int(10) unsigned DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `status` enum('REQUESTED','APPROVED','PICKED','PACKED','DISPATCHED','DELIVERED','PARTIAL_RECEIVED','RECEIVED','COMPLETED','CANCELLED') NOT NULL DEFAULT 'REQUESTED',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `allocation_number` (`allocation_number`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `requested_by` (`requested_by`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `warehouse_allocations_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `warehouse_allocations_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warehouse_allocations_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [82/89] Table structure for `warehouse_categories`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `warehouse_categories`;
CREATE TABLE `warehouse_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `category_type` varchar(50) NOT NULL DEFAULT 'NORMAL',
  `code` varchar(20) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `hsn_code` varchar(20) DEFAULT NULL,
  `gst_rate` decimal(5,2) DEFAULT 5.00,
  `image_url` varchar(500) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- [83/89] Table structure for `warehouse_damages`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `warehouse_damages`;
CREATE TABLE `warehouse_damages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `damage_number` varchar(50) NOT NULL,
  `warehouse_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned NOT NULL,
  `tag_code` varchar(100) DEFAULT NULL,
  `source_type` enum('INWARD_TRANSIT','PRODUCTION_INWARD','STORAGE_HANDLING','CUSTOMER_RETURN','EXPIRED_SPOILED','OTHER') DEFAULT 'STORAGE_HANDLING',
  `reference_id` int(10) unsigned DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `cost_per_unit` decimal(10,2) DEFAULT 0.00,
  `total_loss` decimal(10,2) DEFAULT 0.00,
  `damage_reason` varchar(255) NOT NULL,
  `defect_notes` text DEFAULT NULL,
  `photo_url` varchar(500) DEFAULT NULL,
  `status` enum('LOGGED','INSPECTED','SALVAGED','WRITTEN_OFF','REPAIRED','REWORK') DEFAULT 'LOGGED',
  `resolution_action` varchar(32) DEFAULT NULL,
  `resolution_reason` varchar(255) DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `resolution_amount` decimal(10,2) DEFAULT NULL,
  `replacement_variant_id` int(10) unsigned DEFAULT NULL,
  `replacement_qr_code` varchar(100) DEFAULT NULL,
  `replacement_inwarded_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `reported_by` int(10) unsigned NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `production_batch_id` int(10) unsigned DEFAULT NULL,
  `purchase_inward_id` int(10) unsigned DEFAULT NULL,
  `defect_type` enum('FABRIC_DEFECT','COLOR_FADE','STITCHING_ERROR','PACKAGING_DAMAGE','WATER_STAIN','TRANSIT_DAMAGE','EXPIRED','OTHER') DEFAULT 'OTHER',
  `estimated_cost` decimal(10,2) DEFAULT 0.00,
  `disposition_notes` text DEFAULT NULL,
  `inspected_by` int(10) unsigned DEFAULT NULL,
  `damaged_qty` int(11) NOT NULL DEFAULT 1,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `production_unit_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `damage_number` (`damage_number`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `variant_id` (`variant_id`),
  KEY `idx_warehouse_damages_production_unit` (`production_unit_id`),
  KEY `reported_by` (`reported_by`),
  KEY `idx_warehouse_damages_tag_code` (`tag_code`),
  KEY `idx_warehouse_damages_status` (`status`),
  CONSTRAINT `warehouse_damages_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `warehouse_damages_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`),
  CONSTRAINT `warehouse_damages_ibfk_3` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- [84/89] Table structure for `warehouse_qr_codes`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `warehouse_qr_codes`;
CREATE TABLE `warehouse_qr_codes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `qr_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `warehouse_id` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `inwarded_at` timestamp NULL DEFAULT NULL,
  `pack_quantity` int(11) DEFAULT 1,
  `series_end_code` varchar(100) DEFAULT NULL,
  `is_extra` tinyint(1) NOT NULL DEFAULT 0,
  `extra_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_warehouse_qr_code` (`qr_code`),
  KEY `idx_warehouse_qr_variant` (`variant_id`),
  KEY `idx_warehouse_qr_warehouse` (`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [85/89] Table structure for `warehouse_sub_subcategories`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `warehouse_sub_subcategories`;
CREATE TABLE `warehouse_sub_subcategories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subcategory_id` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `code` varchar(20) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  UNIQUE KEY `unique_subcat_slug` (`subcategory_id`,`slug`),
  KEY `subcategory_id` (`subcategory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- [86/89] Table structure for `warehouse_subcategories`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `warehouse_subcategories`;
CREATE TABLE `warehouse_subcategories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `code` varchar(20) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  UNIQUE KEY `unique_category_slug` (`category_id`,`slug`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- [87/89] Table structure for `warehouses`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `warehouses`;
CREATE TABLE `warehouses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `code` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `gst_number` varchar(20) DEFAULT NULL,
  `manager_id` int(10) unsigned DEFAULT NULL,
  `manager_name` varchar(150) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `concern_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `manager_id` (`manager_id`),
  KEY `idx_warehouses_concern_id` (`concern_id`),
  KEY `idx_warehouses_concern` (`concern_id`),
  CONSTRAINT `warehouses_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [88/89] Table structure for `wastage_sales`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `wastage_sales`;
CREATE TABLE `wastage_sales` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `concern_id` int(11) DEFAULT 1,
  `partition_id` int(10) unsigned DEFAULT 1,
  `sale_number` varchar(50) NOT NULL,
  `buyer_name` varchar(200) NOT NULL,
  `buyer_phone` varchar(20) DEFAULT NULL,
  `buyer_company` varchar(200) DEFAULT NULL,
  `cutting_batch_id` int(10) unsigned DEFAULT NULL,
  `program_id` int(10) unsigned DEFAULT NULL,
  `wastage_type` varchar(100) NOT NULL DEFAULT 'FABRIC_SCRAP',
  `weight` decimal(10,2) NOT NULL DEFAULT 0.00,
  `rate_per_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('PENDING','PARTIAL','PAID','CANCELLED') NOT NULL DEFAULT 'PENDING',
  `payment_method` varchar(50) NOT NULL DEFAULT 'CASH',
  `payment_reference` varchar(100) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `sale_date` date NOT NULL,
  `photo_url` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_wastage_sales_number` (`sale_number`),
  KEY `idx_wastage_sales_batch` (`cutting_batch_id`),
  KEY `idx_wastage_sales_program` (`program_id`),
  KEY `idx_wastage_sales_date` (`sale_date`),
  KEY `idx_wastage_sales_concern` (`concern_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [89/89] Table structure for `wh_category_attributes`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `wh_category_attributes`;
CREATE TABLE `wh_category_attributes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `subcategory_id` int(10) unsigned DEFAULT NULL,
  `sub_subcategory_id` int(10) unsigned DEFAULT NULL,
  `attr_name` varchar(100) NOT NULL,
  `attr_type` enum('TEXT','NUMBER','SELECT','MULTI_SELECT') NOT NULL DEFAULT 'TEXT',
  `attr_options` text DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cat_attrs` (`category_id`,`subcategory_id`,`sub_subcategory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- CORE ESSENTIAL SEED DATA
-- ============================================================

-- 1. Concerns Master
INSERT INTO `concerns` (`id`, `name`, `code`, `legal_name`, `gst_number`, `pan_number`, `address`, `city`, `state`, `pincode`, `phone`, `email`, `logo_url`, `invoice_prefix`, `po_prefix`, `dispatch_prefix`, `is_active`, `created_at`) VALUES
(1, 'GRM Apparels', 'CN-01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'INV', 'PO', 'DSP', 1, '2026-11-19 10:18:33'),
(4, 'testing', 'TE-1', 'grm\'s', '24AAACH7409R2Z6', NULL, 'testing', 'tiruppur', 'tamilnadu', '641601', '9789660115', 'testing@gamil.com', NULL, 'INV-', 'PO-', 'DSP-', 1, '2026-11-19 11:39:35')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 2. Roles Master
INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `is_active`, `created_at`) VALUES
(1, 'Super Admin', 'super-admin', 'Full system access and user administration', 1, '2026-11-17 12:17:27'),
(2, 'Admin', 'admin', 'Business administration and managerial approvals', 1, '2026-11-17 12:17:27'),
(3, 'Production Manager', 'production-manager', 'Production planning and batch tracking', 1, '2026-11-17 12:17:27'),
(4, 'Production Staff', 'production-staff', 'Floor operations and cutting entries', 1, '2026-11-17 12:17:27'),
(5, 'Warehouse Manager', 'warehouse-manager', 'Warehouse receiving and allocations', 1, '2026-11-17 12:17:27'),
(6, 'Warehouse Staff', 'warehouse-staff', 'Picking, packing, and dispatch', 1, '2026-11-17 12:17:27'),
(7, 'Shop Manager', 'shop-manager', 'Retail store operations and point of sale', 1, '2026-11-17 12:17:27'),
(8, 'Billing Staff', 'billing-staff', 'Invoicing, POS checkout and receipts', 1, '2026-11-17 12:17:27'),
(9, 'Accountant', 'accountant', 'Financial registers, expenses, and ledgers', 1, '2026-11-17 12:17:27'),
(10, 'Report Viewer', 'report-viewer', 'Read-only access to analytics and reports', 1, '2026-11-17 12:17:27'),
(12, 'testing', 'testing', 'testing', 1, '2026-11-19 11:44:07')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 3. Permissions Master (447 permissions)
INSERT INTO `permissions` (`id`, `module`, `action`, `description`, `slug`, `created_at`) VALUES
(1, 'allocation', 'approve', 'Approve Stock Allocation Demand', 'allocation.approve', '2026-11-17 12:17:27'),
(2, 'allocation', 'create', 'Create Store Allocation Request', 'allocation.create', '2026-11-17 12:17:27'),
(3, 'allocation', 'dispatch', 'Dispatch Allocated Goods to Store', 'allocation.dispatch', '2026-11-17 12:17:27'),
(4, 'allocation', 'pack', 'Execute Carton Packing & Sealing', 'allocation.pack', '2026-11-17 12:17:27'),
(5, 'allocation', 'pick', 'Execute Floor Box Picking', 'allocation.pick', '2026-11-17 12:17:27'),
(6, 'allocation', 'view', 'View Store Allocations & Picking Sheets', 'allocation.view', '2026-11-17 12:17:27'),
(7, 'allocation_outward', 'create', 'Create Outward Transport Manifest', 'allocation_outward.create', '2026-11-17 12:17:27'),
(8, 'allocation_outward', 'deliver', 'Mark Outward Shipment as Delivered', 'allocation_outward.deliver', '2026-11-17 12:17:27'),
(9, 'allocation_outward', 'gatepass', 'Print Transport Gatepass', 'allocation_outward.gatepass', '2026-11-17 12:17:27'),
(10, 'allocation_outward', 'view', 'View Warehouse Outward Manifests', 'allocation_outward.view', '2026-11-17 12:17:27'),
(11, 'asset_bulk_labels', 'print', 'Print Bulk Asset Barcode QR Stickers', 'asset_bulk_labels.print', '2026-11-17 12:17:27'),
(12, 'asset_bulk_labels', 'view', 'View Bulk Asset QR Label Studio', 'asset_bulk_labels.view', '2026-11-17 12:17:27'),
(13, 'asset_maintenance', 'create', 'Log Maintenance Ticket / Breakdown Service', 'asset_maintenance.create', '2026-11-17 12:17:27'),
(14, 'asset_maintenance', 'edit', 'Edit Service Details & Invoices', 'asset_maintenance.edit', '2026-11-17 12:17:27'),
(15, 'asset_maintenance', 'view', 'View Asset Maintenance & Repair Logs', 'asset_maintenance.view', '2026-11-17 12:17:27'),
(16, 'asset_scanner', 'scan', 'Execute Hardware Scan & Physical Audit', 'asset_scanner.scan', '2026-11-17 12:17:27'),
(17, 'asset_scanner', 'view', 'Access QR Scanner Station', 'asset_scanner.view', '2026-11-17 12:17:27'),
(18, 'asset_transfers', 'approve', 'Approve & Receive Asset Relocation', 'asset_transfers.approve', '2026-11-17 12:17:27'),
(19, 'asset_transfers', 'create', 'Create Asset Relocation Manifest', 'asset_transfers.create', '2026-11-17 12:17:27'),
(20, 'asset_transfers', 'view', 'View Inter-Unit Asset Transfers', 'asset_transfers.view', '2026-11-17 12:17:27'),
(21, 'assets', 'create', 'Register New Machinery / Asset Item', 'assets.create', '2026-11-17 12:17:27'),
(22, 'assets', 'delete', 'Delete Asset Entry', 'assets.delete', '2026-11-17 12:17:27'),
(23, 'assets', 'edit', 'Edit Asset Details, Location & Value', 'assets.edit', '2026-11-17 12:17:27'),
(24, 'assets', 'print', 'Print asset labels', 'assets.print', '2026-11-17 12:17:27'),
(25, 'assets', 'view', 'View Asset Registry & Equipment Valuation', 'assets.view', '2026-11-17 12:17:27'),
(26, 'audit', 'export', 'Export Audit Logs to CSV', 'audit.export', '2026-11-17 12:17:27'),
(27, 'audit', 'view', 'View Audit Trail & System Activity Logs', 'audit.view', '2026-11-17 12:17:27'),
(28, 'backup', 'create', 'Generate Manual Database Backup Snapshot', 'backup.create', '2026-11-17 12:17:27'),
(29, 'backup', 'delete', 'Delete Database Backup File', 'backup.delete', '2026-11-17 12:17:27'),
(30, 'backup', 'download', 'Download Backup Archive (ZIP/SQL)', 'backup.download', '2026-11-17 12:17:27'),
(31, 'backup', 'restore', 'Restore Database from Snapshot File', 'backup.restore', '2026-11-17 12:17:27'),
(32, 'backup', 'view', 'View Database Snapshots & Recovery Hub', 'backup.view', '2026-11-17 12:17:27'),
(33, 'batches', 'approve', 'Approve Finished Goods Packaging Batch', 'batches.approve', '2026-11-17 12:17:27'),
(34, 'batches', 'create', 'Create Packaging Batch & Box Consolidations', 'batches.create', '2026-11-17 12:17:27'),
(35, 'batches', 'print', 'Print Packaging Batch QR Master Label', 'batches.print', '2026-11-17 12:17:27'),
(36, 'batches', 'view', 'View Packaging Batches', 'batches.view', '2026-11-17 12:17:27'),
(37, 'catalog', 'create', 'Create Style Catalog & BOM', 'catalog.create', '2026-11-17 12:17:27'),
(38, 'catalog', 'delete', 'Delete Catalog Item', 'catalog.delete', '2026-11-17 12:17:27'),
(39, 'catalog', 'edit', 'Edit Style Catalog & Specifications', 'catalog.edit', '2026-11-17 12:17:27'),
(40, 'catalog', 'view', 'View Design Catalog & Style Codes', 'catalog.view', '2026-11-17 12:17:27'),
(41, 'customers', 'create', 'Add Customer Profile & Phone', 'customers.create', '2026-11-17 12:17:27'),
(42, 'customers', 'edit', 'Edit Customer Profile & Notes', 'customers.edit', '2026-11-17 12:17:27'),
(43, 'customers', 'view', 'View Customer Directory & 360 CRM', 'customers.view', '2026-11-17 12:17:27'),
(44, 'cutting', 'create', 'Create Cutting Batch & Roll Allocation', 'cutting.create', '2026-11-17 12:17:27'),
(45, 'cutting', 'delete', 'Delete Cutting Batch', 'cutting.delete', '2026-11-17 12:17:27'),
(46, 'cutting', 'edit', 'Edit Cutting Item Quantities & Bundles', 'cutting.edit', '2026-11-17 12:17:27'),
(47, 'cutting', 'view', 'View Cutting Batches & Lay Sheets', 'cutting.view', '2026-11-17 12:17:27'),
(48, 'damage', 'create', 'Log Damaged Stock / Water Defect', 'damage.create', '2026-11-17 12:17:27'),
(49, 'damage', 'resolve', 'Resolve & Classify Damaged Stock Action', 'damage.resolve', '2026-11-17 12:17:27'),
(50, 'damage', 'view', 'View Warehouse Damages & Write-offs', 'damage.view', '2026-11-17 12:17:27'),
(51, 'damages', 'create', 'Report Defective Fabric / Scrap', 'damages.create', '2026-11-17 12:17:27'),
(52, 'damages', 'delete', 'Delete Damage Record', 'damages.delete', '2026-11-17 12:17:27'),
(53, 'damages', 'edit', 'Edit Damage Records', 'damages.edit', '2026-11-17 12:17:27'),
(54, 'damages', 'view', 'View Production Fabric & Floor Damages', 'damages.view', '2026-11-17 12:17:27'),
(55, 'dashboard', 'view', 'View Executive Dashboard KPIs & Analytics', 'dashboard.view', '2026-11-17 12:17:27'),
(56, 'inventory', 'delete', 'Delete an available Production inventory QR piece', 'inventory.delete', '2026-11-17 12:17:27'),
(57, 'inventory', 'view', 'View Passed Finished Goods Inventory', 'inventory.view', '2026-11-17 12:17:27'),
(58, 'inward', 'create', 'Create Inward GRN / Cross-Check Scan', 'inward.create', '2026-11-17 12:17:27'),
(59, 'inward', 'print', 'Print Inward Gatepass & Labels', 'inward.print', '2026-11-17 12:17:27'),
(60, 'inward', 'verify', 'Verify & Confirm Inward Stock Placement', 'inward.verify', '2026-11-17 12:17:27'),
(61, 'inward', 'view', 'View Inward / GRN Shipments', 'inward.view', '2026-11-17 12:17:27'),
(62, 'labels', 'create', 'Create Garment Label Batch', 'labels.create', '2026-11-17 12:17:27'),
(63, 'labels', 'delete', 'Delete Garment Label Batch', 'labels.delete', '2026-11-17 12:17:27'),
(64, 'labels', 'edit', 'Edit Label Quantities & SKUs', 'labels.edit', '2026-11-17 12:17:27'),
(65, 'labels', 'print', 'Print Garment Barcodes & Care Tags', 'labels.print', '2026-11-17 12:17:27'),
(66, 'labels', 'view', 'View Label & Barcode Print Jobs', 'labels.view', '2026-11-17 12:17:27'),
(67, 'master', 'create', 'Add Master Attribute (Category, Size, Color, Unit)', 'master.create', '2026-11-17 12:17:27'),
(68, 'master', 'delete', 'Delete Master Attribute Entry', 'master.delete', '2026-11-17 12:17:27'),
(69, 'master', 'edit', 'Edit Master Attribute Name / Code', 'master.edit', '2026-11-17 12:17:27'),
(70, 'master', 'view', 'View Master Data (Categories, Sizes, Colors)', 'master.view', '2026-11-17 12:17:27'),
(71, 'notifications', 'delete', 'Delete / Clear Notifications', 'notifications.delete', '2026-11-17 12:17:27'),
(72, 'notifications', 'mark_read', 'Mark Notifications as Read', 'notifications.mark_read', '2026-11-17 12:17:27'),
(73, 'notifications', 'view', 'View System Notification Feed', 'notifications.view', '2026-11-17 12:17:27'),
(74, 'outward', 'create', 'Create Production Dispatch Challan', 'outward.create', '2026-11-17 12:17:27'),
(75, 'outward', 'dispatch', 'Dispatch Transport & Generate Gatepass', 'outward.dispatch', '2026-11-17 12:17:27'),
(76, 'outward', 'view', 'View Production Outward Dispatches', 'outward.view', '2026-11-17 12:17:27'),
(77, 'pos', 'create', 'Scan & Process Customer Bill / Checkout', 'pos.create', '2026-11-17 12:17:27'),
(78, 'pos', 'print', 'Print Thermal Sales Invoice Receipt', 'pos.print', '2026-11-17 12:17:27'),
(79, 'pos', 'view', 'Access POS Billing Counter Terminal', 'pos.view', '2026-11-17 12:17:27'),
(80, 'production', 'view', 'View Production Overview & Plant Throughput', 'production.view', '2026-11-17 12:17:27'),
(81, 'production_billing', 'create', 'Create Production Bills', 'production_billing.create', '2026-11-17 12:17:27'),
(82, 'production_billing', 'delete', 'Delete Production Bills', 'production_billing.delete', '2026-11-17 12:17:27'),
(83, 'production_billing', 'edit', 'Edit Production Bills', 'production_billing.edit', '2026-11-17 12:17:27'),
(84, 'production_billing', 'print', 'Print Production Bills', 'production_billing.print', '2026-11-17 12:17:27'),
(85, 'production_billing', 'view', 'View Production Bills', 'production_billing.view', '2026-11-17 12:17:27'),
(86, 'production_expenses', 'create', 'Record Machinery Spare / Plant Expense', 'production_expenses.create', '2026-11-17 12:17:27'),
(87, 'production_expenses', 'delete', 'Delete Expense Entry', 'production_expenses.delete', '2026-11-17 12:17:27'),
(88, 'production_expenses', 'edit', 'Edit Production Expense Entry', 'production_expenses.edit', '2026-11-17 12:17:27'),
(89, 'production_expenses', 'print', 'Print Expense Voucher', 'production_expenses.print', '2026-11-17 12:17:27'),
(90, 'production_expenses', 'view', 'View Plant Machinery & Daily Expenses', 'production_expenses.view', '2026-11-17 12:17:27'),
(91, 'programs', 'approve', 'Approve & Release Production Program', 'programs.approve', '2026-11-17 12:17:27'),
(92, 'programs', 'create', 'Create Production Work Orders', 'programs.create', '2026-11-17 12:17:27'),
(93, 'programs', 'delete', 'Delete Production Program', 'programs.delete', '2026-11-17 12:17:27'),
(94, 'programs', 'edit', 'Edit Program Specifications & Quantities', 'programs.edit', '2026-11-17 12:17:27'),
(95, 'programs', 'view', 'View Production Programs', 'programs.view', '2026-11-17 12:17:27'),
(96, 'purchases', 'create', 'Create Purchase Order / Third-Party Buy', 'purchases.create', '2026-11-17 12:17:27'),
(97, 'purchases', 'edit', 'Edit Purchase Quantities & Invoices', 'purchases.edit', '2026-11-17 12:17:27'),
(98, 'purchases', 'receive', 'Receive & Inward Purchase Order Goods', 'purchases.receive', '2026-11-17 12:17:27'),
(99, 'purchases', 'view', 'View Direct Purchases & Customer Orders', 'purchases.view', '2026-11-17 12:17:27'),
(100, 'purchases_returns', 'create', 'Create Purchase Return / Exchange request', 'purchases_returns.create', '2026-11-17 12:17:27'),
(101, 'purchases_returns', 'delete', 'Delete Purchase Return record', 'purchases_returns.delete', '2026-11-17 12:17:27'),
(102, 'purchases_returns', 'print', 'Print Purchase Return Invoice / Voucher', 'purchases_returns.print', '2026-11-17 12:17:27'),
(103, 'purchases_returns', 'process', 'Process and Restock/Settle Return', 'purchases_returns.process', '2026-11-17 12:17:27'),
(104, 'purchases_returns', 'view', 'View Purchases Returns & Exchanges', 'purchases_returns.view', '2026-11-17 12:17:27'),
(105, 'qc', 'approve', 'Final QC Clearance & Approval', 'qc.approve', '2026-11-17 12:17:27'),
(106, 'qc', 'create', 'Create QC Batch & Start Audit', 'qc.create', '2026-11-17 12:17:27'),
(107, 'qc', 'delete', 'Delete QC Inspection Record', 'qc.delete', '2026-11-17 12:17:27'),
(108, 'qc', 'edit', 'Inspect & Record Defects / Passed Pieces', 'qc.edit', '2026-11-17 12:17:27'),
(109, 'qc', 'view', 'View Quality Control (QC) Inspections', 'qc.view', '2026-11-17 12:17:27'),
(110, 'reports', 'export', 'Export Reports to Excel / CSV', 'reports.export', '2026-11-17 12:17:27'),
(111, 'reports', 'print', 'Print Formatted PDF Summary Reports', 'reports.print', '2026-11-17 12:17:27'),
(112, 'reports', 'view', 'View Analytics & Operational Reports', 'reports.view', '2026-11-17 12:17:27'),
(113, 'reports_inventory', 'export', 'Export Stock Valuation & Inventory report', 'reports_inventory.export', '2026-11-17 12:17:27'),
(114, 'reports_inventory', 'print', 'Print Inventory summary report', 'reports_inventory.print', '2026-11-17 12:17:27'),
(115, 'reports_inventory', 'view', 'View Enterprise Stock Valuation Report', 'reports_inventory.view', '2026-11-17 12:17:27'),
(116, 'reports_online', 'export', 'Export Combined Online report', 'reports_online.export', '2026-11-17 12:17:27'),
(117, 'reports_online', 'print', 'Print Combined Online report', 'reports_online.print', '2026-11-17 12:17:27'),
(118, 'reports_online', 'view', 'View Combined Online Channel Reports', 'reports_online.view', '2026-11-17 12:17:27'),
(119, 'reports_overview', 'export', 'Export Executive Overview data to Excel/CSV', 'reports_overview.export', '2026-11-17 12:17:27'),
(120, 'reports_overview', 'print', 'Print Executive Overview statement', 'reports_overview.print', '2026-11-17 12:17:27'),
(121, 'reports_overview', 'view', 'View Executive Overview and P&L Report', 'reports_overview.view', '2026-11-17 12:17:27'),
(122, 'reports_production', 'export', 'Export Production floor report', 'reports_production.export', '2026-11-17 12:17:27'),
(123, 'reports_production', 'print', 'Print Production summary report', 'reports_production.print', '2026-11-17 12:17:27'),
(124, 'reports_production', 'view', 'View Production Floor & Output Report', 'reports_production.view', '2026-11-17 12:17:27'),
(125, 'reports_profitability', 'export', 'Export Profitability report', 'reports_profitability.export', '2026-11-17 12:17:27'),
(126, 'reports_profitability', 'print', 'Print Profitability statement', 'reports_profitability.print', '2026-11-17 12:17:27'),
(127, 'reports_profitability', 'view', 'View Profitability and Margin Report', 'reports_profitability.view', '2026-11-17 12:17:27'),
(128, 'reports_purchase', 'export', 'Export Purchases and supplier procurement report', 'reports_purchase.export', '2026-11-17 12:17:27'),
(129, 'reports_purchase', 'print', 'Print Purchase procurement report', 'reports_purchase.print', '2026-11-17 12:17:27'),
(130, 'reports_purchase', 'view', 'View Direct Purchases & Supplier Report', 'reports_purchase.view', '2026-11-17 12:17:27'),
(131, 'reports_sales', 'export', 'Export Multi-Channel Sales report', 'reports_sales.export', '2026-11-17 12:17:27'),
(132, 'reports_sales', 'print', 'Print Multi-Channel Sales report', 'reports_sales.print', '2026-11-17 12:17:27'),
(133, 'reports_sales', 'view', 'View Multi-Channel Sales Performance Report', 'reports_sales.view', '2026-11-17 12:17:27'),
(134, 'reports_shop', 'export', 'Export Retail Shop sales report', 'reports_shop.export', '2026-11-17 12:17:27'),
(135, 'reports_shop', 'print', 'Print Retail Shop performance report', 'reports_shop.print', '2026-11-17 12:17:27'),
(136, 'reports_shop', 'view', 'View Retail Shop & POS Performance Report', 'reports_shop.view', '2026-11-17 12:17:27'),
(137, 'reports_warehouse', 'export', 'Export Warehouse storage and dispatch report', 'reports_warehouse.export', '2026-11-17 12:17:27'),
(138, 'reports_warehouse', 'print', 'Print Warehouse report', 'reports_warehouse.print', '2026-11-17 12:17:27'),
(139, 'reports_warehouse', 'view', 'View Warehouse Inward & Dispatch Report', 'reports_warehouse.view', '2026-11-17 12:17:27'),
(140, 'reports_website', 'export', 'Export Website eCommerce analytics', 'reports_website.export', '2026-11-17 12:17:27'),
(141, 'reports_website', 'print', 'Print Website report', 'reports_website.print', '2026-11-17 12:17:27'),
(142, 'reports_website', 'view', 'View Website eCommerce Analytics Report', 'reports_website.view', '2026-11-17 12:17:27'),
(143, 'reports_whatsapp', 'export', 'Export WhatsApp B2B sales data', 'reports_whatsapp.export', '2026-11-17 12:17:27'),
(144, 'reports_whatsapp', 'print', 'Print WhatsApp B2B report', 'reports_whatsapp.print', '2026-11-17 12:17:27'),
(145, 'reports_whatsapp', 'view', 'View WhatsApp B2B Sales Report', 'reports_whatsapp.view', '2026-11-17 12:17:27'),
(146, 'return_to_warehouse', 'create', 'Create Return to Warehouse Carton Manifest', 'return_to_warehouse.create', '2026-11-17 12:17:27'),
(147, 'return_to_warehouse', 'gatepass', 'Print Store RTV Gatepass & Transport Slip', 'return_to_warehouse.gatepass', '2026-11-17 12:17:27'),
(148, 'return_to_warehouse', 'print', 'Print Return Box QR Label', 'return_to_warehouse.print', '2026-11-17 12:17:27'),
(149, 'return_to_warehouse', 'view', 'View Store RTV Returns to Central Warehouse', 'return_to_warehouse.view', '2026-11-17 12:17:27'),
(150, 'returns', 'classify', 'Classify to Restock / RTV / Scrap', 'returns.classify', '2026-11-17 12:17:27'),
(151, 'returns', 'inspect', 'Inspect Return Items & Defect Reasons', 'returns.inspect', '2026-11-17 12:17:27'),
(152, 'returns', 'receive', 'Receive Return Parcel at Dock', 'returns.receive', '2026-11-17 12:17:27'),
(153, 'returns', 'view', 'View Warehouse Store Return Parcels', 'returns.view', '2026-11-17 12:17:27'),
(154, 'roles', 'create', 'Create New Custom Role', 'roles.create', '2026-11-17 12:17:27'),
(155, 'roles', 'delete', 'Delete Custom Role', 'roles.delete', '2026-11-17 12:17:27'),
(156, 'roles', 'edit', 'Edit Role & Configure Permissions Matrix', 'roles.edit', '2026-11-17 12:17:27'),
(157, 'roles', 'view', 'View Roles & Permissions Directory', 'roles.view', '2026-11-17 12:17:27'),
(158, 'sales', 'delete', 'Void Shop POS invoices and reverse stock and payments', 'sales.delete', '2026-11-17 12:17:27'),
(159, 'sales', 'edit', 'Edit Shop POS invoices', 'sales.edit', '2026-11-17 12:17:27'),
(160, 'sales', 'print', 'Reprint Customer Tax Invoice', 'sales.print', '2026-11-17 12:17:27'),
(161, 'sales', 'view', 'View Sales Register & Customer Invoices', 'sales.view', '2026-11-17 12:17:27'),
(162, 'sales_channels', 'create', 'Create WhatsApp B2B Order', 'sales_channels.create', '2026-11-17 12:17:27'),
(163, 'sales_channels', 'process', 'Process & Fulfill WhatsApp Order', 'sales_channels.process', '2026-11-17 12:17:27'),
(164, 'sales_channels', 'view', 'View WhatsApp B2B Customer Orders', 'sales_channels.view', '2026-11-17 12:17:27'),
(165, 'sellers', 'create', 'Create Fabric Seller / Job Worker', 'sellers.create', '2026-11-17 12:17:27'),
(166, 'sellers', 'delete', 'Delete Seller / Fabric Roll', 'sellers.delete', '2026-11-17 12:17:27'),
(167, 'sellers', 'edit', 'Edit Seller Details & Rates', 'sellers.edit', '2026-11-17 12:17:27'),
(168, 'sellers', 'view', 'View Fabric Sellers & Job Worker Directory', 'sellers.view', '2026-11-17 12:17:27'),
(169, 'settings', 'edit', 'Update System Settings, Tax & Branding', 'settings.edit', '2026-11-17 12:17:27'),
(170, 'settings', 'view', 'View System & Company Configurations', 'settings.view', '2026-11-17 12:17:27'),
(171, 'shop', 'view', 'View Retail Store Sales Dashboard', 'shop.view', '2026-11-17 12:17:27'),
(172, 'shop_cash', 'close_shift', 'Close Cash Register & Reconcile Till', 'shop_cash.close_shift', '2026-11-17 12:17:27'),
(173, 'shop_cash', 'create', 'Open Register Shift / Expense / Cash In / Bank Deposit', 'shop_cash.create', '2026-11-17 12:17:27'),
(174, 'shop_cash', 'delete', 'Void manual shop cash ledger transactions', 'shop_cash.delete', '2026-11-17 12:17:27'),
(175, 'shop_cash', 'edit', 'Edit manual shop cash ledger transactions', 'shop_cash.edit', '2026-11-17 12:17:27'),
(176, 'shop_cash', 'view', 'View Register Shift Flow & Till Status', 'shop_cash.view', '2026-11-17 12:17:27'),
(177, 'shop_cash', 'voucher', 'Print Petty Cash / Shift Thermal Slip', 'shop_cash.voucher', '2026-11-17 12:17:27'),
(178, 'shop_damage', 'create', 'Record shop damaged product', 'shop_damage.create', '2026-11-17 12:17:27'),
(179, 'shop_damage', 'delete', 'Delete shop damage record', 'shop_damage.delete', '2026-11-17 12:17:27'),
(180, 'shop_damage', 'edit', 'Edit shop damage record', 'shop_damage.edit', '2026-11-17 12:17:27'),
(181, 'shop_damage', 'process', 'Process salvage or write-off', 'shop_damage.process', '2026-11-17 12:17:27'),
(182, 'shop_damage', 'view', 'View shop floor defect and damaged items', 'shop_damage.view', '2026-11-17 12:17:27'),
(183, 'shop_inward', 'print', 'Print Store Inward Gatepass / Label', 'shop_inward.print', '2026-11-17 12:17:27'),
(184, 'shop_inward', 'receive', 'Scan & Receive Inward Carton Boxes', 'shop_inward.receive', '2026-11-17 12:17:27'),
(185, 'shop_inward', 'verify', 'Verify & Commit Stock to Store Shelf', 'shop_inward.verify', '2026-11-17 12:17:27'),
(186, 'shop_inward', 'view', 'View Store Inward Deliveries from Warehouse', 'shop_inward.view', '2026-11-17 12:17:27'),
(187, 'shop_requests', 'approve', 'Approve & Release Indented Stock', 'shop_requests.approve', '2026-11-17 12:17:27'),
(188, 'shop_requests', 'create', 'Raise Stock Indent Request', 'shop_requests.create', '2026-11-17 12:17:27'),
(189, 'shop_requests', 'view', 'View Shop Urgent Indent Requests', 'shop_requests.view', '2026-11-17 12:17:27'),
(190, 'shop_returns', 'create', 'Create Return Slip / Process Item Exchange', 'shop_returns.create', '2026-11-17 12:17:27'),
(191, 'shop_returns', 'print', 'Print Customer Credit Note / Refund Slip', 'shop_returns.print', '2026-11-17 12:17:27'),
(192, 'shop_returns', 'process', 'Approve Cash / Store Credit Refund', 'shop_returns.process', '2026-11-17 12:17:27'),
(193, 'shop_returns', 'view', 'View Customer Return Slips & Exchanges', 'shop_returns.view', '2026-11-17 12:17:27'),
(194, 'shop_stock', 'adjust', 'Request and approve shop stock adjustments', 'shop_stock.adjust', '2026-11-17 12:17:27'),
(195, 'shop_stock', 'edit', 'Edit Shop Product Selling Price & MRP', 'shop_stock.edit', '2026-11-17 12:17:27'),
(196, 'shop_stock', 'edit_price', 'Quick Edit Retail Selling Price and MRP', 'shop_stock.edit_price', '2026-11-17 12:17:27'),
(197, 'shop_stock', 'request', 'Request Stock Replenishment from Warehouse', 'shop_stock.request', '2026-11-17 12:17:27'),
(198, 'shop_stock', 'view', 'View Store Shelf Live Stock Breakdown', 'shop_stock.view', '2026-11-17 12:17:27'),
(199, 'staff', 'create', 'Create New Staff Member Account', 'staff.create', '2026-11-17 12:17:27'),
(200, 'staff', 'delete', 'Deactivate / Delete Staff Account', 'staff.delete', '2026-11-17 12:17:27'),
(201, 'staff', 'edit', 'Edit Staff Profile & Facility Assignment', 'staff.edit', '2026-11-17 12:17:27'),
(202, 'staff', 'view', 'View Staff Directory & Department Rosters', 'staff.view', '2026-11-17 12:17:27'),
(203, 'stitching', 'create', 'Create Stitching Batch / Assign Worker', 'stitching.create', '2026-11-17 12:17:27'),
(204, 'stitching', 'delete', 'Delete Stitching Batch', 'stitching.delete', '2026-11-17 12:17:27'),
(205, 'stitching', 'edit', 'Edit Stitching Quantities & Piece-Rates', 'stitching.edit', '2026-11-17 12:17:27'),
(206, 'stitching', 'view', 'View Stitching Batches & Lines', 'stitching.view', '2026-11-17 12:17:27'),
(207, 'stitching_wastage', 'create', 'Record Stitching Scrap Sale', 'stitching_wastage.create', '2026-11-17 12:17:27'),
(208, 'stitching_wastage', 'delete', 'Delete Stitching Scrap Record', 'stitching_wastage.delete', '2026-11-17 12:17:27'),
(209, 'stitching_wastage', 'edit', 'Edit Stitching Scrap Record', 'stitching_wastage.edit', '2026-11-17 12:17:27'),
(210, 'stitching_wastage', 'print', 'Print Stitching Scrap Voucher', 'stitching_wastage.print', '2026-11-17 12:17:27'),
(211, 'stitching_wastage', 'view', 'View Stitching Trimming Wastage', 'stitching_wastage.view', '2026-11-17 12:17:27'),
(212, 'stock', 'adjust', 'Execute Stock Count Adjustment', 'stock.adjust', '2026-11-17 12:17:27'),
(213, 'stock', 'edit', 'Edit warehouse stock prices (MRP and selling price)', 'stock.edit', '2026-11-17 12:17:27'),
(214, 'stock', 'view', 'View Warehouse Live Stock Ledger & Bins', 'stock.view', '2026-11-17 12:17:27'),
(215, 'warehouse', 'approve', 'Approve warehouse transfer', 'warehouse.approve', '2026-11-17 12:17:27'),
(216, 'warehouse', 'create', 'Create warehouse transfer', 'warehouse.create', '2026-11-17 12:17:27'),
(217, 'warehouse', 'delete', 'Delete warehouse', 'warehouse.delete', '2026-11-17 12:17:27'),
(218, 'warehouse', 'edit', 'Update warehouse details', 'warehouse.edit', '2026-11-17 12:17:27'),
(219, 'warehouse', 'receive', 'Receive warehouse transfer', 'warehouse.receive', '2026-11-17 12:17:27'),
(220, 'warehouse', 'view', 'View Warehouse Dashboard & Storage Bays', 'warehouse.view', '2026-11-17 12:17:27'),
(221, 'warehouse_expenses', 'create', 'Record warehouse freight/utility expense', 'warehouse_expenses.create', '2026-11-17 12:17:27'),
(222, 'warehouse_expenses', 'delete', 'Delete warehouse expense record', 'warehouse_expenses.delete', '2026-11-17 12:17:27'),
(223, 'warehouse_expenses', 'export', 'Export warehouse expense records', 'warehouse_expenses.export', '2026-11-17 12:17:27'),
(224, 'warehouse_expenses', 'print', 'Print warehouse expense statement', 'warehouse_expenses.print', '2026-11-17 12:17:27'),
(225, 'warehouse_expenses', 'view', 'View warehouse operational expenses', 'warehouse_expenses.view', '2026-11-17 12:17:27'),
(226, 'warehouse_expenses', 'voucher', 'Print warehouse expense payment voucher', 'warehouse_expenses.voucher', '2026-11-17 12:17:27'),
(227, 'warehouse_rework', 'create', 'Receive and Log damaged goods from warehouse for rework', 'warehouse_rework.create', '2026-11-17 12:17:27'),
(228, 'warehouse_rework', 'delete', 'Delete rework entries', 'warehouse_rework.delete', '2026-11-17 12:17:27'),
(229, 'warehouse_rework', 'dispatch', 'Re-dispatch repaired goods back to warehouse', 'warehouse_rework.dispatch', '2026-11-17 12:17:27'),
(230, 'warehouse_rework', 'edit', 'Update rework status, tailor assignment, and repair outcomes', 'warehouse_rework.edit', '2026-11-17 12:17:27'),
(231, 'warehouse_rework', 'view', 'View Warehouse Rework Requests and History', 'warehouse_rework.view', '2026-11-17 12:17:27'),
(232, 'wastage', 'create', 'Record Fabric Scrap Sale', 'wastage.create', '2026-11-17 12:17:27'),
(233, 'wastage', 'delete', 'Delete Wastage Sale', 'wastage.delete', '2026-11-17 12:17:27'),
(234, 'wastage', 'edit', 'Edit Scrap Sale Record', 'wastage.edit', '2026-11-17 12:17:27'),
(235, 'wastage', 'print', 'Print Wastage Receipt', 'wastage.print', '2026-11-17 12:17:27'),
(236, 'wastage', 'view', 'View Cutting Wastage Sales', 'wastage.view', '2026-11-17 12:17:27'),
(237, 'website_customers', 'view', 'View Website channel customers', 'website_customers.view', '2026-11-17 12:17:27'),
(238, 'website_damage', 'create', 'Log website damage entry', 'website_damage.create', '2026-11-17 12:17:27'),
(239, 'website_damage', 'outward', 'Dispatch website damaged goods outward', 'website_damage.outward', '2026-11-17 12:17:27'),
(240, 'website_damage', 'print', 'Print website damage gate pass and labels', 'website_damage.print', '2026-11-17 12:17:27'),
(241, 'website_damage', 'view', 'View website damaged goods', 'website_damage.view', '2026-11-17 12:17:27'),
(242, 'website_dashboard', 'view', 'View Website eCommerce Dashboard', 'website_dashboard.view', '2026-11-17 12:17:27'),
(243, 'website_expenses', 'create', 'Record website hosting/ad/courier expense', 'website_expenses.create', '2026-11-17 12:17:27'),
(244, 'website_expenses', 'delete', 'Delete website expense record', 'website_expenses.delete', '2026-11-17 12:17:27'),
(245, 'website_expenses', 'print', 'Print website expense report', 'website_expenses.print', '2026-11-17 12:17:27'),
(246, 'website_expenses', 'view', 'View website eCommerce expenses', 'website_expenses.view', '2026-11-17 12:17:27'),
(247, 'website_expenses', 'voucher', 'Print website expense voucher', 'website_expenses.voucher', '2026-11-17 12:17:27'),
(248, 'website_inventory', 'adjust', 'Adjust website stock levels', 'website_inventory.adjust', '2026-11-17 12:17:27'),
(249, 'website_inventory', 'view', 'View website dedicated inventory', 'website_inventory.view', '2026-11-17 12:17:27'),
(250, 'website_inward', 'receive', 'Receive and confirm website inward stock', 'website_inward.receive', '2026-11-17 12:17:27'),
(251, 'website_inward', 'view', 'View website stock inward and receipts', 'website_inward.view', '2026-11-17 12:17:27'),
(252, 'website_orders', 'create', 'Create Manual Website Order Entry', 'website_orders.create', '2026-11-17 12:17:27'),
(253, 'website_orders', 'process', 'Process & Dispatch Website Order', 'website_orders.process', '2026-11-17 12:17:27'),
(254, 'website_orders', 'view', 'View Website eCommerce Orders', 'website_orders.view', '2026-11-17 12:17:27'),
(255, 'website_return_to_wh', 'create', 'Create Website return to warehouse', 'website_return_to_wh.create', '2026-11-17 12:17:27'),
(256, 'website_return_to_wh', 'gatepass', 'Print Website return-to-warehouse gate pass', 'website_return_to_wh.gatepass', '2026-11-17 12:17:27'),
(257, 'website_return_to_wh', 'print', 'Print Website return-to-warehouse labels', 'website_return_to_wh.print', '2026-11-17 12:17:27'),
(258, 'website_return_to_wh', 'view', 'View Website returns to warehouse', 'website_return_to_wh.view', '2026-11-17 12:17:27'),
(259, 'website_returns', 'create', 'Initiate website return / exchange', 'website_returns.create', '2026-11-17 12:17:27'),
(260, 'website_returns', 'print', 'Print website return invoices and slips', 'website_returns.print', '2026-11-17 12:17:27'),
(261, 'website_returns', 'process', 'Process website return and restocking', 'website_returns.process', '2026-11-17 12:17:27'),
(262, 'website_returns', 'view', 'View website returns and RTO parcels', 'website_returns.view', '2026-11-17 12:17:27'),
(263, 'wh_batches', 'create', 'Create Warehouse Carton Batch', 'wh_batches.create', '2026-11-17 12:17:27'),
(264, 'wh_batches', 'print', 'Print Carton Batch QR Label', 'wh_batches.print', '2026-11-17 12:17:27'),
(265, 'wh_batches', 'view', 'View Warehouse Carton Batches', 'wh_batches.view', '2026-11-17 12:17:27'),
(266, 'wh_catalog', 'create', 'Add Catalog Style / Product Code', 'wh_catalog.create', '2026-11-17 12:17:27'),
(267, 'wh_catalog', 'delete', 'Delete Warehouse Catalog Item', 'wh_catalog.delete', '2026-11-17 12:17:27'),
(268, 'wh_catalog', 'edit', 'Edit Warehouse Catalog Pricing & SKUs', 'wh_catalog.edit', '2026-11-17 12:17:27'),
(269, 'wh_catalog', 'view', 'View Warehouse Catalog & Third-Party Styles', 'wh_catalog.view', '2026-11-17 12:17:27'),
(270, 'wh_labels', 'create', 'Generate Bulk QR Barcode Labels', 'wh_labels.create', '2026-11-17 12:17:27'),
(271, 'wh_labels', 'print', 'Print TSPL Thermal Barcode Stickers', 'wh_labels.print', '2026-11-17 12:17:27'),
(272, 'wh_labels', 'view', 'View Shelf & Carton QR Label Studio', 'wh_labels.view', '2026-11-17 12:17:27'),
(273, 'wh_sellers', 'create', 'Create Wholesale Supplier', 'wh_sellers.create', '2026-11-17 12:17:27'),
(274, 'wh_sellers', 'delete', 'Delete warehouse supplier', 'wh_sellers.delete', '2026-11-17 12:17:27'),
(275, 'wh_sellers', 'edit', 'Edit Supplier Profile & Invoices', 'wh_sellers.edit', '2026-11-17 12:17:27'),
(276, 'wh_sellers', 'view', 'View Warehouse Wholesale Suppliers', 'wh_sellers.view', '2026-11-17 12:17:27'),
(277, 'whatsapp_customers', 'view', 'View WhatsApp channel customers', 'whatsapp_customers.view', '2026-11-17 12:17:27'),
(278, 'whatsapp_damage', 'create', 'Log new WhatsApp damage entry', 'whatsapp_damage.create', '2026-11-17 12:17:27'),
(279, 'whatsapp_damage', 'delete', 'Delete quarantined WhatsApp damage records', 'whatsapp_damage.delete', '2026-11-17 12:17:27'),
(280, 'whatsapp_damage', 'edit', 'Edit quarantined WhatsApp damage details', 'whatsapp_damage.edit', '2026-11-17 12:17:27'),
(281, 'whatsapp_damage', 'outward', 'Dispatch damaged goods outward', 'whatsapp_damage.outward', '2026-11-17 12:17:27'),
(282, 'whatsapp_damage', 'print', 'Print damage gate pass and carton labels', 'whatsapp_damage.print', '2026-11-17 12:17:27'),
(283, 'whatsapp_damage', 'view', 'View WhatsApp defective and damaged items', 'whatsapp_damage.view', '2026-11-17 12:17:27'),
(284, 'whatsapp_dashboard', 'view', 'View WhatsApp Channel Dashboard', 'whatsapp_dashboard.view', '2026-11-17 12:17:27'),
(285, 'whatsapp_expenses', 'create', 'Record WhatsApp operational expense', 'whatsapp_expenses.create', '2026-11-17 12:17:27'),
(286, 'whatsapp_expenses', 'delete', 'Delete WhatsApp expense record', 'whatsapp_expenses.delete', '2026-11-17 12:17:27'),
(287, 'whatsapp_expenses', 'print', 'Print WhatsApp expense summary', 'whatsapp_expenses.print', '2026-11-17 12:17:27'),
(288, 'whatsapp_expenses', 'view', 'View WhatsApp channel expenses', 'whatsapp_expenses.view', '2026-11-17 12:17:27'),
(289, 'whatsapp_expenses', 'voucher', 'Generate and print expense voucher', 'whatsapp_expenses.voucher', '2026-11-17 12:17:27'),
(290, 'whatsapp_inventory', 'adjust', 'Adjust WhatsApp stock levels', 'whatsapp_inventory.adjust', '2026-11-17 12:17:27'),
(291, 'whatsapp_inventory', 'edit', 'Edit WhatsApp inventory details & stock prices', 'whatsapp_inventory.edit', '2026-11-17 12:17:27'),
(292, 'whatsapp_inventory', 'view', 'View WhatsApp channel inventory', 'whatsapp_inventory.view', '2026-11-17 12:17:27'),
(293, 'whatsapp_inward', 'create', 'Create WhatsApp inward receipt', 'whatsapp_inward.create', '2026-11-17 12:17:27'),
(294, 'whatsapp_inward', 'receive', 'Receive and confirm inward stock', 'whatsapp_inward.receive', '2026-11-17 12:17:27'),
(295, 'whatsapp_inward', 'view', 'View WhatsApp stock inward and receipts', 'whatsapp_inward.view', '2026-11-17 12:17:27'),
(296, 'whatsapp_orders', 'create', 'Create new WhatsApp B2B order', 'whatsapp_orders.create', '2026-11-17 12:17:27'),
(297, 'whatsapp_orders', 'process', 'Process and dispatch WhatsApp order', 'whatsapp_orders.process', '2026-11-17 12:17:27'),
(298, 'whatsapp_orders', 'view', 'View WhatsApp B2B Orders and history', 'whatsapp_orders.view', '2026-11-17 12:17:27'),
(299, 'whatsapp_return_to_wh', 'create', 'Create WhatsApp return to warehouse', 'whatsapp_return_to_wh.create', '2026-11-17 12:17:27'),
(300, 'whatsapp_return_to_wh', 'gatepass', 'Print WhatsApp return-to-warehouse gate pass', 'whatsapp_return_to_wh.gatepass', '2026-11-17 12:17:27'),
(301, 'whatsapp_return_to_wh', 'print', 'Print WhatsApp return-to-warehouse labels', 'whatsapp_return_to_wh.print', '2026-11-17 12:17:27'),
(302, 'whatsapp_return_to_wh', 'view', 'View WhatsApp returns to warehouse', 'whatsapp_return_to_wh.view', '2026-11-17 12:17:27'),
(303, 'whatsapp_returns', 'create', 'Create return request for WhatsApp', 'whatsapp_returns.create', '2026-11-17 12:17:27'),
(304, 'whatsapp_returns', 'print', 'Print WhatsApp return credit notes', 'whatsapp_returns.print', '2026-11-17 12:17:27'),
(305, 'whatsapp_returns', 'process', 'Process return and restock/refund', 'whatsapp_returns.process', '2026-11-17 12:17:27'),
(306, 'whatsapp_returns', 'view', 'View WhatsApp returns and exchanges', 'whatsapp_returns.view', '2026-11-17 12:17:27'),
(307, 'variants', 'view', 'View Product Variants & QR Identities', 'variants.view', '2026-11-17 12:17:29'),
(308, 'variants', 'create', 'Create New Product Variant', 'variants.create', '2026-11-17 12:17:29'),
(309, 'variants', 'edit', 'Edit Product Variant Details & MRP', 'variants.edit', '2026-11-17 12:17:29'),
(310, 'variants', 'delete', 'Delete Product Variant', 'variants.delete', '2026-11-17 12:17:29'),
(311, 'qr_generation', 'view', 'View QR Code Generations & Batches', 'qr_generation.view', '2026-11-17 12:17:29'),
(312, 'qr_generation', 'create', 'Generate QR Code Batches', 'qr_generation.create', '2026-11-17 12:17:29'),
(313, 'qr_generation', 'edit', 'Update QR Generation Details', 'qr_generation.edit', '2026-11-17 12:17:29'),
(314, 'qr_generation', 'delete', 'Delete QR Generation Batch', 'qr_generation.delete', '2026-11-17 12:17:29'),
(315, 'production_purchases', 'view', 'View Production Purchases & Supplier Bills', 'production_purchases.view', '2026-11-18 17:08:51'),
(316, 'production_purchases', 'create', 'Create Production Purchase Record', 'production_purchases.create', '2026-11-18 17:08:51'),
(317, 'production_purchases', 'edit', 'Edit Production Purchase & Invoices', 'production_purchases.edit', '2026-11-18 17:08:51'),
(318, 'production_purchases', 'delete', 'Delete Production Purchase Record', 'production_purchases.delete', '2026-11-18 17:08:51'),
(319, 'production_returns', 'view', 'View Production Customer Returns & Exchanges', 'production_returns.view', '2026-11-18 17:08:51'),
(320, 'production_returns', 'create', 'Process Return Items & Credit Notes', 'production_returns.create', '2026-11-18 17:08:51'),
(321, 'production_returns', 'edit', 'Edit Return Status & Restock Orders', 'production_returns.edit', '2026-11-18 17:08:51'),
(322, 'production_returns', 'delete', 'Cancel & Delete Production Returns', 'production_returns.delete', '2026-11-18 17:08:51'),
(323, 'production_stock_requests', 'view', 'View Warehouse Requisitions & Indents', 'production_stock_requests.view', '2026-11-18 17:08:51'),
(324, 'production_stock_requests', 'create', 'Create Stock Request Indents', 'production_stock_requests.create', '2026-11-18 17:08:51'),
(325, 'production_stock_requests', 'edit', 'Edit & Approve Requisition Quantities', 'production_stock_requests.edit', '2026-11-18 17:08:51'),
(326, 'production_stock_requests', 'delete', 'Cancel & Reject Requisitions', 'production_stock_requests.delete', '2026-11-18 17:08:51'),
(327, 'asset_register', 'view', 'View Asset Register & Machinery Details', 'asset_register.view', '2026-11-18 17:08:51'),
(328, 'asset_register', 'create', 'Add New Machinery & Hardware Assets', 'asset_register.create', '2026-11-18 17:08:51'),
(329, 'asset_register', 'edit', 'Edit Asset Specifications & Valuation', 'asset_register.edit', '2026-11-18 17:08:51'),
(330, 'asset_register', 'delete', 'Archive & Delete Equipment Assets', 'asset_register.delete', '2026-11-18 17:08:51'),
(331, 'asset_dashboard', 'view', 'View Asset Overview KPIs & Valuation', 'asset_dashboard.view', '2026-11-18 17:08:51'),
(332, 'asset_dashboard', 'create', 'Create Asset Widgets & Custom KPIs', 'asset_dashboard.create', '2026-11-18 17:08:51'),
(333, 'asset_dashboard', 'edit', 'Edit Asset Valuation Metrics', 'asset_dashboard.edit', '2026-11-18 17:08:51'),
(334, 'asset_dashboard', 'delete', 'Delete Custom Valuation Metrics', 'asset_dashboard.delete', '2026-11-18 17:08:51'),
(335, 'whatsapp_returned_products', 'view', 'View WhatsApp Returned Parcels & Products', 'whatsapp_returned_products.view', '2026-11-18 17:08:51'),
(336, 'whatsapp_returned_products', 'create', 'Inward & Log WhatsApp Returns', 'whatsapp_returned_products.create', '2026-11-18 17:08:51'),
(337, 'whatsapp_returned_products', 'edit', 'Inspect & Re-shelve WhatsApp Returns', 'whatsapp_returned_products.edit', '2026-11-18 17:08:51'),
(338, 'whatsapp_returned_products', 'delete', 'Delete WhatsApp Return Audit Logs', 'whatsapp_returned_products.delete', '2026-11-18 17:08:51'),
(339, 'dashboard', 'create', 'Create infrastructure facilities (Production Units, Warehouses, Shops, Channels)', 'dashboard.create', '2026-11-18 17:08:51'),
(340, 'dashboard', 'edit', 'Edit infrastructure facilities and dashboard configurations', 'dashboard.edit', '2026-11-18 17:08:51'),
(341, 'dashboard', 'delete', 'Delete infrastructure facilities and records', 'dashboard.delete', '2026-11-18 17:08:51'),
(342, 'batches', 'edit', 'Edit Packaging Batches & Box Allocations', 'batches.edit', '2026-11-18 17:08:51'),
(343, 'batches', 'delete', 'Delete or Cancel Packaging Batches', 'batches.delete', '2026-11-18 17:08:51'),
(344, 'allocation_outward', 'edit', 'Edit Outward Delivery Manifest & Vehicle Notes', 'allocation_outward.edit', '2026-11-18 17:08:51'),
(345, 'allocation_outward', 'delete', 'Delete & Cancel Outward Dispatch Record', 'allocation_outward.delete', '2026-11-18 17:08:51'),
(346, 'shop_inward', 'create', 'Scan, Receive & Verify Shop Inward Shipments', 'shop_inward.create', '2026-11-18 17:08:51'),
(347, 'shop_inward', 'edit', 'Edit Shop Inward Receipts & Notes', 'shop_inward.edit', '2026-11-18 17:08:51'),
(348, 'shop_inward', 'delete', 'Cancel & Delete Shop Inward Records', 'shop_inward.delete', '2026-11-18 17:08:51'),
(349, 'audit', 'create', 'Export Audit Records & Log System Events', 'audit.create', '2026-11-18 17:08:51'),
(350, 'audit', 'edit', 'Archive, Filter & Annotate Audit Trail', 'audit.edit', '2026-11-18 17:08:51'),
(351, 'audit', 'delete', 'Purge & Clear System Activity Logs', 'audit.delete', '2026-11-18 17:08:51'),
(352, 'settings', 'create', 'Create System Configurations & Profile Presets', 'settings.create', '2026-11-18 17:08:51'),
(353, 'settings', 'delete', 'Reset & Delete System Configurations', 'settings.delete', '2026-11-18 17:08:51'),
(354, 'backup', 'edit', 'Restore Database Snapshots & Edit Configurations', 'backup.edit', '2026-11-18 17:08:51'),
(355, 'production', 'create', 'Create Production Records & Work Orders', 'production.create', '2026-11-18 17:08:51'),
(356, 'production', 'edit', 'Edit Production Records & Configurations', 'production.edit', '2026-11-18 17:08:51'),
(357, 'production', 'delete', 'Delete Production Records', 'production.delete', '2026-11-18 17:08:51'),
(358, 'inventory', 'create', 'Create Inventory Entries & Lot Records', 'inventory.create', '2026-11-18 17:08:51'),
(359, 'inventory', 'edit', 'Edit Inventory Records & Bay Assignments', 'inventory.edit', '2026-11-18 17:08:51'),
(360, 'outward', 'edit', 'Edit Outward Challans & Delivery Notes', 'outward.edit', '2026-11-18 17:08:51'),
(361, 'outward', 'delete', 'Delete Outward Challans & Dispatch Records', 'outward.delete', '2026-11-18 17:08:51'),
(362, 'inward', 'edit', 'Edit GRN & Inward Receipts', 'inward.edit', '2026-11-18 17:08:51'),
(363, 'inward', 'delete', 'Delete GRN & Inward Records', 'inward.delete', '2026-11-18 17:08:51'),
(364, 'stock', 'create', 'Create Stock Entries & Bin Records', 'stock.create', '2026-11-18 17:08:51'),
(365, 'stock', 'delete', 'Delete Stock Records & Bin Entries', 'stock.delete', '2026-11-18 17:08:51'),
(366, 'stock_adjustments', 'view', 'View Stock Adjustments & Physical Count History', 'stock_adjustments.view', '2026-11-18 17:08:51'),
(367, 'stock_adjustments', 'create', 'Create & Apply Stock Adjustments', 'stock_adjustments.create', '2026-11-18 17:08:51'),
(368, 'stock_adjustments', 'edit', 'Edit Stock Adjustment Metadata & Reason Notes', 'stock_adjustments.edit', '2026-11-18 17:08:51'),
(369, 'stock_adjustments', 'delete', 'Delete & Reject Pending Stock Adjustments', 'stock_adjustments.delete', '2026-11-18 17:08:51'),
(370, 'wh_labels', 'edit', 'Edit Label Configurations & Print Batches', 'wh_labels.edit', '2026-11-18 17:08:51'),
(371, 'wh_labels', 'delete', 'Delete Label Batches & QR Sticker Records', 'wh_labels.delete', '2026-11-18 17:08:51'),
(372, 'purchases', 'delete', 'Delete Purchase Orders & Procurement Records', 'purchases.delete', '2026-11-18 17:08:51'),
(373, 'purchases_returns', 'edit', 'Edit Return Records & Credit Notes', 'purchases_returns.edit', '2026-11-18 17:08:51'),
(374, 'allocation', 'edit', 'Edit Allocation Plans & Distribution Schedules', 'allocation.edit', '2026-11-18 17:08:52'),
(375, 'allocation', 'delete', 'Delete Allocation Plans & Picking Records', 'allocation.delete', '2026-11-18 17:08:52'),
(376, 'damage', 'edit', 'Edit Damage Records & Salvage Status', 'damage.edit', '2026-11-18 17:08:52'),
(377, 'damage', 'delete', 'Delete Warehouse Damage Records', 'damage.delete', '2026-11-18 17:08:52'),
(378, 'returns', 'create', 'Process Shop Return Packages & Restock', 'returns.create', '2026-11-18 17:08:52'),
(379, 'returns', 'edit', 'Edit Return Records & Restock Evaluations', 'returns.edit', '2026-11-18 17:08:52'),
(380, 'returns', 'delete', 'Delete Return Package Records', 'returns.delete', '2026-11-18 17:08:52'),
(381, 'shop_requests', 'edit', 'Edit Shop Request Quantities & Priorities', 'shop_requests.edit', '2026-11-18 17:08:52'),
(382, 'shop_requests', 'delete', 'Delete Shop Replenishment Requests', 'shop_requests.delete', '2026-11-18 17:08:52'),
(383, 'warehouse_expenses', 'edit', 'Edit Warehouse Expense Records', 'warehouse_expenses.edit', '2026-11-18 17:08:52'),
(384, 'sales_channels', 'edit', 'Edit Sales Channel Configurations', 'sales_channels.edit', '2026-11-18 17:08:52'),
(385, 'sales_channels', 'delete', 'Delete & Disconnect Sales Channels', 'sales_channels.delete', '2026-11-18 17:08:52'),
(386, 'whatsapp_dashboard', 'create', 'Create WhatsApp Dashboard Widgets', 'whatsapp_dashboard.create', '2026-11-18 17:08:52'),
(387, 'whatsapp_dashboard', 'edit', 'Edit WhatsApp Dashboard Configurations', 'whatsapp_dashboard.edit', '2026-11-18 17:08:52'),
(388, 'whatsapp_dashboard', 'delete', 'Delete WhatsApp Dashboard Records', 'whatsapp_dashboard.delete', '2026-11-18 17:08:52'),
(389, 'whatsapp_inward', 'edit', 'Edit WhatsApp Inward Records', 'whatsapp_inward.edit', '2026-11-18 17:08:52'),
(390, 'whatsapp_inward', 'delete', 'Delete WhatsApp Inward Records', 'whatsapp_inward.delete', '2026-11-18 17:08:52'),
(391, 'whatsapp_inventory', 'create', 'Create WhatsApp Inventory Entries', 'whatsapp_inventory.create', '2026-11-18 17:08:52'),
(392, 'whatsapp_inventory', 'delete', 'Delete WhatsApp Inventory Records', 'whatsapp_inventory.delete', '2026-11-18 17:08:52'),
(393, 'whatsapp_orders', 'edit', 'Edit WhatsApp Orders & Processing Status', 'whatsapp_orders.edit', '2026-11-18 17:08:52'),
(394, 'whatsapp_orders', 'delete', 'Delete WhatsApp Order Records', 'whatsapp_orders.delete', '2026-11-18 17:08:52'),
(395, 'whatsapp_returns', 'edit', 'Edit Return Records & Credit Notes', 'whatsapp_returns.edit', '2026-11-18 17:08:52'),
(396, 'whatsapp_returns', 'delete', 'Delete WhatsApp Return Records', 'whatsapp_returns.delete', '2026-11-18 17:08:52'),
(397, 'whatsapp_return_to_wh', 'edit', 'Edit WhatsApp-to-Warehouse Return Details', 'whatsapp_return_to_wh.edit', '2026-11-18 17:08:52'),
(398, 'whatsapp_return_to_wh', 'delete', 'Delete WhatsApp-to-Warehouse Return Records', 'whatsapp_return_to_wh.delete', '2026-11-18 17:08:52'),
(399, 'whatsapp_expenses', 'edit', 'Edit WhatsApp Expense Records', 'whatsapp_expenses.edit', '2026-11-18 17:08:52'),
(400, 'website_dashboard', 'create', 'Create Website Dashboard Configurations', 'website_dashboard.create', '2026-11-18 17:08:52'),
(401, 'website_dashboard', 'edit', 'Edit Website Dashboard Settings', 'website_dashboard.edit', '2026-11-18 17:08:52'),
(402, 'website_dashboard', 'delete', 'Delete Website Dashboard Records', 'website_dashboard.delete', '2026-11-18 17:08:52'),
(403, 'website_inward', 'edit', 'Edit Website Inward Records & GRNs', 'website_inward.edit', '2026-11-18 17:08:52'),
(404, 'website_inward', 'delete', 'Delete Website Inward Records', 'website_inward.delete', '2026-11-18 17:08:52'),
(405, 'website_inventory', 'create', 'Create Website Inventory Entries', 'website_inventory.create', '2026-11-18 17:08:52'),
(406, 'website_inventory', 'delete', 'Delete Website Inventory Records', 'website_inventory.delete', '2026-11-18 17:08:52'),
(407, 'website_damage', 'edit', 'Edit Website Damage Records', 'website_damage.edit', '2026-11-18 17:08:52'),
(408, 'website_returns', 'edit', 'Edit Website Return & Refund Records', 'website_returns.edit', '2026-11-18 17:08:52'),
(409, 'website_returns', 'delete', 'Delete Website Return Records', 'website_returns.delete', '2026-11-18 17:08:52'),
(410, 'website_return_to_wh', 'edit', 'Edit Website-to-Warehouse Return Details', 'website_return_to_wh.edit', '2026-11-18 17:08:52'),
(411, 'website_return_to_wh', 'delete', 'Delete Website-to-Warehouse Return Records', 'website_return_to_wh.delete', '2026-11-18 17:08:52'),
(412, 'website_customers', 'create', 'Create Website Customer Profiles', 'website_customers.create', '2026-11-18 17:08:52'),
(413, 'website_customers', 'edit', 'Edit Website Customer Details', 'website_customers.edit', '2026-11-18 17:08:52'),
(414, 'website_customers', 'delete', 'Delete Website Customer Records', 'website_customers.delete', '2026-11-18 17:08:52'),
(415, 'website_expenses', 'edit', 'Edit Website Expense Records', 'website_expenses.edit', '2026-11-18 17:08:52'),
(416, 'shop', 'create', 'Create Shop Configurations & Counter Records', 'shop.create', '2026-11-18 17:08:52'),
(417, 'shop', 'edit', 'Edit Shop Dashboard & Counter Configurations', 'shop.edit', '2026-11-18 17:08:52'),
(418, 'shop', 'delete', 'Delete Shop Records & Counter Entries', 'shop.delete', '2026-11-18 17:08:52'),
(419, 'shop_stock', 'create', 'Create Shop Stock Entries & Rack Records', 'shop_stock.create', '2026-11-18 17:08:52'),
(420, 'shop_stock', 'delete', 'Delete Shop Stock Records', 'shop_stock.delete', '2026-11-18 17:08:52'),
(421, 'pos', 'edit', 'Edit POS Invoices & Payment Records', 'pos.edit', '2026-11-18 17:08:52'),
(422, 'pos', 'delete', 'Delete POS Transactions & Void Invoices', 'pos.delete', '2026-11-18 17:08:52'),
(423, 'sales', 'create', 'Create Sales Records & Invoice Reprints', 'sales.create', '2026-11-18 17:08:52'),
(424, 'customers', 'delete', 'Delete Customer Profiles & Records', 'customers.delete', '2026-11-18 17:08:52'),
(425, 'shop_returns', 'edit', 'Edit Return Records & Credit Notes', 'shop_returns.edit', '2026-11-18 17:08:52'),
(426, 'shop_returns', 'delete', 'Delete Customer Return Records', 'shop_returns.delete', '2026-11-18 17:08:52'),
(427, 'return_to_warehouse', 'edit', 'Edit Return-to-Warehouse Records', 'return_to_warehouse.edit', '2026-11-18 17:08:52'),
(428, 'return_to_warehouse', 'delete', 'Delete Return-to-Warehouse Records', 'return_to_warehouse.delete', '2026-11-18 17:08:52'),
(429, 'asset_scanner', 'create', 'Create Asset QR Scan Records', 'asset_scanner.create', '2026-11-18 17:08:52'),
(430, 'asset_scanner', 'edit', 'Edit Asset Scan & Inspection Records', 'asset_scanner.edit', '2026-11-18 17:08:52'),
(431, 'asset_scanner', 'delete', 'Delete Asset Scan Records', 'asset_scanner.delete', '2026-11-18 17:08:52'),
(432, 'asset_transfers', 'edit', 'Edit Asset Transfer Details', 'asset_transfers.edit', '2026-11-18 17:08:52'),
(433, 'asset_transfers', 'delete', 'Delete Asset Transfer Records', 'asset_transfers.delete', '2026-11-18 17:08:52'),
(434, 'asset_maintenance', 'delete', 'Delete Maintenance Records & Service Logs', 'asset_maintenance.delete', '2026-11-18 17:08:52'),
(435, 'asset_bulk_labels', 'create', 'Create & Print Asset QR Label Batches', 'asset_bulk_labels.create', '2026-11-18 17:08:52'),
(436, 'asset_bulk_labels', 'edit', 'Edit Asset Label Configurations', 'asset_bulk_labels.edit', '2026-11-18 17:08:52'),
(437, 'asset_bulk_labels', 'delete', 'Delete Asset Label Batches', 'asset_bulk_labels.delete', '2026-11-18 17:08:52'),
(438, 'notifications', 'create', 'Create System Notification Records', 'notifications.create', '2026-11-18 17:08:52'),
(439, 'notifications', 'edit', 'Mark & Archive Notifications', 'notifications.edit', '2026-11-18 17:08:52'),
(440, 'concerns', 'view', 'View Business Concerns & Legal Entities Directory', 'concerns.view', '2026-11-18 17:24:49'),
(441, 'concerns', 'create', 'Register New Business Concern & Assign Staff', 'concerns.create', '2026-11-18 17:24:49'),
(442, 'concerns', 'edit', 'Edit Business Concern Profile & Staff Assignments', 'concerns.edit', '2026-11-18 17:24:49'),
(443, 'concerns', 'delete', 'Deactivate / Remove Business Concern', 'concerns.delete', '2026-11-18 17:24:49'),
(444, 'production_purchase_mapping', 'view', 'View Product Mapping', 'production_purchase_mapping.view', '2026-11-19 12:00:10'),
(445, 'production_purchase_mapping', 'create', 'Add Purchase Lines to Product Mapping', 'production_purchase_mapping.create', '2026-11-19 12:00:10'),
(446, 'production_purchase_mapping', 'edit', 'Map Products to Catalog Variants', 'production_purchase_mapping.edit', '2026-11-19 12:00:10'),
(447, 'production_purchase_mapping', 'delete', 'Remove Product Mapping Lines', 'production_purchase_mapping.delete', '2026-11-19 12:00:10')
ON DUPLICATE KEY UPDATE `module`=VALUES(`module`), `action`=VALUES(`action`);

-- 4. Role Permissions Link (599 assignments)
INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`, `created_at`) VALUES
(1, 1, 1, '2026-11-17 12:17:27'),
(2, 1, 2, '2026-11-17 12:17:27'),
(3, 1, 3, '2026-11-17 12:17:27'),
(4, 1, 4, '2026-11-17 12:17:27'),
(5, 1, 5, '2026-11-17 12:17:27'),
(6, 1, 6, '2026-11-17 12:17:27'),
(7, 1, 7, '2026-11-17 12:17:27'),
(8, 1, 8, '2026-11-17 12:17:27'),
(9, 1, 9, '2026-11-17 12:17:27'),
(10, 1, 10, '2026-11-17 12:17:27'),
(11, 1, 11, '2026-11-17 12:17:27'),
(12, 1, 12, '2026-11-17 12:17:27'),
(13, 1, 13, '2026-11-17 12:17:27'),
(14, 1, 14, '2026-11-17 12:17:27'),
(15, 1, 15, '2026-11-17 12:17:27'),
(16, 1, 16, '2026-11-17 12:17:27'),
(17, 1, 17, '2026-11-17 12:17:27'),
(18, 1, 18, '2026-11-17 12:17:27'),
(19, 1, 19, '2026-11-17 12:17:27'),
(20, 1, 20, '2026-11-17 12:17:27'),
(21, 1, 21, '2026-11-17 12:17:27'),
(22, 1, 22, '2026-11-17 12:17:27'),
(23, 1, 23, '2026-11-17 12:17:27'),
(24, 1, 24, '2026-11-17 12:17:27'),
(25, 1, 25, '2026-11-17 12:17:27'),
(26, 1, 26, '2026-11-17 12:17:27'),
(27, 1, 27, '2026-11-17 12:17:27'),
(28, 1, 28, '2026-11-17 12:17:27'),
(29, 1, 29, '2026-11-17 12:17:27'),
(30, 1, 30, '2026-11-17 12:17:27'),
(31, 1, 31, '2026-11-17 12:17:27'),
(32, 1, 32, '2026-11-17 12:17:27'),
(33, 1, 33, '2026-11-17 12:17:27'),
(34, 1, 34, '2026-11-17 12:17:27'),
(35, 1, 35, '2026-11-17 12:17:27'),
(36, 1, 36, '2026-11-17 12:17:27'),
(37, 1, 37, '2026-11-17 12:17:27'),
(38, 1, 38, '2026-11-17 12:17:27'),
(39, 1, 39, '2026-11-17 12:17:27'),
(40, 1, 40, '2026-11-17 12:17:27'),
(41, 1, 41, '2026-11-17 12:17:27'),
(42, 1, 42, '2026-11-17 12:17:27'),
(43, 1, 43, '2026-11-17 12:17:27'),
(44, 1, 44, '2026-11-17 12:17:27'),
(45, 1, 45, '2026-11-17 12:17:27'),
(46, 1, 46, '2026-11-17 12:17:27'),
(47, 1, 47, '2026-11-17 12:17:27'),
(48, 1, 48, '2026-11-17 12:17:27'),
(49, 1, 49, '2026-11-17 12:17:27'),
(50, 1, 50, '2026-11-17 12:17:27'),
(51, 1, 51, '2026-11-17 12:17:27'),
(52, 1, 52, '2026-11-17 12:17:27'),
(53, 1, 53, '2026-11-17 12:17:27'),
(54, 1, 54, '2026-11-17 12:17:27'),
(55, 1, 55, '2026-11-17 12:17:27'),
(56, 1, 56, '2026-11-17 12:17:27'),
(57, 1, 57, '2026-11-17 12:17:27'),
(58, 1, 58, '2026-11-17 12:17:27'),
(59, 1, 59, '2026-11-17 12:17:27'),
(60, 1, 60, '2026-11-17 12:17:27'),
(61, 1, 61, '2026-11-17 12:17:27'),
(62, 1, 62, '2026-11-17 12:17:27'),
(63, 1, 63, '2026-11-17 12:17:27'),
(64, 1, 64, '2026-11-17 12:17:27'),
(65, 1, 65, '2026-11-17 12:17:27'),
(66, 1, 66, '2026-11-17 12:17:27'),
(67, 1, 67, '2026-11-17 12:17:27'),
(68, 1, 68, '2026-11-17 12:17:27'),
(69, 1, 69, '2026-11-17 12:17:27'),
(70, 1, 70, '2026-11-17 12:17:27'),
(71, 1, 71, '2026-11-17 12:17:27'),
(72, 1, 72, '2026-11-17 12:17:27'),
(73, 1, 73, '2026-11-17 12:17:27'),
(74, 1, 74, '2026-11-17 12:17:27'),
(75, 1, 75, '2026-11-17 12:17:27'),
(76, 1, 76, '2026-11-17 12:17:27'),
(77, 1, 77, '2026-11-17 12:17:27'),
(78, 1, 78, '2026-11-17 12:17:27'),
(79, 1, 79, '2026-11-17 12:17:27'),
(80, 1, 80, '2026-11-17 12:17:27'),
(81, 1, 81, '2026-11-17 12:17:27'),
(82, 1, 82, '2026-11-17 12:17:27'),
(83, 1, 83, '2026-11-17 12:17:27'),
(84, 1, 84, '2026-11-17 12:17:27'),
(85, 1, 85, '2026-11-17 12:17:27'),
(86, 1, 86, '2026-11-17 12:17:27'),
(87, 1, 87, '2026-11-17 12:17:27'),
(88, 1, 88, '2026-11-17 12:17:27'),
(89, 1, 89, '2026-11-17 12:17:27'),
(90, 1, 90, '2026-11-17 12:17:27'),
(91, 1, 91, '2026-11-17 12:17:27'),
(92, 1, 92, '2026-11-17 12:17:27'),
(93, 1, 93, '2026-11-17 12:17:27'),
(94, 1, 94, '2026-11-17 12:17:27'),
(95, 1, 95, '2026-11-17 12:17:27'),
(96, 1, 96, '2026-11-17 12:17:27'),
(97, 1, 97, '2026-11-17 12:17:27'),
(98, 1, 98, '2026-11-17 12:17:27'),
(99, 1, 99, '2026-11-17 12:17:27'),
(100, 1, 100, '2026-11-17 12:17:27'),
(101, 1, 101, '2026-11-17 12:17:27'),
(102, 1, 102, '2026-11-17 12:17:27'),
(103, 1, 103, '2026-11-17 12:17:27'),
(104, 1, 104, '2026-11-17 12:17:27'),
(105, 1, 105, '2026-11-17 12:17:27'),
(106, 1, 106, '2026-11-17 12:17:27'),
(107, 1, 107, '2026-11-17 12:17:27'),
(108, 1, 108, '2026-11-17 12:17:27'),
(109, 1, 109, '2026-11-17 12:17:27'),
(110, 1, 110, '2026-11-17 12:17:27'),
(111, 1, 111, '2026-11-17 12:17:27'),
(112, 1, 112, '2026-11-17 12:17:27'),
(113, 1, 113, '2026-11-17 12:17:27'),
(114, 1, 114, '2026-11-17 12:17:27'),
(115, 1, 115, '2026-11-17 12:17:27'),
(116, 1, 116, '2026-11-17 12:17:27'),
(117, 1, 117, '2026-11-17 12:17:27'),
(118, 1, 118, '2026-11-17 12:17:27'),
(119, 1, 119, '2026-11-17 12:17:27'),
(120, 1, 120, '2026-11-17 12:17:27'),
(121, 1, 121, '2026-11-17 12:17:27'),
(122, 1, 122, '2026-11-17 12:17:27'),
(123, 1, 123, '2026-11-17 12:17:27'),
(124, 1, 124, '2026-11-17 12:17:27'),
(125, 1, 125, '2026-11-17 12:17:27'),
(126, 1, 126, '2026-11-17 12:17:27'),
(127, 1, 127, '2026-11-17 12:17:27'),
(128, 1, 128, '2026-11-17 12:17:27'),
(129, 1, 129, '2026-11-17 12:17:27'),
(130, 1, 130, '2026-11-17 12:17:27'),
(131, 1, 131, '2026-11-17 12:17:27'),
(132, 1, 132, '2026-11-17 12:17:27'),
(133, 1, 133, '2026-11-17 12:17:27'),
(134, 1, 134, '2026-11-17 12:17:27'),
(135, 1, 135, '2026-11-17 12:17:27'),
(136, 1, 136, '2026-11-17 12:17:27'),
(137, 1, 137, '2026-11-17 12:17:27'),
(138, 1, 138, '2026-11-17 12:17:27'),
(139, 1, 139, '2026-11-17 12:17:27'),
(140, 1, 140, '2026-11-17 12:17:27'),
(141, 1, 141, '2026-11-17 12:17:27'),
(142, 1, 142, '2026-11-17 12:17:27'),
(143, 1, 143, '2026-11-17 12:17:27'),
(144, 1, 144, '2026-11-17 12:17:27'),
(145, 1, 145, '2026-11-17 12:17:27'),
(146, 1, 146, '2026-11-17 12:17:27'),
(147, 1, 147, '2026-11-17 12:17:27'),
(148, 1, 148, '2026-11-17 12:17:27'),
(149, 1, 149, '2026-11-17 12:17:27'),
(150, 1, 150, '2026-11-17 12:17:27'),
(151, 1, 151, '2026-11-17 12:17:27'),
(152, 1, 152, '2026-11-17 12:17:27'),
(153, 1, 153, '2026-11-17 12:17:27'),
(154, 1, 154, '2026-11-17 12:17:27'),
(155, 1, 155, '2026-11-17 12:17:27'),
(156, 1, 156, '2026-11-17 12:17:27'),
(157, 1, 157, '2026-11-17 12:17:27'),
(158, 1, 158, '2026-11-17 12:17:27'),
(159, 1, 159, '2026-11-17 12:17:27'),
(160, 1, 160, '2026-11-17 12:17:27'),
(161, 1, 161, '2026-11-17 12:17:27'),
(162, 1, 162, '2026-11-17 12:17:27'),
(163, 1, 163, '2026-11-17 12:17:27'),
(164, 1, 164, '2026-11-17 12:17:27'),
(165, 1, 165, '2026-11-17 12:17:27'),
(166, 1, 166, '2026-11-17 12:17:27'),
(167, 1, 167, '2026-11-17 12:17:27'),
(168, 1, 168, '2026-11-17 12:17:27'),
(169, 1, 169, '2026-11-17 12:17:27'),
(170, 1, 170, '2026-11-17 12:17:27'),
(171, 1, 171, '2026-11-17 12:17:27'),
(172, 1, 172, '2026-11-17 12:17:27'),
(173, 1, 173, '2026-11-17 12:17:27'),
(174, 1, 174, '2026-11-17 12:17:27'),
(175, 1, 175, '2026-11-17 12:17:27'),
(176, 1, 176, '2026-11-17 12:17:27'),
(177, 1, 177, '2026-11-17 12:17:27'),
(178, 1, 178, '2026-11-17 12:17:27'),
(179, 1, 179, '2026-11-17 12:17:27'),
(180, 1, 180, '2026-11-17 12:17:27'),
(181, 1, 181, '2026-11-17 12:17:27'),
(182, 1, 182, '2026-11-17 12:17:27'),
(183, 1, 183, '2026-11-17 12:17:27'),
(184, 1, 184, '2026-11-17 12:17:27'),
(185, 1, 185, '2026-11-17 12:17:27'),
(186, 1, 186, '2026-11-17 12:17:27'),
(187, 1, 187, '2026-11-17 12:17:27'),
(188, 1, 188, '2026-11-17 12:17:27'),
(189, 1, 189, '2026-11-17 12:17:27'),
(190, 1, 190, '2026-11-17 12:17:27'),
(191, 1, 191, '2026-11-17 12:17:27'),
(192, 1, 192, '2026-11-17 12:17:27'),
(193, 1, 193, '2026-11-17 12:17:27'),
(194, 1, 194, '2026-11-17 12:17:27'),
(195, 1, 195, '2026-11-17 12:17:27'),
(196, 1, 196, '2026-11-17 12:17:27'),
(197, 1, 197, '2026-11-17 12:17:27'),
(198, 1, 198, '2026-11-17 12:17:27'),
(199, 1, 199, '2026-11-17 12:17:27'),
(200, 1, 200, '2026-11-17 12:17:27'),
(201, 1, 201, '2026-11-17 12:17:27'),
(202, 1, 202, '2026-11-17 12:17:27'),
(203, 1, 203, '2026-11-17 12:17:27'),
(204, 1, 204, '2026-11-17 12:17:27'),
(205, 1, 205, '2026-11-17 12:17:27'),
(206, 1, 206, '2026-11-17 12:17:27'),
(207, 1, 207, '2026-11-17 12:17:27'),
(208, 1, 208, '2026-11-17 12:17:27'),
(209, 1, 209, '2026-11-17 12:17:27'),
(210, 1, 210, '2026-11-17 12:17:27'),
(211, 1, 211, '2026-11-17 12:17:27'),
(212, 1, 212, '2026-11-17 12:17:27'),
(213, 1, 213, '2026-11-17 12:17:27'),
(214, 1, 214, '2026-11-17 12:17:27'),
(215, 1, 215, '2026-11-17 12:17:27'),
(216, 1, 216, '2026-11-17 12:17:27'),
(217, 1, 217, '2026-11-17 12:17:27'),
(218, 1, 218, '2026-11-17 12:17:27'),
(219, 1, 219, '2026-11-17 12:17:27'),
(220, 1, 220, '2026-11-17 12:17:27'),
(221, 1, 221, '2026-11-17 12:17:27'),
(222, 1, 222, '2026-11-17 12:17:27'),
(223, 1, 223, '2026-11-17 12:17:27'),
(224, 1, 224, '2026-11-17 12:17:27'),
(225, 1, 225, '2026-11-17 12:17:27'),
(226, 1, 226, '2026-11-17 12:17:27'),
(227, 1, 227, '2026-11-17 12:17:27'),
(228, 1, 228, '2026-11-17 12:17:27'),
(229, 1, 229, '2026-11-17 12:17:27'),
(230, 1, 230, '2026-11-17 12:17:27'),
(231, 1, 231, '2026-11-17 12:17:27'),
(232, 1, 232, '2026-11-17 12:17:27'),
(233, 1, 233, '2026-11-17 12:17:27'),
(234, 1, 234, '2026-11-17 12:17:27'),
(235, 1, 235, '2026-11-17 12:17:27'),
(236, 1, 236, '2026-11-17 12:17:27'),
(237, 1, 237, '2026-11-17 12:17:27'),
(238, 1, 238, '2026-11-17 12:17:27'),
(239, 1, 239, '2026-11-17 12:17:27'),
(240, 1, 240, '2026-11-17 12:17:27'),
(241, 1, 241, '2026-11-17 12:17:27'),
(242, 1, 242, '2026-11-17 12:17:27'),
(243, 1, 243, '2026-11-17 12:17:27'),
(244, 1, 244, '2026-11-17 12:17:27'),
(245, 1, 245, '2026-11-17 12:17:27'),
(246, 1, 246, '2026-11-17 12:17:27'),
(247, 1, 247, '2026-11-17 12:17:27'),
(248, 1, 248, '2026-11-17 12:17:27'),
(249, 1, 249, '2026-11-17 12:17:27'),
(250, 1, 250, '2026-11-17 12:17:27'),
(251, 1, 251, '2026-11-17 12:17:27'),
(252, 1, 252, '2026-11-17 12:17:27'),
(253, 1, 253, '2026-11-17 12:17:27'),
(254, 1, 254, '2026-11-17 12:17:27'),
(255, 1, 255, '2026-11-17 12:17:27'),
(256, 1, 256, '2026-11-17 12:17:27'),
(257, 1, 257, '2026-11-17 12:17:27'),
(258, 1, 258, '2026-11-17 12:17:27'),
(259, 1, 259, '2026-11-17 12:17:27'),
(260, 1, 260, '2026-11-17 12:17:27'),
(261, 1, 261, '2026-11-17 12:17:27'),
(262, 1, 262, '2026-11-17 12:17:27'),
(263, 1, 263, '2026-11-17 12:17:27'),
(264, 1, 264, '2026-11-17 12:17:27'),
(265, 1, 265, '2026-11-17 12:17:27'),
(266, 1, 266, '2026-11-17 12:17:27'),
(267, 1, 267, '2026-11-17 12:17:27'),
(268, 1, 268, '2026-11-17 12:17:27'),
(269, 1, 269, '2026-11-17 12:17:27'),
(270, 1, 270, '2026-11-17 12:17:27'),
(271, 1, 271, '2026-11-17 12:17:27'),
(272, 1, 272, '2026-11-17 12:17:27'),
(273, 1, 273, '2026-11-17 12:17:27'),
(274, 1, 274, '2026-11-17 12:17:27'),
(275, 1, 275, '2026-11-17 12:17:27'),
(276, 1, 276, '2026-11-17 12:17:27'),
(277, 1, 277, '2026-11-17 12:17:27'),
(278, 1, 278, '2026-11-17 12:17:27'),
(279, 1, 279, '2026-11-17 12:17:27'),
(280, 1, 280, '2026-11-17 12:17:27'),
(281, 1, 281, '2026-11-17 12:17:27'),
(282, 1, 282, '2026-11-17 12:17:27'),
(283, 1, 283, '2026-11-17 12:17:27'),
(284, 1, 284, '2026-11-17 12:17:27'),
(285, 1, 285, '2026-11-17 12:17:27'),
(286, 1, 286, '2026-11-17 12:17:27'),
(287, 1, 287, '2026-11-17 12:17:27'),
(288, 1, 288, '2026-11-17 12:17:27'),
(289, 1, 289, '2026-11-17 12:17:27'),
(290, 1, 290, '2026-11-17 12:17:27'),
(291, 1, 291, '2026-11-17 12:17:27'),
(292, 1, 292, '2026-11-17 12:17:27'),
(293, 1, 293, '2026-11-17 12:17:27'),
(294, 1, 294, '2026-11-17 12:17:27'),
(295, 1, 295, '2026-11-17 12:17:27'),
(296, 1, 296, '2026-11-17 12:17:27'),
(297, 1, 297, '2026-11-17 12:17:27'),
(298, 1, 298, '2026-11-17 12:17:27'),
(299, 1, 299, '2026-11-17 12:17:27'),
(300, 1, 300, '2026-11-17 12:17:27'),
(301, 1, 301, '2026-11-17 12:17:27'),
(302, 1, 302, '2026-11-17 12:17:27'),
(303, 1, 303, '2026-11-17 12:17:27'),
(304, 1, 304, '2026-11-17 12:17:27'),
(305, 1, 305, '2026-11-17 12:17:27'),
(306, 1, 306, '2026-11-17 12:17:27'),
(1023, 1, 312, '2026-11-17 12:17:29'),
(1024, 1, 314, '2026-11-17 12:17:29'),
(1025, 1, 313, '2026-11-17 12:17:29'),
(1026, 1, 311, '2026-11-17 12:17:29'),
(1027, 1, 308, '2026-11-17 12:17:29'),
(1028, 1, 310, '2026-11-17 12:17:29'),
(1029, 1, 309, '2026-11-17 12:17:29'),
(1030, 1, 307, '2026-11-17 12:17:29'),
(1031, 1, 315, '2026-11-18 17:08:51'),
(1032, 1, 316, '2026-11-18 17:08:51'),
(1033, 1, 317, '2026-11-18 17:08:51'),
(1034, 1, 318, '2026-11-18 17:08:51'),
(1035, 1, 319, '2026-11-18 17:08:51'),
(1036, 1, 320, '2026-11-18 17:08:51'),
(1037, 1, 321, '2026-11-18 17:08:51'),
(1038, 1, 322, '2026-11-18 17:08:51'),
(1039, 1, 323, '2026-11-18 17:08:51'),
(1040, 1, 324, '2026-11-18 17:08:51'),
(1041, 1, 325, '2026-11-18 17:08:51'),
(1042, 1, 326, '2026-11-18 17:08:51'),
(1043, 1, 327, '2026-11-18 17:08:51'),
(1044, 1, 328, '2026-11-18 17:08:51'),
(1045, 1, 329, '2026-11-18 17:08:51'),
(1046, 1, 330, '2026-11-18 17:08:51'),
(1047, 1, 331, '2026-11-18 17:08:51'),
(1048, 1, 332, '2026-11-18 17:08:51'),
(1049, 1, 333, '2026-11-18 17:08:51'),
(1050, 1, 334, '2026-11-18 17:08:51'),
(1051, 1, 335, '2026-11-18 17:08:51'),
(1052, 1, 336, '2026-11-18 17:08:51'),
(1053, 1, 337, '2026-11-18 17:08:51'),
(1054, 1, 338, '2026-11-18 17:08:51'),
(1055, 1, 339, '2026-11-18 17:08:51'),
(1056, 1, 340, '2026-11-18 17:08:51'),
(1057, 1, 341, '2026-11-18 17:08:51'),
(1058, 1, 342, '2026-11-18 17:08:51'),
(1059, 1, 343, '2026-11-18 17:08:51'),
(1060, 1, 344, '2026-11-18 17:08:51'),
(1061, 1, 345, '2026-11-18 17:08:51'),
(1062, 1, 346, '2026-11-18 17:08:51'),
(1063, 1, 347, '2026-11-18 17:08:51'),
(1064, 1, 348, '2026-11-18 17:08:51'),
(1065, 1, 349, '2026-11-18 17:08:51'),
(1066, 1, 350, '2026-11-18 17:08:51'),
(1067, 1, 351, '2026-11-18 17:08:51'),
(1068, 1, 352, '2026-11-18 17:08:51'),
(1069, 1, 353, '2026-11-18 17:08:51'),
(1070, 1, 354, '2026-11-18 17:08:51'),
(1071, 1, 355, '2026-11-18 17:08:51'),
(1072, 1, 356, '2026-11-18 17:08:51'),
(1073, 1, 357, '2026-11-18 17:08:51'),
(1074, 1, 358, '2026-11-18 17:08:51'),
(1075, 1, 359, '2026-11-18 17:08:51'),
(1076, 1, 360, '2026-11-18 17:08:51'),
(1077, 1, 361, '2026-11-18 17:08:51'),
(1078, 1, 362, '2026-11-18 17:08:51'),
(1079, 1, 363, '2026-11-18 17:08:51'),
(1080, 1, 364, '2026-11-18 17:08:51'),
(1081, 1, 365, '2026-11-18 17:08:51'),
(1082, 1, 366, '2026-11-18 17:08:51'),
(1083, 1, 367, '2026-11-18 17:08:51'),
(1084, 1, 368, '2026-11-18 17:08:51'),
(1085, 1, 369, '2026-11-18 17:08:51'),
(1086, 1, 370, '2026-11-18 17:08:51'),
(1087, 1, 371, '2026-11-18 17:08:51'),
(1088, 1, 372, '2026-11-18 17:08:51'),
(1089, 1, 373, '2026-11-18 17:08:51'),
(1090, 1, 374, '2026-11-18 17:08:52'),
(1091, 1, 375, '2026-11-18 17:08:52'),
(1092, 1, 376, '2026-11-18 17:08:52'),
(1093, 1, 377, '2026-11-18 17:08:52'),
(1094, 1, 378, '2026-11-18 17:08:52'),
(1095, 1, 379, '2026-11-18 17:08:52'),
(1096, 1, 380, '2026-11-18 17:08:52'),
(1097, 1, 381, '2026-11-18 17:08:52'),
(1098, 1, 382, '2026-11-18 17:08:52'),
(1099, 1, 383, '2026-11-18 17:08:52'),
(1100, 1, 384, '2026-11-18 17:08:52'),
(1101, 1, 385, '2026-11-18 17:08:52'),
(1102, 1, 386, '2026-11-18 17:08:52'),
(1103, 1, 387, '2026-11-18 17:08:52'),
(1104, 1, 388, '2026-11-18 17:08:52'),
(1105, 1, 389, '2026-11-18 17:08:52'),
(1106, 1, 390, '2026-11-18 17:08:52'),
(1107, 1, 391, '2026-11-18 17:08:52'),
(1108, 1, 392, '2026-11-18 17:08:52'),
(1109, 1, 393, '2026-11-18 17:08:52'),
(1110, 1, 394, '2026-11-18 17:08:52'),
(1111, 1, 395, '2026-11-18 17:08:52'),
(1112, 1, 396, '2026-11-18 17:08:52'),
(1113, 1, 397, '2026-11-18 17:08:52'),
(1114, 1, 398, '2026-11-18 17:08:52'),
(1115, 1, 399, '2026-11-18 17:08:52'),
(1116, 1, 400, '2026-11-18 17:08:52'),
(1117, 1, 401, '2026-11-18 17:08:52'),
(1118, 1, 402, '2026-11-18 17:08:52'),
(1119, 1, 403, '2026-11-18 17:08:52'),
(1120, 1, 404, '2026-11-18 17:08:52'),
(1121, 1, 405, '2026-11-18 17:08:52'),
(1122, 1, 406, '2026-11-18 17:08:52'),
(1123, 1, 407, '2026-11-18 17:08:52'),
(1124, 1, 408, '2026-11-18 17:08:52'),
(1125, 1, 409, '2026-11-18 17:08:52'),
(1126, 1, 410, '2026-11-18 17:08:52'),
(1127, 1, 411, '2026-11-18 17:08:52'),
(1128, 1, 412, '2026-11-18 17:08:52'),
(1129, 1, 413, '2026-11-18 17:08:52'),
(1130, 1, 414, '2026-11-18 17:08:52'),
(1131, 1, 415, '2026-11-18 17:08:52'),
(1132, 1, 416, '2026-11-18 17:08:52'),
(1133, 1, 417, '2026-11-18 17:08:52'),
(1134, 1, 418, '2026-11-18 17:08:52'),
(1135, 1, 419, '2026-11-18 17:08:52'),
(1136, 1, 420, '2026-11-18 17:08:52'),
(1137, 1, 421, '2026-11-18 17:08:52'),
(1138, 1, 422, '2026-11-18 17:08:52'),
(1139, 1, 423, '2026-11-18 17:08:52'),
(1140, 1, 424, '2026-11-18 17:08:52'),
(1141, 1, 425, '2026-11-18 17:08:52'),
(1142, 1, 426, '2026-11-18 17:08:52'),
(1143, 1, 427, '2026-11-18 17:08:52'),
(1144, 1, 428, '2026-11-18 17:08:52'),
(1145, 1, 429, '2026-11-18 17:08:52'),
(1146, 1, 430, '2026-11-18 17:08:52'),
(1147, 1, 431, '2026-11-18 17:08:52'),
(1148, 1, 432, '2026-11-18 17:08:52'),
(1149, 1, 433, '2026-11-18 17:08:52'),
(1150, 1, 434, '2026-11-18 17:08:52'),
(1151, 1, 435, '2026-11-18 17:08:52'),
(1152, 1, 436, '2026-11-18 17:08:52'),
(1153, 1, 437, '2026-11-18 17:08:52'),
(1154, 1, 438, '2026-11-18 17:08:52'),
(1155, 1, 439, '2026-11-18 17:08:52'),
(1156, 1, 441, '2026-11-18 17:24:49'),
(1157, 1, 443, '2026-11-18 17:24:49'),
(1158, 1, 442, '2026-11-18 17:24:49'),
(1159, 1, 440, '2026-11-18 17:24:49'),
(1164, 2, 55, '2026-11-19 09:43:27'),
(1165, 2, 339, '2026-11-19 09:43:27'),
(1166, 2, 340, '2026-11-19 09:43:27'),
(1167, 2, 341, '2026-11-19 09:43:27'),
(1168, 2, 80, '2026-11-19 09:43:27'),
(1169, 2, 355, '2026-11-19 09:43:27'),
(1170, 2, 356, '2026-11-19 09:43:27'),
(1171, 2, 357, '2026-11-19 09:43:27'),
(1172, 2, 168, '2026-11-19 09:43:27'),
(1173, 2, 165, '2026-11-19 09:43:27'),
(1174, 2, 167, '2026-11-19 09:43:27'),
(1175, 2, 166, '2026-11-19 09:43:27'),
(1176, 2, 315, '2026-11-19 09:43:27'),
(1177, 2, 316, '2026-11-19 09:43:27'),
(1178, 2, 317, '2026-11-19 09:43:27'),
(1179, 2, 318, '2026-11-19 09:43:27'),
(1180, 2, 54, '2026-11-19 09:43:27'),
(1181, 2, 51, '2026-11-19 09:43:27'),
(1182, 2, 53, '2026-11-19 09:43:27'),
(1183, 2, 52, '2026-11-19 09:43:27'),
(1184, 2, 40, '2026-11-19 09:43:27'),
(1185, 2, 37, '2026-11-19 09:43:27'),
(1186, 2, 39, '2026-11-19 09:43:27'),
(1187, 2, 38, '2026-11-19 09:43:27'),
(1188, 2, 307, '2026-11-19 09:43:27'),
(1189, 2, 308, '2026-11-19 09:43:27'),
(1190, 2, 309, '2026-11-19 09:43:27'),
(1191, 2, 310, '2026-11-19 09:43:27'),
(1192, 2, 311, '2026-11-19 09:43:27'),
(1193, 2, 312, '2026-11-19 09:43:27'),
(1194, 2, 313, '2026-11-19 09:43:27'),
(1195, 2, 314, '2026-11-19 09:43:27'),
(1196, 2, 95, '2026-11-19 09:43:27'),
(1197, 2, 92, '2026-11-19 09:43:27'),
(1198, 2, 94, '2026-11-19 09:43:27'),
(1199, 2, 93, '2026-11-19 09:43:27'),
(1200, 2, 47, '2026-11-19 09:43:27'),
(1201, 2, 44, '2026-11-19 09:43:27'),
(1202, 2, 46, '2026-11-19 09:43:27'),
(1203, 2, 45, '2026-11-19 09:43:27'),
(1204, 2, 236, '2026-11-19 09:43:27'),
(1205, 2, 232, '2026-11-19 09:43:27'),
(1206, 2, 234, '2026-11-19 09:43:27'),
(1207, 2, 233, '2026-11-19 09:43:27'),
(1208, 2, 206, '2026-11-19 09:43:27'),
(1209, 2, 203, '2026-11-19 09:43:27'),
(1210, 2, 205, '2026-11-19 09:43:27'),
(1211, 2, 204, '2026-11-19 09:43:27'),
(1212, 2, 211, '2026-11-19 09:43:27'),
(1213, 2, 207, '2026-11-19 09:43:27'),
(1214, 2, 209, '2026-11-19 09:43:27'),
(1215, 2, 208, '2026-11-19 09:43:27'),
(1216, 2, 109, '2026-11-19 09:43:27'),
(1217, 2, 106, '2026-11-19 09:43:27'),
(1218, 2, 108, '2026-11-19 09:43:27'),
(1219, 2, 107, '2026-11-19 09:43:27'),
(1220, 2, 57, '2026-11-19 09:43:27'),
(1221, 2, 358, '2026-11-19 09:43:27'),
(1222, 2, 359, '2026-11-19 09:43:27'),
(1223, 2, 56, '2026-11-19 09:43:27'),
(1224, 2, 61, '2026-11-19 09:43:27'),
(1225, 2, 58, '2026-11-19 09:43:27'),
(1226, 2, 362, '2026-11-19 09:43:27'),
(1227, 2, 363, '2026-11-19 09:43:27'),
(1228, 2, 366, '2026-11-19 09:43:27'),
(1229, 2, 367, '2026-11-19 09:43:27'),
(1230, 2, 368, '2026-11-19 09:43:27'),
(1231, 2, 369, '2026-11-19 09:43:27'),
(1232, 2, 90, '2026-11-19 09:43:27'),
(1233, 2, 86, '2026-11-19 09:43:27'),
(1234, 2, 88, '2026-11-19 09:43:27'),
(1235, 2, 87, '2026-11-19 09:43:27'),
(1236, 2, 85, '2026-11-19 09:43:27'),
(1237, 2, 81, '2026-11-19 09:43:27'),
(1238, 2, 83, '2026-11-19 09:43:27'),
(1239, 2, 82, '2026-11-19 09:43:27'),
(1240, 2, 319, '2026-11-19 09:43:27'),
(1241, 2, 320, '2026-11-19 09:43:27'),
(1242, 2, 321, '2026-11-19 09:43:27'),
(1243, 2, 322, '2026-11-19 09:43:27'),
(1244, 2, 36, '2026-11-19 09:43:27'),
(1245, 2, 34, '2026-11-19 09:43:27'),
(1246, 2, 342, '2026-11-19 09:43:27'),
(1247, 2, 343, '2026-11-19 09:43:27'),
(1248, 2, 76, '2026-11-19 09:43:27'),
(1249, 2, 74, '2026-11-19 09:43:27'),
(1250, 2, 360, '2026-11-19 09:43:27'),
(1251, 2, 361, '2026-11-19 09:43:27'),
(1252, 2, 323, '2026-11-19 09:43:27'),
(1253, 2, 324, '2026-11-19 09:43:27'),
(1254, 2, 325, '2026-11-19 09:43:27'),
(1255, 2, 326, '2026-11-19 09:43:27'),
(1256, 2, 231, '2026-11-19 09:43:27'),
(1257, 2, 227, '2026-11-19 09:43:27'),
(1258, 2, 230, '2026-11-19 09:43:27'),
(1259, 2, 228, '2026-11-19 09:43:27'),
(1260, 2, 327, '2026-11-19 09:43:27'),
(1261, 2, 328, '2026-11-19 09:43:27'),
(1262, 2, 329, '2026-11-19 09:43:27'),
(1263, 2, 330, '2026-11-19 09:43:27'),
(1264, 2, 15, '2026-11-19 09:43:27'),
(1265, 2, 13, '2026-11-19 09:43:27'),
(1266, 2, 14, '2026-11-19 09:43:27'),
(1267, 2, 434, '2026-11-19 09:43:27'),
(1268, 2, 440, '2026-11-19 09:43:27'),
(1269, 2, 441, '2026-11-19 09:43:27'),
(1270, 2, 442, '2026-11-19 09:43:27'),
(1271, 2, 443, '2026-11-19 09:43:27'),
(1272, 2, 202, '2026-11-19 09:43:27'),
(1273, 2, 199, '2026-11-19 09:43:27'),
(1274, 2, 201, '2026-11-19 09:43:27'),
(1275, 2, 200, '2026-11-19 09:43:27'),
(1276, 2, 157, '2026-11-19 09:43:27'),
(1277, 2, 154, '2026-11-19 09:43:27'),
(1278, 2, 156, '2026-11-19 09:43:27'),
(1279, 2, 155, '2026-11-19 09:43:27'),
(1280, 2, 27, '2026-11-19 09:43:27'),
(1281, 2, 349, '2026-11-19 09:43:27'),
(1282, 2, 350, '2026-11-19 09:43:27'),
(1283, 2, 351, '2026-11-19 09:43:27'),
(1284, 2, 170, '2026-11-19 09:43:27'),
(1285, 2, 352, '2026-11-19 09:43:27'),
(1286, 2, 169, '2026-11-19 09:43:27'),
(1287, 2, 353, '2026-11-19 09:43:27'),
(1288, 2, 32, '2026-11-19 09:43:27'),
(1289, 2, 28, '2026-11-19 09:43:27'),
(1290, 2, 354, '2026-11-19 09:43:27'),
(1291, 2, 29, '2026-11-19 09:43:27'),
(1292, 2, 73, '2026-11-19 09:43:27'),
(1293, 2, 438, '2026-11-19 09:43:27'),
(1294, 2, 439, '2026-11-19 09:43:27'),
(1295, 2, 71, '2026-11-19 09:43:27'),
(1304, 12, 55, '2026-11-19 11:47:52'),
(1305, 12, 339, '2026-11-19 11:47:52'),
(1306, 12, 340, '2026-11-19 11:47:52'),
(1307, 12, 341, '2026-11-19 11:47:52'),
(1308, 12, 80, '2026-11-19 11:47:52'),
(1309, 12, 168, '2026-11-19 11:47:52'),
(1310, 12, 315, '2026-11-19 11:47:52'),
(1311, 12, 316, '2026-11-19 11:47:52'),
(1312, 12, 317, '2026-11-19 11:47:52'),
(1313, 12, 318, '2026-11-19 11:47:52'),
(1314, 12, 307, '2026-11-19 11:47:52'),
(1315, 12, 311, '2026-11-19 11:47:52'),
(1316, 1, 444, '2026-11-19 12:00:10'),
(1317, 2, 444, '2026-11-19 12:00:10'),
(1318, 12, 444, '2026-11-19 12:00:10'),
(1320, 1, 445, '2026-11-19 12:00:10'),
(1321, 2, 445, '2026-11-19 12:00:10'),
(1322, 12, 445, '2026-11-19 12:00:10'),
(1324, 1, 446, '2026-11-19 12:00:10'),
(1325, 2, 446, '2026-11-19 12:00:10'),
(1326, 12, 446, '2026-11-19 12:00:10'),
(1328, 1, 447, '2026-11-19 12:00:10'),
(1329, 2, 447, '2026-11-19 12:00:10'),
(1330, 12, 447, '2026-11-19 12:00:10')
ON DUPLICATE KEY UPDATE `role_id`=VALUES(`role_id`), `permission_id`=VALUES(`permission_id`);

-- 5. System Settings
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_group`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 'company_name', 'GRM Apparels', 'general', 1, '2026-11-17 12:17:27', '2026-11-17 12:17:27'),
(2, 'company_email', 'admin@grm.com', 'general', 1, '2026-11-17 12:17:27', '2026-11-17 12:17:27'),
(3, 'company_phone', '9876543210', 'general', 1, '2026-11-17 12:17:27', '2026-11-17 12:17:27'),
(4, 'currency_symbol', '₹', 'general', 1, '2026-11-17 12:17:27', '2026-11-17 12:17:27'),
(5, 'currency_code', 'INR', 'general', 1, '2026-11-17 12:17:27', '2026-11-17 12:17:27'),
(6, 'timezone', 'Asia/Kolkata', 'general', 1, '2026-11-17 12:17:27', '2026-11-17 12:17:27'),
(7, 'default_warehouse_id', '1', 'inventory', 0, '2026-11-17 12:17:27', '2026-11-17 12:17:27'),
(8, 'default_production_unit_id', '1', 'production', 0, '2026-11-17 12:17:27', '2026-11-17 12:17:27')
ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);

-- 6. Default Administrator User
INSERT INTO `users` (`id`, `username`, `emp_id`, `email`, `password_hash`, `full_name`, `phone`, `role_id`, `department`, `default_concern_id`, `is_active`, `created_at`) VALUES
(1, 'admin', 'EMP-001', 'admin@grm.com', '$2y$10$WXeV9abEWzjWySfggVzhTOp4j7pzF7UuFNUJ1lrLbWe9NDL7Cz7UC', 'System Administrator', '9876543210', 1, 'ADMINISTRATION', 1, 1, '2026-11-17 12:17:27')
ON DUPLICATE KEY UPDATE `username`=VALUES(`username`);

SET FOREIGN_KEY_CHECKS = 1;
