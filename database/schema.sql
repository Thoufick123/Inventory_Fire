-- ============================================================
-- GRM INVENTORY SOFTWARE - SAFE ADDITIVE MASTER SCHEMA
-- Generated: 2026-11-20
-- Tables: 91 (includes shop damages and production purchase documents)
-- Existing-database upgrade: creates missing tables and adds missing columns/indexes.
-- Does not drop tables, delete rows, or insert seed/demo users.
-- Review and back up the live database before importing.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+05:30';

-- ------------------------------------------------------------
-- [1/91] Table structure for `allocation_outwards`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `allocation_outwards` (
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
-- [2/91] Table structure for `asset_categories`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `asset_categories` (
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
-- [3/91] Table structure for `asset_maintenance_logs`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `asset_maintenance_logs` (
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
-- [4/91] Table structure for `assets`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `assets` (
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
-- [5/91] Table structure for `audit_logs`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
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
-- [6/91] Table structure for `brands`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `brands` (
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
-- [7/91] Table structure for `categories`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
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
-- [8/91] Table structure for `colors`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `colors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `hex_code` varchar(7) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [9/91] Table structure for `concern_modules`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `concern_modules` (
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
-- [10/91] Table structure for `concerns`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `concerns` (
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
-- [11/91] Table structure for `cutting_batch_rolls`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cutting_batch_rolls` (
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
-- [12/91] Table structure for `cutting_batches`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cutting_batches` (
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
-- [13/91] Table structure for `cutting_items`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cutting_items` (
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
-- [14/91] Table structure for `designs`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `designs` (
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
-- [15/91] Table structure for `expense_categories`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `expense_categories` (
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
-- [16/91] Table structure for `hsn_codes`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `hsn_codes` (
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
CREATE TABLE IF NOT EXISTS `product_price_logs` (
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
CREATE TABLE IF NOT EXISTS `notifications` (
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
-- [20/91] Table structure for `permissions`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permissions` (
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
-- [21/91] Table structure for `product_variants`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_variants` (
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
-- [22/91] Table structure for `production_batch_items`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `production_batch_items` (
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
-- [23/91] Table structure for `production_batches`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `production_batches` (
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
-- [24/91] Table structure for `production_bill_item_tags`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `production_bill_item_tags` (
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
-- [25/91] Table structure for `production_bill_items`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `production_bill_items` (
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
-- [26/91] Table structure for `production_bills`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `production_bills` (
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
-- [27/91] Table structure for `production_category_attributes`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `production_category_attributes` (
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
-- [28/91] Table structure for `production_deleted_label_codes`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `production_deleted_label_codes` (
  `code` varchar(50) NOT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [29/91] Table structure for `production_expense_items`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `production_expense_items` (
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
-- [30/91] Table structure for `production_expenses`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `production_expenses` (
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
-- [31/91] Table structure for `production_outward_batches`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `production_outward_batches` (
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
-- [32/91] Table structure for `production_outwards`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `production_outwards` (
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
-- [33/91] Table structure for `production_program_items`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `production_program_items` (
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
-- [34/91] Table structure for `production_program_rolls`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `production_program_rolls` (
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
-- [35/91] Table structure for `production_programs`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `production_programs` (
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
-- [36/91] Table structure for `production_purchase_items`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `production_purchase_items` (
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
-- [37/91] Table structure for `production_purchases`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `production_purchases` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `partition_id` int(10) unsigned DEFAULT 1,
  `purchase_number` varchar(50) NOT NULL,
  `purchase_type` enum('FABRIC_ROLL','RAW_MATERIAL','THIRD_PARTY','OWN_PRODUCT') NOT NULL DEFAULT 'FABRIC_ROLL',
  `seller_id` int(10) unsigned NOT NULL,
  `purchase_date` date NOT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `document_url` varchar(500) DEFAULT NULL,
  `document_urls` text DEFAULT NULL,
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
-- [38/91] Table structure for `production_return_items`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `production_return_items` (
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
-- [39/91] Table structure for `production_returns`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `production_returns` (
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
-- [40/91] Table structure for `production_stock_request_items`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `production_stock_request_items` (
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
-- [41/91] Table structure for `production_stock_requests`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `production_stock_requests` (
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
-- [42/91] Table structure for `production_units`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `production_units` (
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
-- [43/91] Table structure for `production_warehouse_reworks`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `production_warehouse_reworks` (
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
-- [44/91] Table structure for `products`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
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
-- [45/91] Table structure for `purchase_inward_items`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `purchase_inward_items` (
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
-- [46/91] Table structure for `purchase_inwards`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `purchase_inwards` (
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
-- [47/91] Table structure for `purchase_items`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `purchase_items` (
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
-- [48/91] Table structure for `purchase_orders`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `purchase_orders` (
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
-- [49/91] Table structure for `qc_batches`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qc_batches` (
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
-- [50/91] Table structure for `qc_items`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qc_items` (
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
-- [51/91] Table structure for `qr_generation_items`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qr_generation_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `generation_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `size_name` varchar(100) DEFAULT NULL,
  `color_name` varchar(100) DEFAULT NULL,
  `mrp` decimal(10,2) DEFAULT NULL,
  `selling_price` decimal(10,2) DEFAULT NULL,
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
-- [52/91] Table structure for `qr_generations`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qr_generations` (
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
-- [53/91] Table structure for `role_permissions`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `role_permissions` (
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
-- [54/91] Table structure for `roles`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
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
-- [55/91] Table structure for `roll_damage_reasons`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roll_damage_reasons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reason` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_roll_damage_reasons_reason` (`reason`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [56/91] Table structure for `roll_damages`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roll_damages` (
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
-- [57/91] Table structure for `sales_channels`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sales_channels` (
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
-- [58/91] Table structure for `seller_roll_deleted_labels`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `seller_roll_deleted_labels` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `seller_roll_id` int(10) unsigned NOT NULL,
  `sequence` int(10) unsigned NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_seller_roll_deleted_sequence` (`seller_roll_id`,`sequence`),
  CONSTRAINT `seller_roll_deleted_labels_ibfk_1` FOREIGN KEY (`seller_roll_id`) REFERENCES `seller_rolls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [59/91] Table structure for `seller_rolls`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `seller_rolls` (
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
-- [60/91] Table structure for `sellers`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sellers` (
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
-- [61/91] Table structure for `settings`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
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
-- [62/91] Table structure for `shops`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shops` (
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
-- [63/91] Table structure for `sizes`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sizes` (
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
-- [64/91] Table structure for `stitching_batches`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stitching_batches` (
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
-- [65/91] Table structure for `stitching_items`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stitching_items` (
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
-- [66/91] Table structure for `stitching_wastage_sales`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stitching_wastage_sales` (
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
-- [67/91] Table structure for `stock_adjustments`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stock_adjustments` (
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
-- [68/91] Table structure for `stock_balances`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stock_balances` (
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
-- [69/91] Table structure for `stock_transactions`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stock_transactions` (
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
-- [70/91] Table structure for `sub_subcategories`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sub_subcategories` (
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
-- [71/91] Table structure for `subcategories`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subcategories` (
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
-- [72/91] Table structure for `third_party_categories`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `third_party_categories` (
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
-- [73/91] Table structure for `third_party_products`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `third_party_products` (
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
CREATE TABLE IF NOT EXISTS `third_party_product_variants` (
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
-- [74/91] Table structure for `third_party_sub_subcategories`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `third_party_sub_subcategories` (
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
-- [75/91] Table structure for `third_party_subcategories`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `third_party_subcategories` (
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
-- [76/91] Table structure for `units`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `units` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `abbreviation` varchar(10) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [77/91] Table structure for `user_concerns`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_concerns` (
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
-- [78/91] Table structure for `user_permission_overrides`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_permission_overrides` (
  `user_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- [79/91] Table structure for `user_permissions`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_permissions` (
  `user_id` int(10) unsigned NOT NULL,
  `permission_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`permission_id`),
  KEY `idx_user_perms` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- [80/91] Table structure for `users`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
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
-- [81/91] Table structure for `warehouse_allocations`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `warehouse_allocations` (
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
-- [82/91] Table structure for `warehouse_categories`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `warehouse_categories` (
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
-- [83/91] Table structure for `warehouse_damages`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `warehouse_damages` (
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
-- [84/91] Table structure for `warehouse_qr_codes`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `warehouse_qr_codes` (
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
-- [85/91] Table structure for `warehouse_sub_subcategories`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `warehouse_sub_subcategories` (
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
-- [86/91] Table structure for `warehouse_subcategories`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `warehouse_subcategories` (
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
-- [87/91] Table structure for `warehouses`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `warehouses` (
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
-- [88/91] Table structure for `wastage_sales`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wastage_sales` (
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
-- [89/91] Table structure for `wh_category_attributes`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wh_category_attributes` (
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

-- ------------------------------------------------------------
-- [90/91] Table structure for `shop_damages`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shop_damages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `damage_number` varchar(50) NOT NULL,
  `shop_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned NOT NULL,
  `tag_code` varchar(100) DEFAULT NULL,
  `source_type` enum('INWARD_TRANSIT','SHOP_BIN_HANDLING','STORAGE_HANDLING','CUSTOMER_RETURN','EXPIRED_SPOILED','OTHER') DEFAULT 'SHOP_BIN_HANDLING',
  `reference_id` int(10) unsigned DEFAULT NULL,
  `allocation_id` int(10) unsigned DEFAULT NULL,
  `defect_type` enum('FABRIC_DEFECT','COLOR_FADE','STITCHING_ERROR','PACKAGING_DAMAGE','WATER_STAIN','TRANSIT_DAMAGE','EXPIRED','SHOP_SOIL','OTHER') DEFAULT 'OTHER',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `cost_per_unit` decimal(10,2) DEFAULT 0.00,
  `estimated_cost` decimal(10,2) DEFAULT 0.00,
  `total_loss` decimal(10,2) DEFAULT 0.00,
  `damage_reason` varchar(255) NOT NULL,
  `defect_notes` text DEFAULT NULL,
  `photo_url` varchar(500) DEFAULT NULL,
  `status` enum('LOGGED','INSPECTED','SALVAGED','WRITTEN_OFF','REPAIRED','RETURN_TO_WH','REWORK','CANCELLED') DEFAULT 'LOGGED',
  `disposition_notes` text DEFAULT NULL,
  `reported_by` int(10) unsigned DEFAULT NULL,
  `inspected_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `product_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `damage_number` (`damage_number`),
  KEY `shop_id` (`shop_id`),
  KEY `variant_id` (`variant_id`),
  KEY `reported_by` (`reported_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- [91/91] Table structure for `production_purchase_documents`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `production_purchase_documents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` int(10) unsigned NOT NULL,
  `document_url` varchar(500) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pp_docs_purchase` (`purchase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Additive upgrades for databases created from earlier repository schemas
-- Conditional DDL avoids overwriting existing columns or indexes.
-- ------------------------------------------------------------
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cutting_batches' AND COLUMN_NAME = 'partition_id'),
  'SELECT 1',
  'ALTER TABLE `cutting_batches` ADD COLUMN `partition_id` int(10) unsigned DEFAULT 1'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cutting_batches' AND COLUMN_NAME = 'concern_id'),
  'SELECT 1',
  'ALTER TABLE `cutting_batches` ADD COLUMN `concern_id` int(11) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cutting_batches' AND INDEX_NAME = 'idx_cutting_batches_created_at'),
  'SELECT 1',
  'ALTER TABLE `cutting_batches` ADD INDEX `idx_cutting_batches_created_at` (`created_at`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cutting_batches' AND INDEX_NAME = 'idx_cutting_batches_concern_id'),
  'SELECT 1',
  'ALTER TABLE `cutting_batches` ADD INDEX `idx_cutting_batches_concern_id` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cutting_batches' AND INDEX_NAME = 'idx_cutting_batches_concern'),
  'SELECT 1',
  'ALTER TABLE `cutting_batches` ADD INDEX `idx_cutting_batches_concern` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hsn_codes' AND INDEX_NAME = 'idx_hsn_code_search'),
  'SELECT 1',
  'ALTER TABLE `hsn_codes` ADD INDEX `idx_hsn_code_search` (`hsn_code`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hsn_codes' AND INDEX_NAME = 'idx_gst_rate'),
  'SELECT 1',
  'ALTER TABLE `hsn_codes` ADD INDEX `idx_gst_rate` (`gst_rate`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'variant_code'),
  'SELECT 1',
  'ALTER TABLE `product_variants` ADD COLUMN `variant_code` varchar(20) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'production_unit_id'),
  'SELECT 1',
  'ALTER TABLE `product_variants` ADD COLUMN `production_unit_id` int(10) unsigned DEFAULT 1'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'design_id'),
  'SELECT 1',
  'ALTER TABLE `product_variants` ADD COLUMN `design_id` int(10) unsigned DEFAULT 1'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'attr_hash'),
  'SELECT 1',
  'ALTER TABLE `product_variants` ADD COLUMN `attr_hash` varchar(64) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'attributes'),
  'SELECT 1',
  'ALTER TABLE `product_variants` ADD COLUMN `attributes` text DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variants' AND INDEX_NAME = 'uk_variant_attr_hash'),
  'SELECT 1',
  IF(
    EXISTS(SELECT 1 FROM `product_variants` WHERE `attr_hash` IS NOT NULL GROUP BY `attr_hash` HAVING COUNT(*) > 1),
    'SELECT ''WARNING: skipped uk_variant_attr_hash because duplicate values exist'' AS schema_warning',
    'ALTER TABLE `product_variants` ADD UNIQUE INDEX `uk_variant_attr_hash` (`attr_hash`)'
  )
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variants' AND INDEX_NAME = 'uk_variant_code'),
  'SELECT 1',
  IF(
    EXISTS(SELECT 1 FROM `product_variants` WHERE `variant_code` IS NOT NULL GROUP BY `variant_code` HAVING COUNT(*) > 1),
    'SELECT ''WARNING: skipped uk_variant_code because duplicate values exist'' AS schema_warning',
    'ALTER TABLE `product_variants` ADD UNIQUE INDEX `uk_variant_code` (`variant_code`)'
  )
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_batches' AND COLUMN_NAME = 'partition_id'),
  'SELECT 1',
  'ALTER TABLE `production_batches` ADD COLUMN `partition_id` int(10) unsigned DEFAULT 1'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_batches' AND COLUMN_NAME = 'production_bill_id'),
  'SELECT 1',
  'ALTER TABLE `production_batches` ADD COLUMN `production_bill_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_batches' AND COLUMN_NAME = 'concern_id'),
  'SELECT 1',
  'ALTER TABLE `production_batches` ADD COLUMN `concern_id` int(11) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_batches' AND INDEX_NAME = 'idx_production_batches_bill'),
  'SELECT 1',
  'ALTER TABLE `production_batches` ADD INDEX `idx_production_batches_bill` (`production_bill_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_batches' AND INDEX_NAME = 'idx_production_batches_packed_created'),
  'SELECT 1',
  'ALTER TABLE `production_batches` ADD INDEX `idx_production_batches_packed_created` (`packed_date`,`created_at`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_batches' AND INDEX_NAME = 'idx_production_batches_concern_id'),
  'SELECT 1',
  'ALTER TABLE `production_batches` ADD INDEX `idx_production_batches_concern_id` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_batches' AND INDEX_NAME = 'idx_production_batches_concern'),
  'SELECT 1',
  'ALTER TABLE `production_batches` ADD INDEX `idx_production_batches_concern` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_bill_items' AND COLUMN_NAME = 'is_complementary'),
  'SELECT 1',
  'ALTER TABLE `production_bill_items` ADD COLUMN `is_complementary` tinyint(1) NOT NULL DEFAULT 0'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_bills' AND COLUMN_NAME = 'production_unit_id'),
  'SELECT 1',
  'ALTER TABLE `production_bills` ADD COLUMN `production_unit_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_bills' AND COLUMN_NAME = 'concern_id'),
  'SELECT 1',
  'ALTER TABLE `production_bills` ADD COLUMN `concern_id` int(11) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_bills' AND INDEX_NAME = 'idx_production_bills_unit'),
  'SELECT 1',
  'ALTER TABLE `production_bills` ADD INDEX `idx_production_bills_unit` (`production_unit_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_bills' AND INDEX_NAME = 'idx_production_bills_concern_id'),
  'SELECT 1',
  'ALTER TABLE `production_bills` ADD INDEX `idx_production_bills_concern_id` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_bills' AND INDEX_NAME = 'idx_production_bills_concern'),
  'SELECT 1',
  'ALTER TABLE `production_bills` ADD INDEX `idx_production_bills_concern` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_expenses' AND COLUMN_NAME = 'concern_id'),
  'SELECT 1',
  'ALTER TABLE `production_expenses` ADD COLUMN `concern_id` int(11) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_expenses' AND INDEX_NAME = 'idx_production_expenses_date'),
  'SELECT 1',
  'ALTER TABLE `production_expenses` ADD INDEX `idx_production_expenses_date` (`expense_date`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_expenses' AND INDEX_NAME = 'idx_production_expenses_concern_id'),
  'SELECT 1',
  'ALTER TABLE `production_expenses` ADD INDEX `idx_production_expenses_concern_id` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_expenses' AND INDEX_NAME = 'idx_production_expenses_concern'),
  'SELECT 1',
  'ALTER TABLE `production_expenses` ADD INDEX `idx_production_expenses_concern` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_outwards' AND COLUMN_NAME = 'partition_id'),
  'SELECT 1',
  'ALTER TABLE `production_outwards` ADD COLUMN `partition_id` int(10) unsigned DEFAULT 1'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_outwards' AND COLUMN_NAME = 'concern_id'),
  'SELECT 1',
  'ALTER TABLE `production_outwards` ADD COLUMN `concern_id` int(11) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_outwards' AND INDEX_NAME = 'idx_production_outwards_dispatch_created'),
  'SELECT 1',
  'ALTER TABLE `production_outwards` ADD INDEX `idx_production_outwards_dispatch_created` (`dispatch_date`,`created_at`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_outwards' AND INDEX_NAME = 'idx_production_outwards_concern_id'),
  'SELECT 1',
  'ALTER TABLE `production_outwards` ADD INDEX `idx_production_outwards_concern_id` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_outwards' AND INDEX_NAME = 'idx_production_outwards_concern'),
  'SELECT 1',
  'ALTER TABLE `production_outwards` ADD INDEX `idx_production_outwards_concern` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_program_rolls' AND COLUMN_NAME = 'attributes'),
  'SELECT 1',
  'ALTER TABLE `production_program_rolls` ADD COLUMN `attributes` text DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_programs' AND COLUMN_NAME = 'partition_id'),
  'SELECT 1',
  'ALTER TABLE `production_programs` ADD COLUMN `partition_id` int(10) unsigned DEFAULT 1'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_programs' AND COLUMN_NAME = 'concern_id'),
  'SELECT 1',
  'ALTER TABLE `production_programs` ADD COLUMN `concern_id` int(11) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_programs' AND INDEX_NAME = 'idx_production_programs_created_at'),
  'SELECT 1',
  'ALTER TABLE `production_programs` ADD INDEX `idx_production_programs_created_at` (`created_at`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_programs' AND INDEX_NAME = 'idx_production_programs_concern_id'),
  'SELECT 1',
  'ALTER TABLE `production_programs` ADD INDEX `idx_production_programs_concern_id` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_programs' AND INDEX_NAME = 'idx_production_programs_concern'),
  'SELECT 1',
  'ALTER TABLE `production_programs` ADD INDEX `idx_production_programs_concern` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'product_id'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `product_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'third_party_product_id'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `third_party_product_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'variant_id'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `variant_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'product_code'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `product_code` varchar(100) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'vendor_sku'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `vendor_sku` varchar(100) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'inwarded_qty'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `inwarded_qty` decimal(12,3) NOT NULL DEFAULT 0.000'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'mrp'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `mrp` decimal(12,2) NOT NULL DEFAULT 0.00'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'selling_price'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `selling_price` decimal(12,2) NOT NULL DEFAULT 0.00'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'tax_treatment'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `tax_treatment` enum(''INCLUDED'',''EXCLUDED'') NOT NULL DEFAULT ''INCLUDED'''
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'size'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `size` varchar(50) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'start_code'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `start_code` varchar(100) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'end_code'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `end_code` varchar(100) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'qr_code'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `qr_code` varchar(100) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'hsn_code'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `hsn_code` varchar(20) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'is_inwarded'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `is_inwarded` tinyint(1) NOT NULL DEFAULT 0'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'inwarded_at'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `inwarded_at` datetime DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'inwarded_by'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `inwarded_by` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'is_mapped'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `is_mapped` tinyint(1) NOT NULL DEFAULT 0'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'is_complementary'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `is_complementary` tinyint(1) NOT NULL DEFAULT 0'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'mapped_category_id'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `mapped_category_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'mapped_subcategory_id'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `mapped_subcategory_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'mapped_sub_subcategory_id'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `mapped_sub_subcategory_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'mapped_third_party_category_id'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `mapped_third_party_category_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'mapped_third_party_subcategory_id'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `mapped_third_party_subcategory_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'mapped_third_party_sub_subcategory_id'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `mapped_third_party_sub_subcategory_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'mapped_attributes'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `mapped_attributes` text DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'mapped_at'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `mapped_at` datetime DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'mapped_by'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `mapped_by` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'qr_generation_id'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `qr_generation_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'qr_batch_code'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `qr_batch_code` varchar(50) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'grn_id'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `grn_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND COLUMN_NAME = 'grn_number'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD COLUMN `grn_number` varchar(50) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND INDEX_NAME = 'idx_ppi_variant_id'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD INDEX `idx_ppi_variant_id` (`variant_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND INDEX_NAME = 'idx_ppi_type_mapped_qrg'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD INDEX `idx_ppi_type_mapped_qrg` (`item_type`,`is_mapped`,`qr_generation_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND INDEX_NAME = 'idx_ppi_qr_generation'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD INDEX `idx_ppi_qr_generation` (`qr_generation_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchase_items' AND INDEX_NAME = 'idx_ppi_grn'),
  'SELECT 1',
  'ALTER TABLE `production_purchase_items` ADD INDEX `idx_ppi_grn` (`grn_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchases' AND COLUMN_NAME = 'partition_id'),
  'SELECT 1',
  'ALTER TABLE `production_purchases` ADD COLUMN `partition_id` int(10) unsigned DEFAULT 1'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchases' AND COLUMN_NAME = 'purchase_type'),
  'SELECT 1',
  'ALTER TABLE `production_purchases` ADD COLUMN `purchase_type` enum(''FABRIC_ROLL'',''RAW_MATERIAL'',''THIRD_PARTY'',''OWN_PRODUCT'') NOT NULL DEFAULT ''FABRIC_ROLL'''
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchases' AND COLUMN_NAME = 'document_urls'),
  'SELECT 1',
  'ALTER TABLE `production_purchases` ADD COLUMN `document_urls` text DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchases' AND COLUMN_NAME = 'grn_id'),
  'SELECT 1',
  'ALTER TABLE `production_purchases` ADD COLUMN `grn_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchases' AND COLUMN_NAME = 'grn_number'),
  'SELECT 1',
  'ALTER TABLE `production_purchases` ADD COLUMN `grn_number` varchar(50) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchases' AND COLUMN_NAME = 'is_inwarded'),
  'SELECT 1',
  'ALTER TABLE `production_purchases` ADD COLUMN `is_inwarded` tinyint(1) DEFAULT 0'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchases' AND COLUMN_NAME = 'inwarded_at'),
  'SELECT 1',
  'ALTER TABLE `production_purchases` ADD COLUMN `inwarded_at` datetime DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchases' AND COLUMN_NAME = 'concern_id'),
  'SELECT 1',
  'ALTER TABLE `production_purchases` ADD COLUMN `concern_id` int(11) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchases' AND INDEX_NAME = 'idx_purchases_type_date'),
  'SELECT 1',
  'ALTER TABLE `production_purchases` ADD INDEX `idx_purchases_type_date` (`purchase_type`,`purchase_date`,`seller_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchases' AND INDEX_NAME = 'idx_pp_grn'),
  'SELECT 1',
  'ALTER TABLE `production_purchases` ADD INDEX `idx_pp_grn` (`grn_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchases' AND INDEX_NAME = 'idx_production_purchases_concern'),
  'SELECT 1',
  'ALTER TABLE `production_purchases` ADD INDEX `idx_production_purchases_concern` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchases' AND INDEX_NAME = 'idx_production_purchases_concern_id'),
  'SELECT 1',
  'ALTER TABLE `production_purchases` ADD INDEX `idx_production_purchases_concern_id` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_purchases' AND INDEX_NAME = 'idx_pp_concern_date'),
  'SELECT 1',
  'ALTER TABLE `production_purchases` ADD INDEX `idx_pp_concern_date` (`concern_id`,`purchase_date`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_returns' AND COLUMN_NAME = 'concern_id'),
  'SELECT 1',
  'ALTER TABLE `production_returns` ADD COLUMN `concern_id` int(11) DEFAULT 1'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_returns' AND INDEX_NAME = 'idx_production_returns_concern'),
  'SELECT 1',
  'ALTER TABLE `production_returns` ADD INDEX `idx_production_returns_concern` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_stock_request_items' AND COLUMN_NAME = 'dispatched_qty'),
  'SELECT 1',
  'ALTER TABLE `production_stock_request_items` ADD COLUMN `dispatched_qty` int(10) unsigned NOT NULL DEFAULT 0'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_stock_request_items' AND COLUMN_NAME = 'received_qty'),
  'SELECT 1',
  'ALTER TABLE `production_stock_request_items` ADD COLUMN `received_qty` int(10) unsigned NOT NULL DEFAULT 0'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_stock_requests' AND COLUMN_NAME = 'from_module'),
  'SELECT 1',
  'ALTER TABLE `production_stock_requests` ADD COLUMN `from_module` varchar(100) NOT NULL DEFAULT ''Central Warehouse'''
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_stock_requests' AND COLUMN_NAME = 'to_module'),
  'SELECT 1',
  'ALTER TABLE `production_stock_requests` ADD COLUMN `to_module` varchar(100) NOT NULL DEFAULT ''Production Unit'''
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_stock_requests' AND COLUMN_NAME = 'from_unit_id'),
  'SELECT 1',
  'ALTER TABLE `production_stock_requests` ADD COLUMN `from_unit_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_stock_requests' AND COLUMN_NAME = 'to_unit_id'),
  'SELECT 1',
  'ALTER TABLE `production_stock_requests` ADD COLUMN `to_unit_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_stock_requests' AND COLUMN_NAME = 'dispatched_pieces'),
  'SELECT 1',
  'ALTER TABLE `production_stock_requests` ADD COLUMN `dispatched_pieces` int(10) unsigned NOT NULL DEFAULT 0'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_stock_requests' AND COLUMN_NAME = 'received_by_send_area_at'),
  'SELECT 1',
  'ALTER TABLE `production_stock_requests` ADD COLUMN `received_by_send_area_at` datetime DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_stock_requests' AND COLUMN_NAME = 'received_by_send_area_by'),
  'SELECT 1',
  'ALTER TABLE `production_stock_requests` ADD COLUMN `received_by_send_area_by` varchar(150) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_stock_requests' AND COLUMN_NAME = 'dispatched_at'),
  'SELECT 1',
  'ALTER TABLE `production_stock_requests` ADD COLUMN `dispatched_at` datetime DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_stock_requests' AND COLUMN_NAME = 'dispatched_by'),
  'SELECT 1',
  'ALTER TABLE `production_stock_requests` ADD COLUMN `dispatched_by` varchar(150) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_stock_requests' AND COLUMN_NAME = 'received_at'),
  'SELECT 1',
  'ALTER TABLE `production_stock_requests` ADD COLUMN `received_at` datetime DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_stock_requests' AND COLUMN_NAME = 'received_by'),
  'SELECT 1',
  'ALTER TABLE `production_stock_requests` ADD COLUMN `received_by` varchar(150) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_stock_requests' AND COLUMN_NAME = 'concern_id'),
  'SELECT 1',
  'ALTER TABLE `production_stock_requests` ADD COLUMN `concern_id` int(11) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_stock_requests' AND INDEX_NAME = 'idx_production_stock_requests_created_at'),
  'SELECT 1',
  'ALTER TABLE `production_stock_requests` ADD INDEX `idx_production_stock_requests_created_at` (`created_at`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_stock_requests' AND INDEX_NAME = 'idx_production_stock_requests_concern_id'),
  'SELECT 1',
  'ALTER TABLE `production_stock_requests` ADD INDEX `idx_production_stock_requests_concern_id` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_units' AND COLUMN_NAME = 'concern_id'),
  'SELECT 1',
  'ALTER TABLE `production_units` ADD COLUMN `concern_id` int(11) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_units' AND COLUMN_NAME = 'logo_url'),
  'SELECT 1',
  'ALTER TABLE `production_units` ADD COLUMN `logo_url` varchar(255) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_units' AND INDEX_NAME = 'idx_production_units_concern'),
  'SELECT 1',
  'ALTER TABLE `production_units` ADD INDEX `idx_production_units_concern` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'attributes'),
  'SELECT 1',
  'ALTER TABLE `products` ADD COLUMN `attributes` text DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_inward_items' AND COLUMN_NAME = 'production_purchase_item_id'),
  'SELECT 1',
  'ALTER TABLE `purchase_inward_items` ADD COLUMN `production_purchase_item_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_inward_items' AND INDEX_NAME = 'idx_pii_production_purchase_item'),
  'SELECT 1',
  'ALTER TABLE `purchase_inward_items` ADD INDEX `idx_pii_production_purchase_item` (`production_purchase_item_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_inwards' AND COLUMN_NAME = 'concern_id'),
  'SELECT 1',
  'ALTER TABLE `purchase_inwards` ADD COLUMN `concern_id` int(11) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_inwards' AND INDEX_NAME = 'idx_purchase_inwards_concern_id'),
  'SELECT 1',
  'ALTER TABLE `purchase_inwards` ADD INDEX `idx_purchase_inwards_concern_id` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_items' AND COLUMN_NAME = 'hsn_code'),
  'SELECT 1',
  'ALTER TABLE `purchase_items` ADD COLUMN `hsn_code` varchar(20) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_orders' AND COLUMN_NAME = 'production_purchase_id'),
  'SELECT 1',
  'ALTER TABLE `purchase_orders` ADD COLUMN `production_purchase_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_orders' AND COLUMN_NAME = 'is_direct_purchase'),
  'SELECT 1',
  'ALTER TABLE `purchase_orders` ADD COLUMN `is_direct_purchase` tinyint(1) NOT NULL DEFAULT 0'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_orders' AND COLUMN_NAME = 'cash_tendered'),
  'SELECT 1',
  'ALTER TABLE `purchase_orders` ADD COLUMN `cash_tendered` decimal(12,2) NOT NULL DEFAULT 0.00'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_orders' AND COLUMN_NAME = 'change_amount'),
  'SELECT 1',
  'ALTER TABLE `purchase_orders` ADD COLUMN `change_amount` decimal(12,2) NOT NULL DEFAULT 0.00'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_orders' AND INDEX_NAME = 'idx_po_production_purchase'),
  'SELECT 1',
  'ALTER TABLE `purchase_orders` ADD INDEX `idx_po_production_purchase` (`production_purchase_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'qc_batches' AND COLUMN_NAME = 'partition_id'),
  'SELECT 1',
  'ALTER TABLE `qc_batches` ADD COLUMN `partition_id` int(10) unsigned DEFAULT 1'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'qc_batches' AND COLUMN_NAME = 'qr_generation_id'),
  'SELECT 1',
  'ALTER TABLE `qc_batches` ADD COLUMN `qr_generation_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'qc_batches' AND COLUMN_NAME = 'concern_id'),
  'SELECT 1',
  'ALTER TABLE `qc_batches` ADD COLUMN `concern_id` int(11) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'qc_batches' AND INDEX_NAME = 'idx_qc_batches_created_at'),
  'SELECT 1',
  'ALTER TABLE `qc_batches` ADD INDEX `idx_qc_batches_created_at` (`created_at`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'qc_batches' AND INDEX_NAME = 'idx_qc_batches_concern_id'),
  'SELECT 1',
  'ALTER TABLE `qc_batches` ADD INDEX `idx_qc_batches_concern_id` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'qc_batches' AND INDEX_NAME = 'idx_qc_batches_concern'),
  'SELECT 1',
  'ALTER TABLE `qc_batches` ADD INDEX `idx_qc_batches_concern` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'qc_batches' AND INDEX_NAME = 'idx_qcb_stitching_status'),
  'SELECT 1',
  'ALTER TABLE `qc_batches` ADD INDEX `idx_qcb_stitching_status` (`stitching_batch_id`,`status`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'qc_batches' AND INDEX_NAME = 'idx_qcb_qr_generation'),
  'SELECT 1',
  'ALTER TABLE `qc_batches` ADD INDEX `idx_qcb_qr_generation` (`qr_generation_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'qc_items' AND COLUMN_NAME = 'total_qty'),
  'SELECT 1',
  'ALTER TABLE `qc_items` ADD COLUMN `total_qty` int(11) NOT NULL DEFAULT 0'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'qc_items' AND INDEX_NAME = 'idx_qci_batch_passed'),
  'SELECT 1',
  'ALTER TABLE `qc_items` ADD INDEX `idx_qci_batch_passed` (`qc_batch_id`,`passed_qty`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roles' AND COLUMN_NAME = 'concern_scope'),
  'SELECT 1',
  'ALTER TABLE `roles` ADD COLUMN `concern_scope` varchar(20) NOT NULL DEFAULT ''ALL'''
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roles' AND COLUMN_NAME = 'applicable_concerns'),
  'SELECT 1',
  'ALTER TABLE `roles` ADD COLUMN `applicable_concerns` text DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roll_damages' AND COLUMN_NAME = 'partition_id'),
  'SELECT 1',
  'ALTER TABLE `roll_damages` ADD COLUMN `partition_id` int(10) unsigned DEFAULT 1'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roll_damages' AND COLUMN_NAME = 'concern_id'),
  'SELECT 1',
  'ALTER TABLE `roll_damages` ADD COLUMN `concern_id` int(11) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roll_damages' AND INDEX_NAME = 'idx_roll_damages_qr_code'),
  'SELECT 1',
  'ALTER TABLE `roll_damages` ADD INDEX `idx_roll_damages_qr_code` (`qr_code`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roll_damages' AND INDEX_NAME = 'idx_roll_damages_concern_id'),
  'SELECT 1',
  'ALTER TABLE `roll_damages` ADD INDEX `idx_roll_damages_concern_id` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'seller_rolls' AND COLUMN_NAME = 'partition_id'),
  'SELECT 1',
  'ALTER TABLE `seller_rolls` ADD COLUMN `partition_id` int(10) unsigned DEFAULT 1'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'seller_rolls' AND COLUMN_NAME = 'hsn_code'),
  'SELECT 1',
  'ALTER TABLE `seller_rolls` ADD COLUMN `hsn_code` varchar(20) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'seller_rolls' AND INDEX_NAME = 'idx_seller_rolls_created_at'),
  'SELECT 1',
  'ALTER TABLE `seller_rolls` ADD INDEX `idx_seller_rolls_created_at` (`created_at`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sellers' AND COLUMN_NAME = 'partition_id'),
  'SELECT 1',
  'ALTER TABLE `sellers` ADD COLUMN `partition_id` int(10) unsigned DEFAULT 1'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sellers' AND COLUMN_NAME = 'production_deleted_at'),
  'SELECT 1',
  'ALTER TABLE `sellers` ADD COLUMN `production_deleted_at` datetime DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'shops' AND COLUMN_NAME = 'concern_id'),
  'SELECT 1',
  'ALTER TABLE `shops` ADD COLUMN `concern_id` int(11) DEFAULT 1'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'shops' AND INDEX_NAME = 'idx_shops_concern'),
  'SELECT 1',
  'ALTER TABLE `shops` ADD INDEX `idx_shops_concern` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stitching_batches' AND COLUMN_NAME = 'partition_id'),
  'SELECT 1',
  'ALTER TABLE `stitching_batches` ADD COLUMN `partition_id` int(10) unsigned DEFAULT 1'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stitching_batches' AND COLUMN_NAME = 'qr_generation_id'),
  'SELECT 1',
  'ALTER TABLE `stitching_batches` ADD COLUMN `qr_generation_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stitching_batches' AND COLUMN_NAME = 'qr_batch_code'),
  'SELECT 1',
  'ALTER TABLE `stitching_batches` ADD COLUMN `qr_batch_code` varchar(50) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stitching_batches' AND COLUMN_NAME = 'concern_id'),
  'SELECT 1',
  'ALTER TABLE `stitching_batches` ADD COLUMN `concern_id` int(11) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stitching_batches' AND INDEX_NAME = 'idx_stitching_batches_created_at'),
  'SELECT 1',
  'ALTER TABLE `stitching_batches` ADD INDEX `idx_stitching_batches_created_at` (`created_at`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stitching_batches' AND INDEX_NAME = 'idx_stitching_batches_concern_id'),
  'SELECT 1',
  'ALTER TABLE `stitching_batches` ADD INDEX `idx_stitching_batches_concern_id` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stitching_batches' AND INDEX_NAME = 'idx_stitching_batches_concern'),
  'SELECT 1',
  'ALTER TABLE `stitching_batches` ADD INDEX `idx_stitching_batches_concern` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stitching_batches' AND INDEX_NAME = 'idx_sb_qrg_concern_status'),
  'SELECT 1',
  'ALTER TABLE `stitching_batches` ADD INDEX `idx_sb_qrg_concern_status` (`qr_generation_id`,`concern_id`,`status`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stitching_wastage_sales' AND COLUMN_NAME = 'concern_id'),
  'SELECT 1',
  'ALTER TABLE `stitching_wastage_sales` ADD COLUMN `concern_id` int(11) DEFAULT 1'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stitching_wastage_sales' AND INDEX_NAME = 'idx_stitching_wastage_sales_date'),
  'SELECT 1',
  'ALTER TABLE `stitching_wastage_sales` ADD INDEX `idx_stitching_wastage_sales_date` (`sale_date`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stitching_wastage_sales' AND INDEX_NAME = 'idx_stitching_wastage_sales_concern'),
  'SELECT 1',
  'ALTER TABLE `stitching_wastage_sales` ADD INDEX `idx_stitching_wastage_sales_concern` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_adjustments' AND COLUMN_NAME = 'concern_id'),
  'SELECT 1',
  'ALTER TABLE `stock_adjustments` ADD COLUMN `concern_id` int(11) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_adjustments' AND INDEX_NAME = 'idx_stock_adjustments_concern_id'),
  'SELECT 1',
  'ALTER TABLE `stock_adjustments` ADD INDEX `idx_stock_adjustments_concern_id` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_balances' AND COLUMN_NAME = 'concern_id'),
  'SELECT 1',
  'ALTER TABLE `stock_balances` ADD COLUMN `concern_id` int(11) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_balances' AND INDEX_NAME = 'idx_stock_balances_concern_id'),
  'SELECT 1',
  'ALTER TABLE `stock_balances` ADD INDEX `idx_stock_balances_concern_id` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_balances' AND INDEX_NAME = 'idx_stock_balances_concern'),
  'SELECT 1',
  'ALTER TABLE `stock_balances` ADD INDEX `idx_stock_balances_concern` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_transactions' AND COLUMN_NAME = 'before_quantity'),
  'SELECT 1',
  'ALTER TABLE `stock_transactions` ADD COLUMN `before_quantity` decimal(10,2) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_transactions' AND COLUMN_NAME = 'after_quantity'),
  'SELECT 1',
  'ALTER TABLE `stock_transactions` ADD COLUMN `after_quantity` decimal(10,2) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_transactions' AND COLUMN_NAME = 'from_location_type'),
  'SELECT 1',
  'ALTER TABLE `stock_transactions` ADD COLUMN `from_location_type` varchar(50) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_transactions' AND COLUMN_NAME = 'from_location_id'),
  'SELECT 1',
  'ALTER TABLE `stock_transactions` ADD COLUMN `from_location_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_transactions' AND COLUMN_NAME = 'to_location_type'),
  'SELECT 1',
  'ALTER TABLE `stock_transactions` ADD COLUMN `to_location_type` varchar(50) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_transactions' AND COLUMN_NAME = 'to_location_id'),
  'SELECT 1',
  'ALTER TABLE `stock_transactions` ADD COLUMN `to_location_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_transactions' AND COLUMN_NAME = 'concern_id'),
  'SELECT 1',
  'ALTER TABLE `stock_transactions` ADD COLUMN `concern_id` int(11) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_transactions' AND INDEX_NAME = 'idx_stock_transactions_concern_id'),
  'SELECT 1',
  'ALTER TABLE `stock_transactions` ADD INDEX `idx_stock_transactions_concern_id` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_transactions' AND INDEX_NAME = 'idx_stock_transactions_concern'),
  'SELECT 1',
  'ALTER TABLE `stock_transactions` ADD INDEX `idx_stock_transactions_concern` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'staff_role'),
  'SELECT 1',
  'ALTER TABLE `users` ADD COLUMN `staff_role` varchar(50) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'default_concern_id'),
  'SELECT 1',
  'ALTER TABLE `users` ADD COLUMN `default_concern_id` int(11) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'warehouse_damages' AND COLUMN_NAME = 'resolution_action'),
  'SELECT 1',
  'ALTER TABLE `warehouse_damages` ADD COLUMN `resolution_action` varchar(32) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'warehouse_damages' AND COLUMN_NAME = 'resolution_reason'),
  'SELECT 1',
  'ALTER TABLE `warehouse_damages` ADD COLUMN `resolution_reason` varchar(255) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'warehouse_damages' AND COLUMN_NAME = 'resolution_notes'),
  'SELECT 1',
  'ALTER TABLE `warehouse_damages` ADD COLUMN `resolution_notes` text DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'warehouse_damages' AND COLUMN_NAME = 'resolution_amount'),
  'SELECT 1',
  'ALTER TABLE `warehouse_damages` ADD COLUMN `resolution_amount` decimal(10,2) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'warehouse_damages' AND COLUMN_NAME = 'replacement_variant_id'),
  'SELECT 1',
  'ALTER TABLE `warehouse_damages` ADD COLUMN `replacement_variant_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'warehouse_damages' AND COLUMN_NAME = 'replacement_qr_code'),
  'SELECT 1',
  'ALTER TABLE `warehouse_damages` ADD COLUMN `replacement_qr_code` varchar(100) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'warehouse_damages' AND COLUMN_NAME = 'replacement_inwarded_at'),
  'SELECT 1',
  'ALTER TABLE `warehouse_damages` ADD COLUMN `replacement_inwarded_at` datetime DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'warehouse_damages' AND COLUMN_NAME = 'deleted_at'),
  'SELECT 1',
  'ALTER TABLE `warehouse_damages` ADD COLUMN `deleted_at` datetime DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'warehouse_damages' AND COLUMN_NAME = 'deleted_by'),
  'SELECT 1',
  'ALTER TABLE `warehouse_damages` ADD COLUMN `deleted_by` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'warehouses' AND COLUMN_NAME = 'concern_id'),
  'SELECT 1',
  'ALTER TABLE `warehouses` ADD COLUMN `concern_id` int(11) DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'warehouses' AND INDEX_NAME = 'idx_warehouses_concern_id'),
  'SELECT 1',
  'ALTER TABLE `warehouses` ADD INDEX `idx_warehouses_concern_id` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'warehouses' AND INDEX_NAME = 'idx_warehouses_concern'),
  'SELECT 1',
  'ALTER TABLE `warehouses` ADD INDEX `idx_warehouses_concern` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wastage_sales' AND COLUMN_NAME = 'concern_id'),
  'SELECT 1',
  'ALTER TABLE `wastage_sales` ADD COLUMN `concern_id` int(11) DEFAULT 1'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wastage_sales' AND COLUMN_NAME = 'partition_id'),
  'SELECT 1',
  'ALTER TABLE `wastage_sales` ADD COLUMN `partition_id` int(10) unsigned DEFAULT 1'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wastage_sales' AND INDEX_NAME = 'idx_wastage_sales_date'),
  'SELECT 1',
  'ALTER TABLE `wastage_sales` ADD INDEX `idx_wastage_sales_date` (`sale_date`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wastage_sales' AND INDEX_NAME = 'idx_wastage_sales_concern'),
  'SELECT 1',
  'ALTER TABLE `wastage_sales` ADD INDEX `idx_wastage_sales_concern` (`concern_id`)'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

-- Compatible data-preserving type expansions from later migrations.
ALTER TABLE `cutting_items` MODIFY COLUMN `weight_per_piece` decimal(10,4) DEFAULT 0.0000;
ALTER TABLE `stitching_items` MODIFY COLUMN `weight_per_piece` decimal(10,4) DEFAULT 0.0000;
ALTER TABLE `hsn_codes` MODIFY COLUMN `description` text NOT NULL, MODIFY COLUMN `gst_rate` decimal(5,2) DEFAULT NULL;
ALTER TABLE `production_purchase_items` MODIFY COLUMN `item_type` enum('FABRIC_ROLL','RAW_MATERIAL','THIRD_PARTY','OWN_PRODUCT','GENERAL_SUPPLY') NOT NULL DEFAULT 'FABRIC_ROLL';
ALTER TABLE `production_stock_request_items` MODIFY COLUMN `status` enum('PENDING','RECEIVED_BY_SEND_AREA','PARTIAL','ALLOCATED','DISPATCHED','FULFILLED','REJECTED') NOT NULL DEFAULT 'PENDING';
ALTER TABLE `production_stock_requests` MODIFY COLUMN `status` enum('PENDING','RECEIVED_BY_SEND_AREA','APPROVED','IN_PRODUCTION','READY_FOR_DISPATCH','DISPATCHED','FULFILLED','REJECTED') NOT NULL DEFAULT 'PENDING';
ALTER TABLE `roll_damages` MODIFY COLUMN `status` enum('LOGGED','INVESTIGATING','CLAIMED','SETTLED','REJECTED','CLOSED','CANCELLED','REPLACEMENT_RECEIVED','RETURN_INITIATED','RETURNED_TO_SELLER','REFUND_RECEIVED') NOT NULL DEFAULT 'LOGGED';
ALTER TABLE `shop_damages` MODIFY COLUMN `status` enum('LOGGED','INSPECTED','SALVAGED','WRITTEN_OFF','REPAIRED','RETURN_TO_WH','REWORK','CANCELLED') DEFAULT 'LOGGED';
ALTER TABLE `product_variants` MODIFY COLUMN `attr_hash` varchar(64) DEFAULT NULL;

SET FOREIGN_KEY_CHECKS = 1;
