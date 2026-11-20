-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: grm_inventory
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `allocation_items`
--

DROP TABLE IF EXISTS `allocation_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `allocation_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `allocation_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned NOT NULL,
  `requested_qty` int(11) NOT NULL,
  `approved_qty` int(11) DEFAULT 0,
  `picked_qty` int(11) DEFAULT 0,
  `packed_qty` int(11) DEFAULT 0,
  `dispatched_qty` int(11) DEFAULT 0,
  `selected_qr_codes` text DEFAULT NULL,
  `received_qr_codes` longtext DEFAULT NULL,
  `received_qty` int(11) NOT NULL DEFAULT 0,
  `damaged_qty` int(11) NOT NULL DEFAULT 0,
  `missing_qty` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `allocation_id` (`allocation_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `allocation_items_ibfk_1` FOREIGN KEY (`allocation_id`) REFERENCES `warehouse_allocations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `allocation_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `allocation_items`
--

LOCK TABLES `allocation_items` WRITE;
/*!40000 ALTER TABLE `allocation_items` DISABLE KEYS */;
INSERT INTO `allocation_items` VALUES (1,1,7,5,5,0,0,5,'TTT112651',NULL,0,0,0),(2,2,4,1,1,0,0,1,'MSF112651','[\"MSF112651\",\"MSF112652\",\"MSF112653\",\"MSF112654\",\"MSF112656\",\"MSF112657\",\"MSF112658\",\"MSF112659\",\"MSF112660\",\"MSF112655\"]',10,1,0),(3,2,4,1,1,0,0,1,'MSF112652','[\"MSF112651\",\"MSF112652\",\"MSF112653\",\"MSF112654\",\"MSF112656\",\"MSF112657\",\"MSF112658\",\"MSF112659\",\"MSF112660\",\"MSF112655\"]',10,1,0),(4,2,4,1,1,0,0,1,'MSF112653','[\"MSF112651\",\"MSF112652\",\"MSF112653\",\"MSF112654\",\"MSF112656\",\"MSF112657\",\"MSF112658\",\"MSF112659\",\"MSF112660\",\"MSF112655\"]',10,1,0),(5,2,4,1,1,0,0,1,'MSF112654','[\"MSF112651\",\"MSF112652\",\"MSF112653\",\"MSF112654\",\"MSF112656\",\"MSF112657\",\"MSF112658\",\"MSF112659\",\"MSF112660\",\"MSF112655\"]',10,1,0),(6,2,4,1,1,0,0,1,'MSF112655','[\"MSF112651\",\"MSF112652\",\"MSF112653\",\"MSF112654\",\"MSF112656\",\"MSF112657\",\"MSF112658\",\"MSF112659\",\"MSF112660\",\"MSF112655\"]',10,1,0),(7,2,4,1,1,0,0,1,'MSF112656','[\"MSF112651\",\"MSF112652\",\"MSF112653\",\"MSF112654\",\"MSF112656\",\"MSF112657\",\"MSF112658\",\"MSF112659\",\"MSF112660\",\"MSF112655\"]',10,1,0),(8,2,4,1,1,0,0,1,'MSF112657','[\"MSF112651\",\"MSF112652\",\"MSF112653\",\"MSF112654\",\"MSF112656\",\"MSF112657\",\"MSF112658\",\"MSF112659\",\"MSF112660\",\"MSF112655\"]',10,1,0),(9,2,4,1,1,0,0,1,'MSF112658','[\"MSF112651\",\"MSF112652\",\"MSF112653\",\"MSF112654\",\"MSF112656\",\"MSF112657\",\"MSF112658\",\"MSF112659\",\"MSF112660\",\"MSF112655\"]',10,1,0),(10,2,4,1,1,0,0,1,'MSF112659','[\"MSF112651\",\"MSF112652\",\"MSF112653\",\"MSF112654\",\"MSF112656\",\"MSF112657\",\"MSF112658\",\"MSF112659\",\"MSF112660\",\"MSF112655\"]',10,1,0),(11,2,4,1,1,0,0,1,'MSF112660','[\"MSF112651\",\"MSF112652\",\"MSF112653\",\"MSF112654\",\"MSF112656\",\"MSF112657\",\"MSF112658\",\"MSF112659\",\"MSF112660\",\"MSF112655\"]',10,1,0);
/*!40000 ALTER TABLE `allocation_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `allocation_outwards`
--

DROP TABLE IF EXISTS `allocation_outwards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `allocation_outwards`
--

LOCK TABLES `allocation_outwards` WRITE;
/*!40000 ALTER TABLE `allocation_outwards` DISABLE KEYS */;
INSERT INTO `allocation_outwards` VALUES (1,'OUT-ALC-20261109-0001',1,1,'SHOP',1,1,NULL,1,5,'ASKLDJF','kldjfkda','9803487589','kakfdg','mdf',NULL,'2026-11-09 15:08:00','DISPATCHED',NULL,'2026-11-09 15:08:59','2026-11-09 15:08:59',0,NULL,'2026-11-09 15:08:59'),(2,'OUT-ALC-20261109-0002',2,1,'SHOP',1,1,NULL,1,10,'SADF','test','9789660115','ksj',NULL,NULL,'2026-11-09 21:23:00','DELIVERED',NULL,'2026-11-09 21:24:05','2026-11-09 21:26:20',0,NULL,'2026-11-09 21:24:05');
/*!40000 ALTER TABLE `allocation_outwards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_audit_items`
--

DROP TABLE IF EXISTS `asset_audit_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_audit_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `audit_id` int(10) unsigned NOT NULL,
  `asset_id` int(10) unsigned NOT NULL,
  `status` enum('VERIFIED_OK','DAMAGED','MISSING','WRONG_LOCATION') NOT NULL DEFAULT 'VERIFIED_OK',
  `scanned_at` datetime DEFAULT NULL,
  `observed_condition` enum('EXCELLENT','GOOD','FAIR','POOR','CRITICAL') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_audit` (`audit_id`),
  KEY `idx_asset` (`asset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_audit_items`
--

LOCK TABLES `asset_audit_items` WRITE;
/*!40000 ALTER TABLE `asset_audit_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `asset_audit_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_audits`
--

DROP TABLE IF EXISTS `asset_audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_audits` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `audit_number` varchar(50) NOT NULL,
  `location_type` enum('PRODUCTION','WAREHOUSE','SHOP','OFFICE') NOT NULL,
  `location_id` int(10) unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `audit_date` date NOT NULL,
  `status` enum('PLANNED','IN_PROGRESS','COMPLETED','CANCELLED') NOT NULL DEFAULT 'COMPLETED',
  `total_assets_expected` int(11) NOT NULL DEFAULT 0,
  `total_assets_verified` int(11) NOT NULL DEFAULT 0,
  `total_assets_missing` int(11) NOT NULL DEFAULT 0,
  `total_assets_damaged` int(11) NOT NULL DEFAULT 0,
  `auditor_id` int(10) unsigned DEFAULT NULL,
  `auditor_name` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `audit_number` (`audit_number`),
  KEY `idx_loc` (`location_type`,`location_id`),
  KEY `idx_status` (`status`),
  KEY `idx_date` (`audit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_audits`
--

LOCK TABLES `asset_audits` WRITE;
/*!40000 ALTER TABLE `asset_audits` DISABLE KEYS */;
/*!40000 ALTER TABLE `asset_audits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_categories`
--

DROP TABLE IF EXISTS `asset_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_categories`
--

LOCK TABLES `asset_categories` WRITE;
/*!40000 ALTER TABLE `asset_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `asset_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_maintenance_logs`
--

DROP TABLE IF EXISTS `asset_maintenance_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_maintenance_logs`
--

LOCK TABLES `asset_maintenance_logs` WRITE;
/*!40000 ALTER TABLE `asset_maintenance_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `asset_maintenance_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_transfers`
--

DROP TABLE IF EXISTS `asset_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_transfers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `transfer_number` varchar(50) NOT NULL,
  `asset_id` int(10) unsigned NOT NULL,
  `from_location_type` enum('PRODUCTION','WAREHOUSE','SHOP','OFFICE') NOT NULL,
  `from_location_id` int(10) unsigned NOT NULL,
  `from_floor_area` varchar(150) DEFAULT NULL,
  `to_location_type` enum('PRODUCTION','WAREHOUSE','SHOP','OFFICE') NOT NULL,
  `to_location_id` int(10) unsigned NOT NULL,
  `to_floor_area` varchar(150) DEFAULT NULL,
  `transfer_date` datetime NOT NULL,
  `status` enum('INITIATED','IN_TRANSIT','COMPLETED','REJECTED') NOT NULL DEFAULT 'COMPLETED',
  `transferred_by` int(10) unsigned DEFAULT NULL,
  `received_by` int(10) unsigned DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `challan_notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transfer_number` (`transfer_number`),
  KEY `idx_asset` (`asset_id`),
  KEY `idx_from` (`from_location_type`,`from_location_id`),
  KEY `idx_to` (`to_location_type`,`to_location_id`),
  KEY `idx_status` (`status`),
  KEY `idx_date` (`transfer_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_transfers`
--

LOCK TABLES `asset_transfers` WRITE;
/*!40000 ALTER TABLE `asset_transfers` DISABLE KEYS */;
/*!40000 ALTER TABLE `asset_transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assets`
--

LOCK TABLES `assets` WRITE;
/*!40000 ALTER TABLE `assets` DISABLE KEYS */;
/*!40000 ALTER TABLE `assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'production','CREATE','sellers',1,NULL,'{\"Supplier Name\":\"ABI\",\"Company \\/ Mill\":\"GRM\",\"Phone\":\"9789660115\",\"Email\":\"abiprasath6@gmail.com\",\"City\":\"TIRUPPUR\",\"State\":\"TAMIL NADU\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-08 22:04:01',NULL,NULL,NULL,NULL),(2,1,'production','CREATE','production_purchases',1,NULL,'{\"purchase_number\":\"PP-2026-000001\",\"seller_id\":1,\"total_amount\":178250,\"item_count\":3}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-08 22:09:46',NULL,NULL,NULL,NULL),(3,1,'production','CREATE','products',1,NULL,'{\"name\":\"MENS - SHIRT - FULL SLEEVE\",\"code\":\"GRM-STY-0001\",\"variants\":2}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-08 22:34:39',NULL,NULL,NULL,NULL),(4,1,'production','CREATE','production_programs',1,NULL,'{\"name\":\"SHIRT PROGRAM\",\"program_number\":\"PRG-20261108-0001\",\"roll_count\":1}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-08 22:39:10',NULL,NULL,NULL,NULL),(5,1,'production','CREATE','cutting_batches',1,NULL,'{\"name\":\"PROGRAM CUTTING\",\"program_id\":1,\"batch_number\":\"CUT-20261108-0001\",\"seller_roll_id\":1,\"roll_weight\":3,\"roll_price\":5000,\"wastage_weight\":0,\"wastage_cost\":0,\"used_weight\":3,\"cost_per_piece\":50,\"category_id\":4,\"subcategory_id\":1,\"sub_subcategory_id\":1,\"operator_id\":1,\"production_unit_id\":null,\"material_issued\":\"Roll Weight: 3 kg | Wastage: 0 kg (Ôé╣0) | Used: 3 kg\",\"planned_qty\":100,\"cut_qty\":100,\"rejected_qty\":0,\"wastage_qty\":0,\"status\":\"IN_PROGRESS\",\"notes\":\"\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-08 22:39:46',NULL,NULL,NULL,NULL),(6,1,'production','EDIT','cutting_batches',1,'{\"id\":1,\"program_id\":1,\"seller_roll_id\":1,\"roll_weight\":\"3.00\",\"roll_price\":\"5000.00\",\"wastage_weight\":\"0.00\",\"wastage_cost\":\"0.00\",\"used_weight\":\"3.00\",\"cost_per_piece\":\"50.00\",\"category_id\":4,\"subcategory_id\":1,\"sub_subcategory_id\":1,\"batch_number\":\"CUT-20261108-0001\",\"name\":\"PROGRAM CUTTING\",\"operator_id\":1,\"production_unit_id\":null,\"material_issued\":\"Roll Weight: 3 kg | Wastage: 0 kg (Ôé╣0) | Used: 3 kg\",\"planned_qty\":100,\"cut_qty\":100,\"rejected_qty\":0,\"wastage_qty\":0,\"status\":\"IN_PROGRESS\",\"notes\":\"\",\"is_active\":1,\"created_at\":\"2026-11-08 22:39:46\",\"updated_at\":\"2026-11-08 22:39:46\",\"cutting_master\":null,\"operator_name\":\"System Administrator\",\"start_date\":\"2026-11-08\",\"completion_date\":null,\"created_by\":null,\"program_name\":\"SHIRT PROGRAM\",\"program_number\":\"PRG-20261108-0001\",\"target_date\":\"2026-11-22\",\"end_date\":\"2026-11-22\",\"roll_number\":\"ROLL #1\",\"roll_color\":\"RED\",\"roll_design\":\"RED-PANDA\",\"seller_name\":\"ABI\",\"seller_company\":\"GRM\",\"material_type\":null,\"category_name\":\"MENS\",\"subcategory_name\":\"SHIRT\",\"sub_subcategory_name\":\"FULL SLEEVE\",\"unit_name\":null,\"unit_code\":null,\"product_name\":\"MENS - SHIRT - FULL SLEEVE\",\"product_code\":\"GRM-STY-0001\"}','{\"name\":\"PROGRAM CUTTING\",\"roll_weight\":3,\"roll_price\":5000,\"wastage_weight\":0,\"wastage_cost\":0,\"used_weight\":3,\"cost_per_piece\":45.45,\"operator_id\":1,\"production_unit_id\":null,\"planned_qty\":110,\"cut_qty\":110,\"rejected_qty\":0,\"wastage_qty\":0,\"status\":\"IN_PROGRESS\",\"notes\":\"\",\"seller_roll_id\":1}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-08 22:40:11',NULL,NULL,NULL,NULL),(7,1,'production','CREATE','stitching_batch',1,NULL,'{\"name\":\"PROGRAM STITCHING\",\"batch_number\":\"STCH-20261108-0001\",\"program_id\":1,\"cutting_batch_id\":1,\"category_id\":4,\"subcategory_id\":1,\"sub_subcategory_id\":1,\"worker_id\":1,\"start_date\":null,\"end_date\":null,\"received_qty\":110,\"stitched_qty\":110,\"total_weight\":2.97,\"status\":\"IN_PROGRESS\",\"notes\":\"\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-08 22:41:03',NULL,NULL,NULL,NULL),(8,1,'production','CREATE','label_batch',1,NULL,'{\"program_id\":1,\"stitching_batch_id\":1,\"batch_number\":\"LBL-20261108-0001\",\"status\":\"COMPLETED\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-08 22:42:45',NULL,NULL,NULL,NULL),(9,1,'production','CREATE','production_batch',1,NULL,'{\"program_id\":1,\"qc_batch_id\":1,\"destination_warehouse_id\":1,\"box_count\":1,\"status\":\"PREPARING\",\"include_rework\":false,\"notes\":null,\"batch_number\":\"BAT-20261108-0001\",\"prepared_by\":1,\"total_qty\":105}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-08 22:46:32',NULL,NULL,NULL,NULL),(10,1,'production','APPROVE','production_batch',1,'{\"status\":\"PREPARING\"}','{\"status\":\"APPROVED\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-08 22:46:37',NULL,NULL,NULL,NULL),(11,1,'production','CREATE','production_outward',1,NULL,'{\"production_batch_id\":1,\"dispatch_date\":\"2026-11-08T22:46\",\"vehicle_number\":\"tn39cp0015\",\"notes\":\"\",\"outward_number\":\"OUT-20261108-0001\",\"created_by\":1}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-08 22:46:52',NULL,NULL,NULL,NULL),(12,1,'warehouse','INWARD_DAMAGE_LOGGED','warehouse_damages',1,NULL,'{\"damage_number\":\"DMG-20261108-0001\",\"batch_id\":1,\"batch_number\":\"BAT-20261108-0001\",\"production_unit_name\":\"Main Production Facility\",\"production_unit_code\":\"PU1\",\"variant_id\":3,\"sku\":\"GRM-STY-0001-S-RED\",\"damaged_qty\":5,\"warehouse_id\":1,\"cost_per_unit\":700,\"total_loss\":3500}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-08 22:50:49',NULL,NULL,NULL,NULL),(13,1,'warehouse','RECEIVE_GRN','production_batch',1,'{\"status\":\"DISPATCHED\"}','{\"status\":\"RECEIVED\",\"warehouse_id\":1,\"total_expected\":105,\"total_accepted\":100,\"total_damaged\":5,\"total_missing\":0,\"notes\":\" (Incremental Inward: +100 accepted, +5 damaged)\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-08 22:50:49',NULL,NULL,NULL,NULL),(14,1,'warehouse','CREATE_SELLER_PHASE1','warehouse_suppliers',1,NULL,'{\"name\":\"MEKHA\",\"company\":\"GRM\'S\",\"brand_id\":1,\"phone\":\"9500903648\",\"seller_type\":\"PRODUCT_SELLER\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-08 23:07:56',NULL,NULL,NULL,NULL),(15,1,'warehouse','STORE_SELLER_PRODUCTS_PENDING_QR','purchase_orders',1,NULL,'{\"supplier_id\":1,\"total_products\":3,\"total_amount\":28350,\"po_number\":\"PO-WH-20261108-0001\",\"status\":\"DRAFT\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-08 23:11:24',NULL,NULL,NULL,NULL),(16,1,'warehouse','INWARD','stock_balances',7,NULL,'{\"action\":\"THIRD_PARTY_STICKERED_INWARD\",\"variant_id\":7,\"sku\":\"SKU-CAT-MEKHA-0004-M\",\"quantity\":30,\"warehouse_id\":1,\"warehouse\":\"Central Distribution Warehouse\",\"new_balance\":\"30.00\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-08 23:12:26',NULL,NULL,NULL,NULL),(17,1,'warehouse','USE_EXISTING_SELLER_PHASE1','warehouse_suppliers',1,NULL,'{\"name\":\"MEKHA\",\"company\":\"GRM\'S\",\"brand_id\":1,\"phone\":\"9500903648\",\"seller_type\":\"PRODUCT_SELLER\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-09 12:01:52',NULL,NULL,NULL,NULL),(18,1,'warehouse','ALLOCATION_CREATE','warehouse_allocations',1,NULL,'{\"number\":\"ALC-20261109-0001\",\"warehouse_id\":1}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-09 14:58:02',NULL,NULL,NULL,NULL),(19,1,'production','CREATE','production_programs',2,NULL,'{\"name\":\"SECOND PROGRAM\",\"program_number\":\"PRG-20261109-0002\",\"roll_count\":1}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-09 15:00:13',NULL,NULL,NULL,NULL),(20,1,'production','CREATE','cutting_batches',2,NULL,'{\"name\":\"SECOND CUTTING\",\"program_id\":2,\"batch_number\":\"CUT-20261109-0002\",\"seller_roll_id\":3,\"roll_weight\":2,\"roll_price\":6500,\"wastage_weight\":0,\"wastage_cost\":0,\"used_weight\":2,\"cost_per_piece\":65,\"category_id\":4,\"subcategory_id\":1,\"sub_subcategory_id\":1,\"operator_id\":null,\"production_unit_id\":null,\"material_issued\":\"Roll Weight: 2 kg | Wastage: 0 kg (Ôé╣0) | Used: 2 kg\",\"planned_qty\":100,\"cut_qty\":100,\"rejected_qty\":0,\"wastage_qty\":0,\"status\":\"IN_PROGRESS\",\"notes\":\"\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-09 15:00:51',NULL,NULL,NULL,NULL),(21,1,'production','CREATE','stitching_batch',2,NULL,'{\"name\":\"SECOND STITCHING\",\"batch_number\":\"STCH-20261109-0002\",\"program_id\":2,\"cutting_batch_id\":2,\"category_id\":4,\"subcategory_id\":1,\"sub_subcategory_id\":1,\"worker_id\":1,\"start_date\":null,\"end_date\":null,\"received_qty\":100,\"stitched_qty\":100,\"total_weight\":2,\"status\":\"IN_PROGRESS\",\"notes\":\"\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-09 15:01:29',NULL,NULL,NULL,NULL),(22,1,'production','CREATE','label_batch',2,NULL,'{\"program_id\":2,\"stitching_batch_id\":2,\"batch_number\":\"LBL-20261109-0002\",\"status\":\"COMPLETED\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-09 15:02:01',NULL,NULL,NULL,NULL),(23,1,'warehouse','ALLOCATION_OUTWARD','allocation_outwards',1,NULL,'{\"outward_number\":\"OUT-ALC-20261109-0001\",\"allocation_id\":1}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-09 15:08:59',NULL,NULL,NULL,NULL),(24,1,'warehouse','INWARD','stock_balances',6,NULL,'{\"action\":\"THIRD_PARTY_STICKERED_INWARD\",\"variant_id\":6,\"sku\":\"SKU-CAT-MEKHA-0003-S\",\"quantity\":40,\"warehouse_id\":1,\"warehouse\":\"Central Distribution Warehouse\",\"new_balance\":\"40.00\"}','127.0.0.1','CLI/System','2026-11-09 16:48:02',NULL,NULL,NULL,NULL),(26,1,'warehouse','USE_EXISTING_SELLER_PHASE1','warehouse_suppliers',1,NULL,'{\"name\":\"MEKHA\",\"company\":\"GRM\'S\",\"brand_id\":1,\"phone\":\"9500903648\",\"seller_type\":\"PRODUCT_SELLER\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-09 19:35:54',NULL,NULL,NULL,NULL),(27,1,'warehouse','STORE_SELLER_PRODUCTS_PENDING_QR','purchase_orders',2,NULL,'{\"supplier_id\":1,\"total_products\":1,\"total_amount\":189,\"po_number\":\"PO-WH-20261109-0001\",\"status\":\"DRAFT\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-09 19:37:32',NULL,NULL,NULL,NULL),(28,1,'warehouse','INWARD','stock_balances',10,NULL,'{\"action\":\"THIRD_PARTY_STICKERED_INWARD\",\"variant_id\":10,\"sku\":\"SKU-CAT-MEKHA-0005-XS\",\"quantity\":120,\"warehouse_id\":1,\"warehouse\":\"Central Distribution Warehouse\",\"new_balance\":\"120.00\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-09 19:38:34',NULL,NULL,NULL,NULL),(29,1,'warehouse','CREATE','purchase_orders',3,NULL,'{\"customer_id\":1,\"warehouse_id\":1,\"order_date\":\"2026-11-09\",\"expected_date\":\"2026-11-09\",\"payment_method\":\"CASH\",\"payment_status\":\"PAID\",\"gst_type\":\"CGST_SGST\",\"status\":\"COMPLETED\",\"notes\":\"\",\"created_by\":1,\"po_number\":\"DPO-20261109-0001\",\"subtotal\":1333.34,\"discount_amount\":2600,\"cgst_amount\":33.34,\"sgst_amount\":33.32,\"igst_amount\":0,\"tax_amount\":66.66,\"total_amount\":1400}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-09 20:47:51',NULL,NULL,NULL,NULL),(30,1,'warehouse','ALLOCATION_CREATE','warehouse_allocations',2,NULL,'{\"number\":\"ALC-20261109-0002\",\"warehouse_id\":1}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-09 21:23:49',NULL,NULL,NULL,NULL),(31,1,'warehouse','ALLOCATION_OUTWARD','allocation_outwards',2,NULL,'{\"outward_number\":\"OUT-ALC-20261109-0002\",\"allocation_id\":2}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-09 21:24:06',NULL,NULL,NULL,NULL),(32,1,'shop','INWARD_DAMAGE_LOGGED','shop_damages',1,NULL,'{\"damage_number\":\"SH-DMG-20261109-0001\",\"allocation_id\":2,\"allocation_number\":\"ALC-20261109-0002\",\"variant_id\":4,\"damaged_qty\":1,\"tag_code\":\"MSF112655\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-09 21:26:20',NULL,NULL,NULL,NULL),(33,1,'shop','ALLOCATION_INWARD_RECEIVE','warehouse_allocations',2,NULL,'{\"number\":\"ALC-20261109-0002\",\"shop_id\":1,\"notes\":\"\",\"status\":\"RECEIVED\",\"received_pcs\":10,\"expected_pcs\":10}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-09 21:26:20',NULL,NULL,NULL,NULL),(34,1,'shop','RETURN_TO_WAREHOUSE_DISPATCH','warehouse_returns',1,NULL,'{\"return_number\":\"RET-20261109-0001\",\"shop_id\":1,\"warehouse_id\":1,\"items_count\":1,\"outward\":{\"carrier_name\":\"GRM Dedicated Logistics\",\"vehicle_no\":\"GH67HBJ\",\"driver_name\":\"kjh\",\"driver_phone\":\"\",\"tracking_no\":\"ihu\",\"carton_count\":1,\"dispatch_date\":\"2026-11-09 21:28:11\",\"remarks\":\"jhgjl\"}}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-09 21:28:11',NULL,NULL,NULL,NULL),(35,1,'production','CREATE','production_programs',3,NULL,'{\"name\":\"TEST\",\"program_number\":\"PRG-20261109-0003\",\"roll_count\":1}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-09 22:14:15',NULL,NULL,NULL,NULL),(37,1,'production','CREATE','production_purchases',2,NULL,'{\"purchase_number\":\"PP-2026-000002\",\"seller_id\":1,\"total_amount\":70000,\"item_count\":1}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-09 22:29:07',NULL,NULL,NULL,NULL),(38,1,'production','CREATE','production_purchases',3,NULL,'{\"purchase_number\":\"PP-2026-000003\",\"seller_id\":1,\"total_amount\":75000,\"item_count\":1}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-09 22:43:47',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `backups`
--

DROP TABLE IF EXISTS `backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `file_size` bigint(20) unsigned DEFAULT 0,
  `backup_type` enum('FULL','SCHEMA','DATA') DEFAULT 'FULL',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `filepath` varchar(500) NOT NULL,
  `filesize` bigint(20) DEFAULT 0,
  `status` enum('COMPLETED','FAILED','IN_PROGRESS') DEFAULT 'IN_PROGRESS',
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `backups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backups`
--

LOCK TABLES `backups` WRITE;
/*!40000 ALTER TABLE `backups` DISABLE KEYS */;
/*!40000 ALTER TABLE `backups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_deposits`
--

DROP TABLE IF EXISTS `bank_deposits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_deposits` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `register_id` int(10) unsigned DEFAULT NULL,
  `deposit_number` varchar(50) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `slip_number` varchar(100) DEFAULT NULL,
  `deposit_date` date NOT NULL,
  `deposited_by` int(10) unsigned NOT NULL,
  `verified_by` int(10) unsigned DEFAULT NULL,
  `status` enum('PENDING','VERIFIED','REJECTED') NOT NULL DEFAULT 'VERIFIED',
  `notes` text DEFAULT NULL,
  `slip_image` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `deposit_number` (`deposit_number`),
  KEY `idx_shop_status` (`shop_id`,`status`),
  KEY `idx_deposit_date` (`deposit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_deposits`
--

LOCK TABLES `bank_deposits` WRITE;
/*!40000 ALTER TABLE `bank_deposits` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_deposits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brands`
--

LOCK TABLES `brands` WRITE;
/*!40000 ALTER TABLE `brands` DISABLE KEYS */;
INSERT INTO `brands` VALUES (1,'MEKHA',NULL,1,'2026-11-08 23:07:56','2026-11-08 23:07:56','MEKHA',NULL,NULL);
/*!40000 ALTER TABLE `brands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_expenses`
--

DROP TABLE IF EXISTS `cash_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cash_expenses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `register_id` int(10) unsigned DEFAULT NULL,
  `voucher_number` varchar(50) NOT NULL,
  `expense_category` varchar(100) NOT NULL DEFAULT 'General Expense',
  `amount` decimal(10,2) NOT NULL,
  `paid_to` varchar(150) NOT NULL,
  `payment_mode` enum('CASH','UPI','CARD','ONLINE') NOT NULL DEFAULT 'CASH',
  `bill_number` varchar(100) DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `receipt_image` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `voucher_number` (`voucher_number`),
  KEY `idx_shop_cat` (`shop_id`,`expense_category`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_expenses`
--

LOCK TABLES `cash_expenses` WRITE;
/*!40000 ALTER TABLE `cash_expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `cash_expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_registers`
--

DROP TABLE IF EXISTS `cash_registers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cash_registers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `register_number` varchar(50) NOT NULL,
  `cashier_id` int(10) unsigned NOT NULL,
  `cashier_name` varchar(150) DEFAULT NULL,
  `closed_by` int(10) unsigned DEFAULT NULL,
  `opening_time` datetime NOT NULL,
  `closing_time` datetime DEFAULT NULL,
  `opening_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `opening_expected_float` decimal(12,2) NOT NULL DEFAULT 0.00,
  `opening_difference` decimal(12,2) NOT NULL DEFAULT 0.00,
  `opening_denominations` text DEFAULT NULL,
  `opening_notes` text DEFAULT NULL,
  `opening_discrepancy_reason` text DEFAULT NULL,
  `cash_sales_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `upi_sales_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `card_sales_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cash_in_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cash_out_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cash_refund_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `bank_deposit_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `expected_cash` decimal(12,2) NOT NULL DEFAULT 0.00,
  `closing_cash_counted` decimal(12,2) DEFAULT NULL,
  `closing_denominations` text DEFAULT NULL,
  `cash_difference` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discrepancy_reason` text DEFAULT NULL,
  `status` enum('OPEN','CLOSED','AUDITED') NOT NULL DEFAULT 'OPEN',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `register_number` (`register_number`),
  KEY `idx_shop_status` (`shop_id`,`status`),
  KEY `idx_cashier` (`cashier_id`),
  KEY `idx_opening_time` (`opening_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_registers`
--

LOCK TABLES `cash_registers` WRITE;
/*!40000 ALTER TABLE `cash_registers` DISABLE KEYS */;
/*!40000 ALTER TABLE `cash_registers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_transactions`
--

DROP TABLE IF EXISTS `cash_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cash_transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `register_id` int(10) unsigned DEFAULT NULL,
  `transaction_number` varchar(50) NOT NULL,
  `transaction_type` enum('OPENING_FLOAT','SALE_CASH','SALE_UPI','SALE_CARD','CASH_IN','CASH_OUT','EXPENSE','REFUND_CASH','BANK_DEPOSIT','SAFE_DROP','CLOSING_ADJUSTMENT') NOT NULL,
  `flow_direction` enum('IN','OUT') NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_mode` enum('CASH','UPI','CARD','BANK_TRANSFER','OTHER') NOT NULL DEFAULT 'CASH',
  `category` varchar(100) NOT NULL DEFAULT 'GENERAL',
  `reference_id` varchar(100) DEFAULT NULL,
  `source_table` varchar(50) DEFAULT NULL,
  `source_id` int(10) unsigned DEFAULT NULL,
  `handled_by` int(10) unsigned NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_number` (`transaction_number`),
  KEY `idx_shop_flow` (`shop_id`,`flow_direction`),
  KEY `idx_register` (`register_id`),
  KEY `idx_type` (`transaction_type`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_transactions`
--

LOCK TABLES `cash_transactions` WRITE;
/*!40000 ALTER TABLE `cash_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `cash_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Men\'s Wear','MW','mens-wear','Men\'s apparel and garments','6109',5.00,NULL,1,1,'2026-09-23 17:19:59','2026-11-08 00:47:38',NULL),(2,'Women\'s Wear','WW','womens-wear','Women\'s apparel and fashion','6204',5.00,NULL,1,2,'2026-09-23 17:19:59','2026-11-08 00:47:38',NULL),(3,'Kids\' Wear','KW','kids-wear','Kids and teen wear','6109',5.00,NULL,1,3,'2026-09-23 17:19:59','2026-11-08 00:47:38',NULL),(4,'MENS','MEN','mens',NULL,NULL,5.00,NULL,1,4,'2026-11-08 22:33:47','2026-11-08 22:33:47',NULL);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `channel_damages`
--

DROP TABLE IF EXISTS `channel_damages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `channel_damages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `damage_number` varchar(50) NOT NULL,
  `channel_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `tag_code` text DEFAULT NULL,
  `source_type` enum('INWARD_TRANSIT','STORAGE_HANDLING','CUSTOMER_RETURN','EXPIRED_SPOILED','OTHER') DEFAULT 'INWARD_TRANSIT',
  `reference_id` int(10) unsigned DEFAULT NULL,
  `allocation_id` int(10) unsigned DEFAULT NULL,
  `defect_type` enum('FABRIC_DEFECT','COLOR_FADE','STITCHING_ERROR','PACKAGING_DAMAGE','WATER_STAIN','TRANSIT_DAMAGE','EXPIRED','OTHER') DEFAULT 'TRANSIT_DAMAGE',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `cost_per_unit` decimal(10,2) DEFAULT 0.00,
  `estimated_cost` decimal(10,2) DEFAULT 0.00,
  `total_loss` decimal(10,2) DEFAULT 0.00,
  `damage_reason` varchar(255) NOT NULL,
  `defect_notes` text DEFAULT NULL,
  `photo_url` varchar(500) DEFAULT NULL,
  `status` enum('LOGGED','INSPECTED','OUTWARD_BATCHED','DISPATCHED','RECEIVED_AT_WH','SALVAGED','WRITTEN_OFF','REWORK') DEFAULT 'LOGGED',
  `outward_batch_id` int(10) unsigned DEFAULT NULL,
  `disposition_notes` text DEFAULT NULL,
  `reported_by` int(10) unsigned DEFAULT NULL,
  `inspected_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_channel_damage_number` (`damage_number`),
  KEY `idx_channel_damages_channel` (`channel_id`),
  KEY `idx_channel_damages_variant` (`variant_id`),
  KEY `idx_channel_damages_status` (`status`),
  KEY `idx_channel_damages_alloc` (`allocation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `channel_damages`
--

LOCK TABLES `channel_damages` WRITE;
/*!40000 ALTER TABLE `channel_damages` DISABLE KEYS */;
/*!40000 ALTER TABLE `channel_damages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `colors`
--

DROP TABLE IF EXISTS `colors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `colors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `hex_code` varchar(7) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `colors`
--

LOCK TABLES `colors` WRITE;
/*!40000 ALTER TABLE `colors` DISABLE KEYS */;
INSERT INTO `colors` VALUES (1,'Royal Blue','#1E40AF',1,'2026-09-23 17:19:59','2026-09-23 17:19:59'),(2,'Crimson Red','#DC2626',1,'2026-09-23 17:19:59','2026-09-23 17:19:59'),(3,'Jet Black','#0F172A',1,'2026-09-23 17:19:59','2026-09-23 17:19:59'),(4,'Pearl White','#F8FAFC',1,'2026-09-23 17:19:59','2026-09-23 17:19:59'),(5,'Olive Green','#4D7C0F',1,'2026-09-23 17:19:59','2026-09-23 17:19:59'),(6,'RED',NULL,1,'2026-11-08 22:46:00','2026-11-08 22:46:00'),(7,'NAVY BLUE',NULL,1,'2026-11-09 15:03:37','2026-11-09 15:03:37');
/*!40000 ALTER TABLE `colors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `gst_number` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `pincode` varchar(10) DEFAULT NULL,
  `total_purchases` decimal(12,2) DEFAULT 0.00,
  `source_channel` varchar(50) DEFAULT 'SHOP',
  PRIMARY KEY (`id`),
  KEY `idx_customer_phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'mekha','9500904638',NULL,'41/65','Tiruppur','Tamil Nadu',NULL,1,'2026-11-09 20:47:51','2026-11-09 20:47:51',NULL,0.00,'WAREHOUSE');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cutting_batch_rolls`
--

DROP TABLE IF EXISTS `cutting_batch_rolls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cutting_batch_rolls`
--

LOCK TABLES `cutting_batch_rolls` WRITE;
/*!40000 ALTER TABLE `cutting_batch_rolls` DISABLE KEYS */;
INSERT INTO `cutting_batch_rolls` VALUES (2,1,1,1,4,1,1,3.00,5000.00,0.00,0.00,'RED','RED-PANDA','2026-11-08 22:40:11',0.00,'2026-11-08 22:40:11'),(3,2,3,1,4,1,1,2.00,6500.00,0.00,0.00,'NAVY BLUE','BEE','2026-11-09 15:00:51',0.00,'2026-11-09 15:00:51');
/*!40000 ALTER TABLE `cutting_batch_rolls` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cutting_batches`
--

DROP TABLE IF EXISTS `cutting_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cutting_batches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch_number` (`batch_number`),
  KEY `program_id` (`program_id`),
  KEY `operator_id` (`operator_id`),
  KEY `production_unit_id` (`production_unit_id`),
  CONSTRAINT `cutting_batches_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `production_programs` (`id`),
  CONSTRAINT `cutting_batches_ibfk_2` FOREIGN KEY (`operator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cutting_batches_ibfk_3` FOREIGN KEY (`production_unit_id`) REFERENCES `production_units` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cutting_batches`
--

LOCK TABLES `cutting_batches` WRITE;
/*!40000 ALTER TABLE `cutting_batches` DISABLE KEYS */;
INSERT INTO `cutting_batches` VALUES (1,1,1,3.00,5000.00,0.00,0.00,3.00,45.45,4,1,1,'CUT-20261108-0001','PROGRAM CUTTING',1,NULL,'Roll Weight: 3 kg | Wastage: 0 kg (Ôé╣0) | Used: 3 kg',110,110,0,0,'IN_PROGRESS','',1,'2026-11-08 22:39:46','2026-11-08 22:40:11',NULL,NULL,NULL,NULL,NULL),(2,2,3,2.00,6500.00,0.00,0.00,2.00,65.00,4,1,1,'CUT-20261109-0002','SECOND CUTTING',NULL,NULL,'Roll Weight: 2 kg | Wastage: 0 kg (Ôé╣0) | Used: 2 kg',100,100,0,0,'IN_PROGRESS','',1,'2026-11-09 15:00:51','2026-11-09 15:00:51',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `cutting_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cutting_items`
--

DROP TABLE IF EXISTS `cutting_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `weight_per_piece` decimal(10,3) DEFAULT 0.000,
  `weight` decimal(10,2) DEFAULT 0.00,
  `base_price` decimal(10,2) DEFAULT 0.00,
  `discount_price` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `cutting_batch_id` (`cutting_batch_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `cutting_items_ibfk_1` FOREIGN KEY (`cutting_batch_id`) REFERENCES `cutting_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cutting_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cutting_items`
--

LOCK TABLES `cutting_items` WRITE;
/*!40000 ALTER TABLE `cutting_items` DISABLE KEYS */;
INSERT INTO `cutting_items` VALUES (3,1,NULL,4,1,1,'MENS ÔÇ║ SHIRT ÔÇ║ FULL SLEEVE','S','RED',50,50,0,0,0.000,0.00,0.00,0.00),(4,1,NULL,4,1,1,'MENS ÔÇ║ SHIRT ÔÇ║ FULL SLEEVE','M','RED',60,60,0,0,0.000,0.00,0.00,0.00),(5,2,NULL,4,1,1,'MENS ÔÇ║ SHIRT ÔÇ║ FULL SLEEVE','S','NAVY BLUE',50,50,0,0,0.000,0.00,0.00,0.00),(6,2,NULL,4,1,1,'MENS ÔÇ║ SHIRT ÔÇ║ FULL SLEEVE','M','NAVY BLUE',50,50,0,0,0.000,0.00,0.00,0.00);
/*!40000 ALTER TABLE `cutting_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deliveries`
--

DROP TABLE IF EXISTS `deliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deliveries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` int(10) unsigned NOT NULL,
  `delivery_number` varchar(50) NOT NULL,
  `status` enum('REQUESTED','APPROVED','RESERVED','PICKING','PACKING','INVOICED','DISPATCHED','IN_TRANSIT','DELIVERED','RETURNED') DEFAULT 'REQUESTED',
  `dispatch_date` datetime DEFAULT NULL,
  `delivery_date` datetime DEFAULT NULL,
  `address` text DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `delivery_staff_id` int(10) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `dispatched_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `courier_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `delivery_number` (`delivery_number`),
  KEY `sale_id` (`sale_id`),
  KEY `delivery_staff_id` (`delivery_staff_id`),
  CONSTRAINT `deliveries_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  CONSTRAINT `deliveries_ibfk_2` FOREIGN KEY (`delivery_staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deliveries`
--

LOCK TABLES `deliveries` WRITE;
/*!40000 ALTER TABLE `deliveries` DISABLE KEYS */;
/*!40000 ALTER TABLE `deliveries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exchange_items`
--

DROP TABLE IF EXISTS `exchange_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exchange_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `exchange_id` int(10) unsigned NOT NULL,
  `item_type` enum('OLD','NEW') NOT NULL,
  `variant_id` int(10) unsigned NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `piece_tag` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exchange_id` (`exchange_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `exchange_items_ibfk_1` FOREIGN KEY (`exchange_id`) REFERENCES `exchanges` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exchange_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exchange_items`
--

LOCK TABLES `exchange_items` WRITE;
/*!40000 ALTER TABLE `exchange_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `exchange_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exchanges`
--

DROP TABLE IF EXISTS `exchanges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exchanges` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `exchange_number` varchar(50) DEFAULT NULL,
  `return_id` int(10) unsigned NOT NULL,
  `source_type` varchar(50) NOT NULL DEFAULT 'SHOP',
  `sale_id` int(10) unsigned DEFAULT NULL,
  `purchase_order_id` int(10) unsigned DEFAULT NULL,
  `original_amount` decimal(10,2) DEFAULT 0.00,
  `new_amount` decimal(10,2) DEFAULT 0.00,
  `difference_amount` decimal(10,2) DEFAULT 0.00,
  `settlement_mode` varchar(50) NOT NULL DEFAULT 'CASH',
  `payment_action` enum('COLLECT','REFUND','NONE') DEFAULT 'NONE',
  `status` enum('PENDING','PROCESSING','COMPLETED','CANCELLED') DEFAULT 'PENDING',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_status` enum('PAID','PENDING','REFUNDED') DEFAULT 'PAID',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `return_id` (`return_id`),
  KEY `sale_id` (`sale_id`),
  KEY `idx_exchanges_source` (`source_type`),
  KEY `idx_exchanges_po` (`purchase_order_id`),
  CONSTRAINT `exchanges_ibfk_1` FOREIGN KEY (`return_id`) REFERENCES `returns` (`id`),
  CONSTRAINT `exchanges_ibfk_2` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exchanges`
--

LOCK TABLES `exchanges` WRITE;
/*!40000 ALTER TABLE `exchanges` DISABLE KEYS */;
/*!40000 ALTER TABLE `exchanges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_categories`
--

DROP TABLE IF EXISTS `expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_categories`
--

LOCK TABLES `expense_categories` WRITE;
/*!40000 ALTER TABLE `expense_categories` DISABLE KEYS */;
INSERT INTO `expense_categories` VALUES (1,'Machinery Spares & Parts','PROD_SPARES','PRODUCTION','Cutting blades, stitching needles, motors, belts, looper parts',1,'2026-09-29 10:48:15'),(2,'Consumables & Lubricants','PROD_CONSUMABLES','PRODUCTION','Machine oil, thread spools, chalk, fusing tapes, scissors',1,'2026-09-29 10:48:15'),(3,'Machine Repairs & Maintenance','PROD_MAINTENANCE','PRODUCTION','External technician labor, overlock servicing, generator service',1,'2026-09-29 10:48:15'),(4,'Factory Utilities & Power','PROD_UTILITIES','PRODUCTION','Electricity bills, diesel for generator, water supply',1,'2026-09-29 10:48:15'),(5,'Packaging Materials & Cartons','WH_PACKAGING','WAREHOUSE','Corrugated boxes, bubble wrap, polybags, brown tape, barcode ribbons',1,'2026-09-29 10:48:15'),(6,'Logistics & Freight Shipping','WH_FREIGHT','WAREHOUSE','Inter-unit vehicle transport, tempo rent, courier charges',1,'2026-09-29 10:48:15'),(7,'Warehouse Handling & Maintenance','WH_MAINTENANCE','WAREHOUSE','Forklift fuel, pallet repairs, rack maintenance, pest control',1,'2026-09-29 10:48:15'),(8,'Shop Petty Cash & Refreshments','SHOP_PETTY','SHOP','Daily staff tea, coffee, drinking water, customer snacks',1,'2026-09-29 10:48:15'),(9,'Store Utilities & Cleaning','SHOP_UTILITIES','SHOP','Store electricity, cleaning materials, air freshener, bulb replacement',1,'2026-09-29 10:48:15'),(10,'Store Rent & Maintenance','SHOP_RENT','SHOP','Retail outlet monthly rent, AC service, glass cleaning',1,'2026-09-29 10:48:15'),(11,'Digital Ads & Meta Marketing','CHAN_ADS','CHANNEL','Facebook/Instagram ads, Google ads, influencer promotion',1,'2026-09-29 10:48:15'),(12,'Software & API Charges','CHAN_SOFTWARE','CHANNEL','WhatsApp Cloud API fees, Shopify/WooCommerce plugins, SMS gateway',1,'2026-09-29 10:48:15'),(13,'Courier & Parcel Dispatch','CHAN_COURIER','CHANNEL','Shiprocket, DTDC, Delhivery customer parcel shipping costs',1,'2026-09-29 10:48:15'),(14,'Office Supplies & Stationery','OFFICE_SUPPLIES','OFFICE','A4 printing paper, printer ink, pens, registers, staples',1,'2026-09-29 10:48:15'),(15,'Professional & Legal Fees','OFFICE_LEGAL','OFFICE','Chartered Accountant fees, GST filing consultancy, auditing',1,'2026-09-29 10:48:15'),(16,'Staff Welfare & General Admin','OFFICE_GENERAL','OFFICE','Team lunches, staff first aid, courier receiving, miscellaneous',1,'2026-09-29 10:48:15'),(17,'Sewing Materials & Trims (Threads, Needles, Zips)','SEWING_MATERIALS','PRODUCTION','Threads, Needles, Zips, Elastics, Buttons',1,'2026-11-09 11:37:24'),(18,'Machine Maintenance & Repairs (Oil, Spares, Belts)','MAINTENANCE_REPAIR','PRODUCTION','Oil, Spares, Belts, Technician Charges',1,'2026-11-09 11:37:24'),(19,'Operational & Utilities (Electricity, Fuel, Polybags)','OPERATIONAL_UTILITY','PRODUCTION','Electricity, Fuel, Polybags, Factory supplies',1,'2026-11-09 11:37:24'),(20,'Other Floor Expenses','OTHER','PRODUCTION','Miscellaneous floor and daily production expenses',1,'2026-11-09 11:37:24');
/*!40000 ALTER TABLE `expense_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expenses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `expense_number` varchar(50) NOT NULL,
  `location_type` enum('PRODUCTION','WAREHOUSE','SHOP','CHANNEL','OFFICE','WHATSAPP','WEBSITE') NOT NULL DEFAULT 'PRODUCTION',
  `location_id` int(10) unsigned DEFAULT NULL,
  `location_name` varchar(150) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_type` varchar(50) NOT NULL DEFAULT 'PCS',
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('PAID','PENDING','PARTIAL','APPROVED','CANCELLED') NOT NULL DEFAULT 'PAID',
  `payment_method` enum('CASH','UPI','BANK_TRANSFER','CARD','PETTY_CASH','CHEQUE','CREDIT','OTHER') NOT NULL DEFAULT 'CASH',
  `payment_reference` varchar(100) DEFAULT NULL,
  `paid_to` varchar(200) NOT NULL,
  `vendor_phone` varchar(25) DEFAULT NULL,
  `vendor_company` varchar(200) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `expense_date` date NOT NULL,
  `receipt_url` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `expense_number` (`expense_number`),
  KEY `idx_location` (`location_type`,`location_id`),
  KEY `idx_date` (`expense_date`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hsn_codes`
--

DROP TABLE IF EXISTS `hsn_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hsn_codes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hsn_code` varchar(20) NOT NULL,
  `description` varchar(255) NOT NULL,
  `gst_rate` decimal(5,2) NOT NULL DEFAULT 5.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_hsn_code` (`hsn_code`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hsn_codes`
--

LOCK TABLES `hsn_codes` WRITE;
/*!40000 ALTER TABLE `hsn_codes` DISABLE KEYS */;
INSERT INTO `hsn_codes` VALUES (1,'6109','T-Shirts, Singlets & Vests (Knitted)',5.00,'2026-11-08 00:11:21','2026-11-08 00:11:21'),(2,'6205','Men\'s / Boys\' Woven Shirts',5.00,'2026-11-08 00:11:21','2026-11-08 00:11:21'),(3,'6203','Men\'s Trousers, Suits & Shorts',5.00,'2026-11-08 00:11:21','2026-11-08 00:11:21'),(4,'6204','Women\'s Dresses, Kurtis & Skirts',5.00,'2026-11-08 00:11:21','2026-11-08 00:11:21'),(5,'6104','Women\'s Knitted Suits & Nightwear',5.00,'2026-11-08 00:11:21','2026-11-08 00:11:21'),(6,'6115','Socks & Pantyhose',5.00,'2026-11-08 00:11:21','2026-11-08 00:11:21'),(7,'6211','Tracksuits & Sportswear',12.00,'2026-11-08 00:11:21','2026-11-08 00:11:21'),(8,'5208','Woven Cotton Fabrics',5.00,'2026-11-08 00:11:21','2026-11-08 00:11:21'),(9,'6302','Bed, Table & Toilet Linen',5.00,'2026-11-08 00:11:21','2026-11-08 00:11:21'),(10,'6206','Women\'s or girls\' blouses, shirts and shirt-blouses',5.00,'2026-11-08 00:19:13','2026-11-08 00:19:13'),(11,'5525','MES(TESTING)',10.00,'2026-11-08 00:40:05','2026-11-08 00:40:05'),(12,'MENS','TESTING',5.00,'2026-11-08 22:34:21','2026-11-08 22:34:21');
/*!40000 ALTER TABLE `hsn_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `label_batches`
--

DROP TABLE IF EXISTS `label_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `label_batches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` int(10) unsigned NOT NULL,
  `stitching_batch_id` int(10) unsigned DEFAULT NULL,
  `batch_number` varchar(50) NOT NULL,
  `required_qty` int(11) NOT NULL DEFAULT 0,
  `printed_qty` int(11) DEFAULT 0,
  `used_qty` int(11) DEFAULT 0,
  `damaged_qty` int(11) DEFAULT 0,
  `status` enum('PENDING','IN_PROGRESS','COMPLETED') DEFAULT 'PENDING',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_qty` int(11) NOT NULL DEFAULT 0,
  `applied_qty` int(11) DEFAULT 0,
  `session_data` longtext DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch_number` (`batch_number`),
  KEY `program_id` (`program_id`),
  KEY `fk_label_batches_stitching` (`stitching_batch_id`),
  CONSTRAINT `fk_label_batches_stitching` FOREIGN KEY (`stitching_batch_id`) REFERENCES `stitching_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `label_batches_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `production_programs` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `label_batches`
--

LOCK TABLES `label_batches` WRITE;
/*!40000 ALTER TABLE `label_batches` DISABLE KEYS */;
INSERT INTO `label_batches` VALUES (1,1,1,'LBL-20261108-0001',110,110,110,0,'COMPLETED','2026-11-08 22:42:45','2026-11-08 22:42:45',0,0,NULL,NULL,NULL),(2,2,2,'LBL-20261109-0002',100,100,100,0,'COMPLETED','2026-11-09 15:02:01','2026-11-09 15:02:01',0,0,NULL,NULL,NULL);
/*!40000 ALTER TABLE `label_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `label_items`
--

DROP TABLE IF EXISTS `label_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `label_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `label_batch_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `subcategory_id` int(10) unsigned DEFAULT NULL,
  `sub_subcategory_id` int(10) unsigned DEFAULT NULL,
  `catalog_hierarchy` varchar(255) DEFAULT NULL,
  `size_name` varchar(100) DEFAULT NULL,
  `color_name` varchar(100) DEFAULT NULL,
  `base_price` decimal(10,2) DEFAULT 0.00,
  `discount_price` decimal(10,2) DEFAULT 0.00,
  `required_qty` int(11) NOT NULL DEFAULT 0,
  `printed_qty` int(11) DEFAULT 0,
  `used_qty` int(11) DEFAULT 0,
  `damaged_qty` int(11) DEFAULT 0,
  `start_seq` int(11) DEFAULT 1,
  `end_seq` int(11) DEFAULT 1,
  `start_code` varchar(50) DEFAULT NULL,
  `end_code` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `label_batch_id` (`label_batch_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `label_items_ibfk_1` FOREIGN KEY (`label_batch_id`) REFERENCES `label_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `label_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `label_items`
--

LOCK TABLES `label_items` WRITE;
/*!40000 ALTER TABLE `label_items` DISABLE KEYS */;
INSERT INTO `label_items` VALUES (1,1,NULL,4,1,1,'MENS ÔÇ║ SHIRT ÔÇ║ FULL SLEEVE','S','RED',1000.00,700.00,50,50,50,0,1,50,'MSF112601','MSF112650'),(2,1,NULL,4,1,1,'MENS ÔÇ║ SHIRT ÔÇ║ FULL SLEEVE','M','RED',1000.00,700.00,60,60,60,0,51,110,'MSF112651','MSF1126110'),(3,2,NULL,4,1,1,'MENS ÔÇ║ SHIRT ÔÇ║ FULL SLEEVE','S','NAVY BLUE',1500.00,1000.00,50,50,50,0,111,160,'MSF1126111','MSF1126160'),(4,2,NULL,4,1,1,'MENS ÔÇ║ SHIRT ÔÇ║ FULL SLEEVE','M','NAVY BLUE',1500.00,1000.00,50,50,50,0,161,210,'MSF1126161','MSF1126210');
/*!40000 ALTER TABLE `label_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `locations`
--

DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `locations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_id` int(10) unsigned NOT NULL,
  `rack` varchar(50) DEFAULT NULL,
  `shelf` varchar(50) DEFAULT NULL,
  `bin` varchar(50) DEFAULT NULL,
  `label` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `zone` varchar(50) NOT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_loc_warehouse` (`warehouse_id`),
  CONSTRAINT `locations_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `locations`
--

LOCK TABLES `locations` WRITE;
/*!40000 ALTER TABLE `locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` int(10) unsigned NOT NULL,
  `payment_mode` varchar(50) NOT NULL DEFAULT 'CASH',
  `amount` decimal(10,2) NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `payment_date` datetime DEFAULT current_timestamp(),
  `status` enum('PENDING','COMPLETED','FAILED','REFUNDED') DEFAULT 'COMPLETED',
  `created_at` datetime DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `collected_by` int(10) unsigned DEFAULT NULL,
  `payment_number` varchar(50) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'CASH',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `purchase_order_id` int(10) unsigned DEFAULT NULL,
  `payment_type` enum('INCOMING','OUTGOING') NOT NULL DEFAULT 'INCOMING',
  `method` enum('CASH','UPI','CARD','NET_BANKING','CHEQUE','CREDIT_NOTE') NOT NULL DEFAULT 'CASH',
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `slug` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_module_action` (`module`,`action`)
) ENGINE=InnoDB AUTO_INCREMENT=398 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'dashboard','view','View Executive Dashboard KPIs & Analytics','2026-09-23 16:41:13',NULL),(2,'production','view','View Production Overview & Plant Throughput','2026-09-23 16:41:13',NULL),(3,'sellers','view','View Fabric Sellers & Job Worker Directory','2026-09-23 16:41:13',NULL),(4,'sellers','create','Create Fabric Seller / Job Worker','2026-09-23 16:41:13',NULL),(5,'sellers','edit','Edit Seller Details & Rates','2026-09-23 16:41:13',NULL),(6,'sellers','delete','Delete Seller / Fabric Roll','2026-09-23 16:41:13',NULL),(7,'damages','view','View Production Fabric & Floor Damages','2026-09-23 16:41:13',NULL),(8,'damages','create','Report Defective Fabric / Scrap','2026-09-23 16:41:13',NULL),(9,'damages','edit','Edit Damage Records','2026-09-23 16:41:13',NULL),(10,'damages','delete','Delete Damage Record','2026-09-23 16:41:13',NULL),(11,'catalog','view','View Design Catalog & Style Codes','2026-09-23 16:41:13',NULL),(12,'catalog','create','Create Style Catalog & BOM','2026-09-23 16:41:13',NULL),(13,'catalog','edit','Edit Style Catalog & Specifications','2026-09-23 16:41:13',NULL),(14,'catalog','delete','Delete Catalog Item','2026-09-23 16:41:13',NULL),(15,'programs','view','View Production Programs','2026-09-23 16:41:13',NULL),(16,'programs','create','Create Production Work Orders','2026-09-23 16:41:13',NULL),(17,'programs','edit','Edit Program Specifications & Quantities','2026-09-23 16:41:13',NULL),(18,'programs','delete','Delete Production Program','2026-09-23 16:41:13',NULL),(19,'programs','approve','Approve & Release Production Program','2026-09-23 16:41:13',NULL),(20,'cutting','view','View Cutting Batches & Lay Sheets','2026-09-23 16:41:13',NULL),(21,'cutting','create','Create Cutting Batch & Roll Allocation','2026-09-23 16:41:13',NULL),(22,'cutting','edit','Edit Cutting Item Quantities & Bundles','2026-09-23 16:41:13',NULL),(23,'cutting','delete','Delete Cutting Batch','2026-09-23 16:41:13',NULL),(24,'wastage','view','View Cutting Wastage Sales','2026-09-23 16:41:13',NULL),(25,'wastage','create','Record Fabric Scrap Sale','2026-09-23 16:41:13',NULL),(26,'wastage','edit','Edit Scrap Sale Record','2026-09-23 16:41:13',NULL),(27,'wastage','delete','Delete Wastage Sale','2026-09-23 16:41:13',NULL),(28,'wastage','print','Print Wastage Receipt','2026-09-23 16:41:13',NULL),(29,'stitching','view','View Stitching Batches & Lines','2026-09-23 16:41:13',NULL),(30,'stitching','create','Create Stitching Batch / Assign Worker','2026-09-23 16:41:13',NULL),(31,'stitching','edit','Edit Stitching Quantities & Piece-Rates','2026-09-23 16:41:13',NULL),(32,'stitching','delete','Delete Stitching Batch','2026-09-23 16:41:13',NULL),(33,'stitching_wastage','view','View Stitching Trimming Wastage','2026-09-23 16:41:13',NULL),(34,'stitching_wastage','create','Record Stitching Scrap Sale','2026-09-23 16:41:13',NULL),(35,'stitching_wastage','edit','Edit Stitching Scrap Record','2026-09-23 16:41:13',NULL),(36,'stitching_wastage','delete','Delete Stitching Scrap Record','2026-09-23 16:41:13',NULL),(37,'stitching_wastage','print','Print Stitching Scrap Voucher','2026-09-23 16:41:13',NULL),(38,'production_expenses','view','View Plant Machinery & Daily Expenses','2026-09-23 16:41:13',NULL),(39,'production_expenses','create','Record Machinery Spare / Plant Expense','2026-09-23 16:41:13',NULL),(40,'production_expenses','edit','Edit Production Expense Entry','2026-09-23 16:41:13',NULL),(41,'production_expenses','delete','Delete Expense Entry','2026-09-23 16:41:13',NULL),(42,'production_expenses','print','Print Expense Voucher','2026-09-23 16:41:13',NULL),(43,'labels','view','View Label & Barcode Print Jobs','2026-09-23 16:41:13',NULL),(44,'labels','create','Create Garment Label Batch','2026-09-23 16:41:13',NULL),(45,'labels','edit','Edit Label Quantities & SKUs','2026-09-23 16:41:13',NULL),(46,'labels','print','Print Garment Barcodes & Care Tags','2026-09-23 16:41:13',NULL),(47,'qc','view','View Quality Control (QC) Inspections','2026-09-23 16:41:13',NULL),(48,'qc','create','Create QC Batch & Start Audit','2026-09-23 16:41:13',NULL),(49,'qc','edit','Inspect & Record Defects / Passed Pieces','2026-09-23 16:41:13',NULL),(50,'qc','delete','Delete QC Inspection Record','2026-09-23 16:41:13',NULL),(51,'qc','approve','Final QC Clearance & Approval','2026-09-23 16:41:13',NULL),(52,'inventory','view','View Passed Finished Goods Inventory','2026-09-23 16:41:13',NULL),(53,'batches','view','View Packaging Batches','2026-09-23 16:41:13',NULL),(54,'batches','create','Create Packaging Batch & Box Consolidations','2026-09-23 16:41:13',NULL),(55,'batches','approve','Approve Finished Goods Packaging Batch','2026-09-23 16:41:13',NULL),(56,'batches','print','Print Packaging Batch QR Master Label','2026-09-23 16:41:13',NULL),(57,'outward','view','View Production Outward Dispatches','2026-09-23 16:41:13',NULL),(58,'outward','create','Create Production Dispatch Challan','2026-09-23 16:41:13',NULL),(59,'outward','dispatch','Dispatch Transport & Generate Gatepass','2026-09-23 16:41:13',NULL),(60,'warehouse','view','View Warehouse Dashboard & Storage Bays','2026-09-23 16:41:13',NULL),(61,'inward','view','View Inward / GRN Shipments','2026-09-23 16:41:13',NULL),(62,'inward','create','Create Inward GRN / Cross-Check Scan','2026-09-23 16:41:13',NULL),(63,'inward','verify','Verify & Confirm Inward Stock Placement','2026-09-23 16:41:13',NULL),(64,'inward','print','Print Inward Gatepass & Labels','2026-09-23 16:41:13',NULL),(65,'stock','view','View Warehouse Live Stock Ledger & Bins','2026-09-23 16:41:13',NULL),(66,'stock','adjust','Execute Stock Count Adjustment','2026-09-23 16:41:13',NULL),(67,'wh_sellers','view','View Warehouse Wholesale Suppliers','2026-09-23 16:41:13',NULL),(68,'wh_sellers','create','Create Wholesale Supplier','2026-09-23 16:41:13',NULL),(69,'wh_sellers','edit','Edit Supplier Profile & Invoices','2026-09-23 16:41:13',NULL),(70,'wh_catalog','view','View Warehouse Catalog & Third-Party Styles','2026-09-23 16:41:13',NULL),(71,'wh_catalog','create','Add Catalog Style / Product Code','2026-09-23 16:41:13',NULL),(72,'wh_catalog','edit','Edit Warehouse Catalog Pricing & SKUs','2026-09-23 16:41:13',NULL),(73,'wh_catalog','delete','Delete Warehouse Catalog Item','2026-09-23 16:41:13',NULL),(74,'purchases','view','View Direct Purchases & Customer Orders','2026-09-23 16:41:13',NULL),(75,'purchases','create','Create Purchase Order / Third-Party Buy','2026-09-23 16:41:13',NULL),(76,'purchases','edit','Edit Purchase Quantities & Invoices','2026-09-23 16:41:13',NULL),(77,'purchases','receive','Receive & Inward Purchase Order Goods','2026-09-23 16:41:13',NULL),(78,'allocation','view','View Store Allocations & Picking Sheets','2026-09-23 16:41:13',NULL),(79,'allocation','create','Create Store Allocation Request','2026-09-23 16:41:13',NULL),(80,'allocation','approve','Approve Stock Allocation Demand','2026-09-23 16:41:13',NULL),(81,'allocation','pick','Execute Floor Box Picking','2026-09-23 16:41:13',NULL),(82,'allocation','pack','Execute Carton Packing & Sealing','2026-09-23 16:41:13',NULL),(83,'allocation','dispatch','Dispatch Allocated Goods to Store','2026-09-23 16:41:13',NULL),(84,'wh_batches','view','View Warehouse Carton Batches','2026-09-23 16:41:13',NULL),(85,'wh_batches','create','Create Warehouse Carton Batch','2026-09-23 16:41:13',NULL),(86,'wh_batches','print','Print Carton Batch QR Label','2026-09-23 16:41:13',NULL),(87,'allocation_outward','view','View Warehouse Outward Manifests','2026-09-23 16:41:13',NULL),(88,'allocation_outward','create','Create Outward Transport Manifest','2026-09-23 16:41:13',NULL),(89,'allocation_outward','deliver','Mark Outward Shipment as Delivered','2026-09-23 16:41:13',NULL),(90,'allocation_outward','gatepass','Print Transport Gatepass','2026-09-23 16:41:13',NULL),(91,'damage','view','View Warehouse Damages & Write-offs','2026-09-23 16:41:13',NULL),(92,'damage','create','Log Damaged Stock / Water Defect','2026-09-23 16:41:13',NULL),(93,'damage','resolve','Resolve & Classify Damaged Stock Action','2026-09-23 16:41:13',NULL),(94,'returns','view','View Warehouse Store Return Parcels','2026-09-23 16:41:13',NULL),(95,'returns','receive','Receive Return Parcel at Dock','2026-09-23 16:41:13',NULL),(96,'returns','inspect','Inspect Return Items & Defect Reasons','2026-09-23 16:41:13',NULL),(97,'returns','classify','Classify to Restock / RTV / Scrap','2026-09-23 16:41:13',NULL),(98,'wh_labels','view','View Shelf & Carton QR Label Studio','2026-09-23 16:41:13',NULL),(99,'wh_labels','create','Generate Bulk QR Barcode Labels','2026-09-23 16:41:13',NULL),(100,'wh_labels','print','Print TSPL Thermal Barcode Stickers','2026-09-23 16:41:13',NULL),(101,'shop_requests','view','View Shop Urgent Indent Requests','2026-09-23 16:41:13',NULL),(102,'shop_requests','create','Raise Stock Indent Request','2026-09-23 16:41:13',NULL),(103,'shop_requests','approve','Approve & Release Indented Stock','2026-09-23 16:41:13',NULL),(104,'sales_channels','view','View WhatsApp B2B Customer Orders','2026-09-23 16:41:13',NULL),(105,'sales_channels','create','Create WhatsApp B2B Order','2026-09-23 16:41:13',NULL),(106,'sales_channels','process','Process & Fulfill WhatsApp Order','2026-09-23 16:41:13',NULL),(107,'website_orders','view','View Website eCommerce Orders','2026-09-23 16:41:13',NULL),(108,'website_orders','create','Create Manual Website Order Entry','2026-09-23 16:41:13',NULL),(109,'website_orders','process','Process & Dispatch Website Order','2026-09-23 16:41:13',NULL),(110,'shop','view','View Retail Store Sales Dashboard','2026-09-23 16:41:13',NULL),(111,'shop_cash','view','View Register Shift Flow & Till Status','2026-09-23 16:41:13',NULL),(112,'shop_cash','create','Open Register Shift / Expense / Cash In / Bank Deposit','2026-09-23 16:41:13',NULL),(113,'shop_cash','close_shift','Close Cash Register & Reconcile Till','2026-09-23 16:41:13',NULL),(114,'shop_cash','voucher','Print Petty Cash / Shift Thermal Slip','2026-09-23 16:41:13',NULL),(115,'shop_inward','view','View Store Inward Deliveries from Warehouse','2026-09-23 16:41:13',NULL),(116,'shop_inward','receive','Scan & Receive Inward Carton Boxes','2026-09-23 16:41:13',NULL),(117,'shop_inward','verify','Verify & Commit Stock to Store Shelf','2026-09-23 16:41:13',NULL),(118,'shop_inward','print','Print Store Inward Gatepass / Label','2026-09-23 16:41:13',NULL),(119,'shop_stock','view','View Store Shelf Live Stock Breakdown','2026-09-23 16:41:13',NULL),(120,'shop_stock','request','Request Stock Replenishment from Warehouse','2026-09-23 16:41:13',NULL),(121,'pos','view','Access POS Billing Counter Terminal','2026-09-23 16:41:13',NULL),(122,'pos','create','Scan & Process Customer Bill / Checkout','2026-09-23 16:41:13',NULL),(123,'pos','print','Print Thermal Sales Invoice Receipt','2026-09-23 16:41:13',NULL),(124,'sales','view','View Sales Register & Customer Invoices','2026-09-23 16:41:13',NULL),(125,'sales','print','Reprint Customer Tax Invoice','2026-09-23 16:41:13',NULL),(126,'customers','view','View Customer Directory & 360 CRM','2026-09-23 16:41:13',NULL),(127,'customers','create','Add Customer Profile & Phone','2026-09-23 16:41:13',NULL),(128,'customers','edit','Edit Customer Profile & Notes','2026-09-23 16:41:13',NULL),(129,'shop_returns','view','View Customer Return Slips & Exchanges','2026-09-23 16:41:13',NULL),(130,'shop_returns','create','Create Return Slip / Process Item Exchange','2026-09-23 16:41:13',NULL),(131,'shop_returns','process','Approve Cash / Store Credit Refund','2026-09-23 16:41:13',NULL),(132,'shop_returns','print','Print Customer Credit Note / Refund Slip','2026-09-23 16:41:13',NULL),(133,'return_to_warehouse','view','View Store RTV Returns to Central Warehouse','2026-09-23 16:41:13',NULL),(134,'return_to_warehouse','create','Create Return to Warehouse Carton Manifest','2026-09-23 16:41:13',NULL),(135,'return_to_warehouse','gatepass','Print Store RTV Gatepass & Transport Slip','2026-09-23 16:41:13',NULL),(136,'return_to_warehouse','print','Print Return Box QR Label','2026-09-23 16:41:13',NULL),(137,'assets','view','View Asset Registry & Equipment Valuation','2026-09-23 16:41:13',NULL),(138,'assets','create','Register New Machinery / Asset Item','2026-09-23 16:41:13',NULL),(139,'assets','edit','Edit Asset Details, Location & Value','2026-09-23 16:41:13',NULL),(140,'assets','delete','Delete Asset Entry','2026-09-23 16:41:13',NULL),(141,'asset_scanner','view','Access QR Scanner Station','2026-09-23 16:41:13',NULL),(142,'asset_scanner','scan','Execute Hardware Scan & Physical Audit','2026-09-23 16:41:13',NULL),(143,'asset_transfers','view','View Inter-Unit Asset Transfers','2026-09-23 16:41:13',NULL),(144,'asset_transfers','create','Create Asset Relocation Manifest','2026-09-23 16:41:13',NULL),(145,'asset_transfers','approve','Approve & Receive Asset Relocation','2026-09-23 16:41:13',NULL),(146,'asset_maintenance','view','View Asset Maintenance & Repair Logs','2026-09-23 16:41:13',NULL),(147,'asset_maintenance','create','Log Maintenance Ticket / Breakdown Service','2026-09-23 16:41:13',NULL),(148,'asset_maintenance','edit','Edit Service Details & Invoices','2026-09-23 16:41:13',NULL),(149,'asset_bulk_labels','view','View Bulk Asset QR Label Studio','2026-09-23 16:41:13',NULL),(150,'asset_bulk_labels','print','Print Bulk Asset Barcode QR Stickers','2026-09-23 16:41:13',NULL),(151,'reports','view','View Analytics & Operational Reports','2026-09-23 16:41:13',NULL),(152,'reports','export','Export Reports to Excel / CSV','2026-09-23 16:41:13',NULL),(153,'reports','print','Print Formatted PDF Summary Reports','2026-09-23 16:41:13',NULL),(154,'staff','view','View Staff Directory & Department Rosters','2026-09-23 16:41:13',NULL),(155,'staff','create','Create New Staff Member Account','2026-09-23 16:41:13',NULL),(156,'staff','edit','Edit Staff Profile & Facility Assignment','2026-09-23 16:41:13',NULL),(157,'staff','delete','Deactivate / Delete Staff Account','2026-09-23 16:41:13',NULL),(158,'roles','view','View Roles & Permissions Directory','2026-09-23 16:41:13',NULL),(159,'roles','create','Create New Custom Role','2026-09-23 16:41:13',NULL),(160,'roles','edit','Edit Role & Configure Permissions Matrix','2026-09-23 16:41:13',NULL),(161,'roles','delete','Delete Custom Role','2026-09-23 16:41:13',NULL),(162,'settings','view','View System & Company Configurations','2026-09-23 16:41:13',NULL),(163,'settings','edit','Update System Settings, Tax & Branding','2026-09-23 16:41:13',NULL),(164,'backup','view','View Database Snapshots & Recovery Hub','2026-09-23 16:41:13',NULL),(165,'backup','create','Generate Manual Database Backup Snapshot','2026-09-23 16:41:13',NULL),(166,'backup','delete','Delete Database Backup File','2026-09-23 16:41:13',NULL),(167,'backup','download','Download Backup Archive (ZIP/SQL)','2026-09-23 16:41:13',NULL),(168,'backup','restore','Restore Database from Snapshot File','2026-09-23 16:41:13',NULL),(169,'audit','view','View Audit Trail & System Activity Logs','2026-09-23 16:41:13',NULL),(170,'audit','export','Export Audit Logs to CSV','2026-09-23 16:41:13',NULL),(171,'notifications','view','View System Notification Feed','2026-09-23 16:41:13',NULL),(172,'notifications','delete','Delete / Clear Notifications','2026-09-23 16:41:13',NULL),(173,'notifications','mark_read','Mark Notifications as Read','2026-09-23 16:41:13',NULL),(174,'master','view','View Master Data (Categories, Sizes, Colors)','2026-09-23 16:41:13',NULL),(175,'master','create','Add Master Attribute (Category, Size, Color, Unit)','2026-09-23 16:41:13',NULL),(176,'master','edit','Edit Master Attribute Name / Code','2026-09-23 16:41:13',NULL),(177,'master','delete','Delete Master Attribute Entry','2026-09-23 16:41:13',NULL),(276,'labels','delete','Delete Garment Label Batch','2026-10-14 10:20:00','labels.delete'),(277,'stock','edit','Edit warehouse stock prices (MRP and selling price)','2026-11-03 00:00:00','stock.edit'),(278,'inventory','delete','Delete an available Production inventory QR piece','2026-11-09 21:19:18','inventory.delete'),(279,'production_billing','view','View Production Bills','2026-11-09 21:19:18','production_billing.view'),(280,'production_billing','create','Create Production Bills','2026-11-09 21:19:18','production_billing.create'),(281,'production_billing','edit','Edit Production Bills','2026-11-09 21:19:18','production_billing.edit'),(282,'production_billing','delete','Delete Production Bills','2026-11-09 21:19:18','production_billing.delete'),(283,'production_billing','print','Print Production Bills','2026-11-09 21:19:18','production_billing.print'),(284,'whatsapp_orders','view','View WhatsApp B2B Orders and history','2026-11-09 21:19:18','whatsapp_orders.view'),(285,'whatsapp_orders','create','Create new WhatsApp B2B order','2026-11-09 21:19:18','whatsapp_orders.create'),(286,'whatsapp_orders','process','Process and dispatch WhatsApp order','2026-11-09 21:19:18','whatsapp_orders.process'),(287,'whatsapp_inward','view','View WhatsApp stock inward and receipts','2026-11-09 21:19:18','whatsapp_inward.view'),(288,'whatsapp_inward','create','Create WhatsApp inward receipt','2026-11-09 21:19:18','whatsapp_inward.create'),(289,'whatsapp_inward','receive','Receive and confirm inward stock','2026-11-09 21:19:18','whatsapp_inward.receive'),(290,'whatsapp_inventory','view','View WhatsApp channel inventory','2026-11-09 21:19:18','whatsapp_inventory.view'),(291,'whatsapp_inventory','edit','Edit WhatsApp inventory details & stock prices','2026-11-09 21:19:18','whatsapp_inventory.edit'),(292,'whatsapp_inventory','adjust','Adjust WhatsApp stock levels','2026-11-09 21:19:18','whatsapp_inventory.adjust'),(293,'whatsapp_damage','view','View WhatsApp defective and damaged items','2026-11-09 21:19:18','whatsapp_damage.view'),(294,'whatsapp_damage','create','Log new WhatsApp damage entry','2026-11-09 21:19:18','whatsapp_damage.create'),(295,'whatsapp_damage','outward','Dispatch damaged goods outward','2026-11-09 21:19:18','whatsapp_damage.outward'),(296,'whatsapp_damage','print','Print damage gate pass and carton labels','2026-11-09 21:19:18','whatsapp_damage.print'),(297,'whatsapp_returns','view','View WhatsApp returns and exchanges','2026-11-09 21:19:18','whatsapp_returns.view'),(298,'whatsapp_returns','create','Create return request for WhatsApp','2026-11-09 21:19:18','whatsapp_returns.create'),(299,'whatsapp_returns','process','Process return and restock/refund','2026-11-09 21:19:18','whatsapp_returns.process'),(300,'whatsapp_returns','print','Print WhatsApp return credit notes','2026-11-09 21:19:18','whatsapp_returns.print'),(301,'whatsapp_expenses','view','View WhatsApp channel expenses','2026-11-09 21:19:18','whatsapp_expenses.view'),(302,'whatsapp_expenses','create','Record WhatsApp operational expense','2026-11-09 21:19:18','whatsapp_expenses.create'),(303,'whatsapp_expenses','delete','Delete WhatsApp expense record','2026-11-09 21:19:18','whatsapp_expenses.delete'),(304,'whatsapp_expenses','voucher','Generate and print expense voucher','2026-11-09 21:19:18','whatsapp_expenses.voucher'),(305,'whatsapp_expenses','print','Print WhatsApp expense summary','2026-11-09 21:19:18','whatsapp_expenses.print'),(306,'website_inward','view','View website stock inward and receipts','2026-11-09 21:19:18','website_inward.view'),(307,'website_inward','receive','Receive and confirm website inward stock','2026-11-09 21:19:18','website_inward.receive'),(308,'website_inventory','view','View website dedicated inventory','2026-11-09 21:19:18','website_inventory.view'),(309,'website_inventory','adjust','Adjust website stock levels','2026-11-09 21:19:18','website_inventory.adjust'),(310,'website_damage','view','View website damaged goods','2026-11-09 21:19:18','website_damage.view'),(311,'website_damage','create','Log website damage entry','2026-11-09 21:19:18','website_damage.create'),(312,'website_damage','outward','Dispatch website damaged goods outward','2026-11-09 21:19:18','website_damage.outward'),(313,'website_damage','print','Print website damage gate pass and labels','2026-11-09 21:19:18','website_damage.print'),(314,'website_returns','view','View website returns and RTO parcels','2026-11-09 21:19:18','website_returns.view'),(315,'website_returns','create','Initiate website return / exchange','2026-11-09 21:19:18','website_returns.create'),(316,'website_returns','process','Process website return and restocking','2026-11-09 21:19:18','website_returns.process'),(317,'website_returns','print','Print website return invoices and slips','2026-11-09 21:19:18','website_returns.print'),(318,'website_expenses','view','View website eCommerce expenses','2026-11-09 21:19:18','website_expenses.view'),(319,'website_expenses','create','Record website hosting/ad/courier expense','2026-11-09 21:19:18','website_expenses.create'),(320,'website_expenses','delete','Delete website expense record','2026-11-09 21:19:18','website_expenses.delete'),(321,'website_expenses','voucher','Print website expense voucher','2026-11-09 21:19:18','website_expenses.voucher'),(322,'website_expenses','print','Print website expense report','2026-11-09 21:19:18','website_expenses.print'),(323,'warehouse_expenses','view','View warehouse operational expenses','2026-11-09 21:19:18','warehouse_expenses.view'),(324,'warehouse_expenses','create','Record warehouse freight/utility expense','2026-11-09 21:19:18','warehouse_expenses.create'),(325,'warehouse_expenses','delete','Delete warehouse expense record','2026-11-09 21:19:18','warehouse_expenses.delete'),(326,'warehouse_expenses','voucher','Print warehouse expense payment voucher','2026-11-09 21:19:18','warehouse_expenses.voucher'),(327,'warehouse_expenses','print','Print warehouse expense statement','2026-11-09 21:19:18','warehouse_expenses.print'),(328,'warehouse_expenses','export','Export warehouse expense records','2026-11-09 21:19:18','warehouse_expenses.export'),(329,'shop_damage','view','View shop floor defect and damaged items','2026-11-09 21:19:18','shop_damage.view'),(330,'shop_damage','create','Record shop damaged product','2026-11-09 21:19:18','shop_damage.create'),(331,'shop_damage','edit','Edit shop damage record','2026-11-09 21:19:18','shop_damage.edit'),(332,'shop_damage','delete','Delete shop damage record','2026-11-09 21:19:18','shop_damage.delete'),(333,'shop_damage','process','Process salvage or write-off','2026-11-09 21:19:18','shop_damage.process'),(334,'shop_stock','edit','Edit Shop Product Selling Price & MRP','2026-11-09 21:19:18','shop_stock.edit'),(335,'shop_stock','edit_price','Quick Edit Retail Selling Price and MRP','2026-11-09 21:19:18','shop_stock.edit_price'),(336,'reports_overview','view','View Executive Overview and P&L Report','2026-11-09 21:19:18','reports_overview.view'),(337,'reports_overview','export','Export Executive Overview data to Excel/CSV','2026-11-09 21:19:18','reports_overview.export'),(338,'reports_overview','print','Print Executive Overview statement','2026-11-09 21:19:18','reports_overview.print'),(339,'reports_profitability','view','View Profitability and Margin Report','2026-11-09 21:19:18','reports_profitability.view'),(340,'reports_profitability','export','Export Profitability report','2026-11-09 21:19:18','reports_profitability.export'),(341,'reports_profitability','print','Print Profitability statement','2026-11-09 21:19:18','reports_profitability.print'),(342,'reports_sales','view','View Multi-Channel Sales Performance Report','2026-11-09 21:19:18','reports_sales.view'),(343,'reports_sales','export','Export Multi-Channel Sales report','2026-11-09 21:19:18','reports_sales.export'),(344,'reports_sales','print','Print Multi-Channel Sales report','2026-11-09 21:19:18','reports_sales.print'),(345,'reports_website','view','View Website eCommerce Analytics Report','2026-11-09 21:19:18','reports_website.view'),(346,'reports_website','export','Export Website eCommerce analytics','2026-11-09 21:19:18','reports_website.export'),(347,'reports_website','print','Print Website report','2026-11-09 21:19:18','reports_website.print'),(348,'reports_whatsapp','view','View WhatsApp B2B Sales Report','2026-11-09 21:19:18','reports_whatsapp.view'),(349,'reports_whatsapp','export','Export WhatsApp B2B sales data','2026-11-09 21:19:18','reports_whatsapp.export'),(350,'reports_whatsapp','print','Print WhatsApp B2B report','2026-11-09 21:19:18','reports_whatsapp.print'),(351,'reports_production','view','View Production Floor & Output Report','2026-11-09 21:19:18','reports_production.view'),(352,'reports_production','export','Export Production floor report','2026-11-09 21:19:18','reports_production.export'),(353,'reports_production','print','Print Production summary report','2026-11-09 21:19:18','reports_production.print'),(354,'reports_warehouse','view','View Warehouse Inward & Dispatch Report','2026-11-09 21:19:18','reports_warehouse.view'),(355,'reports_warehouse','export','Export Warehouse storage and dispatch report','2026-11-09 21:19:18','reports_warehouse.export'),(356,'reports_warehouse','print','Print Warehouse report','2026-11-09 21:19:18','reports_warehouse.print'),(357,'reports_purchase','view','View Direct Purchases & Supplier Report','2026-11-09 21:19:18','reports_purchase.view'),(358,'reports_purchase','export','Export Purchases and supplier procurement report','2026-11-09 21:19:18','reports_purchase.export'),(359,'reports_purchase','print','Print Purchase procurement report','2026-11-09 21:19:18','reports_purchase.print'),(360,'reports_shop','view','View Retail Shop & POS Performance Report','2026-11-09 21:19:18','reports_shop.view'),(361,'reports_shop','export','Export Retail Shop sales report','2026-11-09 21:19:18','reports_shop.export'),(362,'reports_shop','print','Print Retail Shop performance report','2026-11-09 21:19:18','reports_shop.print'),(363,'reports_inventory','view','View Enterprise Stock Valuation Report','2026-11-09 21:19:18','reports_inventory.view'),(364,'reports_inventory','export','Export Stock Valuation & Inventory report','2026-11-09 21:19:18','reports_inventory.export'),(365,'reports_inventory','print','Print Inventory summary report','2026-11-09 21:19:18','reports_inventory.print'),(366,'purchases_returns','view','View Purchases Returns & Exchanges','2026-11-09 21:19:18','purchases_returns.view'),(367,'purchases_returns','create','Create Purchase Return / Exchange request','2026-11-09 21:19:18','purchases_returns.create'),(368,'purchases_returns','process','Process and Restock/Settle Return','2026-11-09 21:19:18','purchases_returns.process'),(369,'purchases_returns','print','Print Purchase Return Invoice / Voucher','2026-11-09 21:19:18','purchases_returns.print'),(370,'purchases_returns','delete','Delete Purchase Return record','2026-11-09 21:19:18','purchases_returns.delete'),(371,'whatsapp_dashboard','view','View WhatsApp Channel Dashboard','2026-11-09 21:19:18','whatsapp_dashboard.view'),(372,'website_dashboard','view','View Website eCommerce Dashboard','2026-11-09 21:19:18','website_dashboard.view'),(373,'reports_online','view','View Combined Online Channel Reports','2026-11-09 21:19:18','reports_online.view'),(374,'reports_online','export','Export Combined Online report','2026-11-09 21:19:18','reports_online.export'),(375,'reports_online','print','Print Combined Online report','2026-11-09 21:19:18','reports_online.print'),(376,'warehouse_rework','view','View Warehouse Rework Requests and History','2026-11-09 21:19:18','warehouse_rework.view'),(377,'warehouse_rework','create','Receive and Log damaged goods from warehouse for rework','2026-11-09 21:19:18','warehouse_rework.create'),(378,'warehouse_rework','edit','Update rework status, tailor assignment, and repair outcomes','2026-11-09 21:19:18','warehouse_rework.edit'),(379,'warehouse_rework','delete','Delete rework entries','2026-11-09 21:19:18','warehouse_rework.delete'),(380,'warehouse_rework','dispatch','Re-dispatch repaired goods back to warehouse','2026-11-09 21:19:18','warehouse_rework.dispatch'),(381,'wh_sellers','delete','Delete warehouse supplier','2026-11-09 21:19:18','wh_sellers.delete'),(382,'whatsapp_customers','view','View WhatsApp channel customers','2026-11-09 21:19:18','whatsapp_customers.view'),(383,'whatsapp_return_to_wh','view','View WhatsApp returns to warehouse','2026-11-09 21:19:18','whatsapp_return_to_wh.view'),(384,'whatsapp_return_to_wh','create','Create WhatsApp return to warehouse','2026-11-09 21:19:18','whatsapp_return_to_wh.create'),(385,'whatsapp_return_to_wh','gatepass','Print WhatsApp return-to-warehouse gate pass','2026-11-09 21:19:18','whatsapp_return_to_wh.gatepass'),(386,'whatsapp_return_to_wh','print','Print WhatsApp return-to-warehouse labels','2026-11-09 21:19:18','whatsapp_return_to_wh.print'),(387,'website_customers','view','View Website channel customers','2026-11-09 21:19:18','website_customers.view'),(388,'website_return_to_wh','view','View Website returns to warehouse','2026-11-09 21:19:18','website_return_to_wh.view'),(389,'website_return_to_wh','create','Create Website return to warehouse','2026-11-09 21:19:18','website_return_to_wh.create'),(390,'website_return_to_wh','gatepass','Print Website return-to-warehouse gate pass','2026-11-09 21:19:18','website_return_to_wh.gatepass'),(391,'website_return_to_wh','print','Print Website return-to-warehouse labels','2026-11-09 21:19:18','website_return_to_wh.print'),(392,'warehouse','create','Create warehouse transfer','2026-11-09 21:19:18','warehouse.create'),(393,'warehouse','edit','Update warehouse details','2026-11-09 21:19:18','warehouse.edit'),(394,'warehouse','delete','Delete warehouse','2026-11-09 21:19:18','warehouse.delete'),(395,'warehouse','approve','Approve warehouse transfer','2026-11-09 21:19:18','warehouse.approve'),(396,'warehouse','receive','Receive warehouse transfer','2026-11-09 21:19:18','warehouse.receive'),(397,'assets','print','Print asset labels','2026-11-09 21:19:18','assets.print');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_variants` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `size_id` int(10) unsigned DEFAULT NULL,
  `color_id` int(10) unsigned DEFAULT NULL,
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  UNIQUE KEY `qr_code` (`qr_code`),
  KEY `idx_variant_product` (`product_id`),
  KEY `idx_variant_sku` (`sku`),
  KEY `idx_variant_qr` (`qr_code`),
  KEY `size_id` (`size_id`),
  KEY `color_id` (`color_id`),
  CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_variants_ibfk_2` FOREIGN KEY (`size_id`) REFERENCES `sizes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_variants_ibfk_3` FOREIGN KEY (`color_id`) REFERENCES `colors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variants`
--

LOCK TABLES `product_variants` WRITE;
/*!40000 ALTER TABLE `product_variants` DISABLE KEYS */;
INSERT INTO `product_variants` VALUES (1,1,1,NULL,'GRM-STY-0001-S',NULL,5.00,NULL,'INDIVIDUAL',NULL,NULL,1,0,0.00,0.00,0.00,0.00,NULL,NULL,1,'2026-11-08 22:34:39','2026-11-08 22:34:39',NULL,NULL,NULL),(2,1,2,NULL,'GRM-STY-0001-M',NULL,5.00,NULL,'INDIVIDUAL',NULL,NULL,1,0,0.00,0.00,0.00,0.00,NULL,NULL,1,'2026-11-08 22:34:39','2026-11-08 22:34:39',NULL,NULL,NULL),(3,1,1,6,'GRM-STY-0001-S-RED',NULL,5.00,NULL,'INDIVIDUAL',NULL,NULL,1,0,1000.00,0.00,0.00,700.00,NULL,NULL,1,'2026-11-08 22:46:00','2026-11-08 22:46:00',NULL,NULL,NULL),(4,1,2,6,'GRM-STY-0001-M-RED',NULL,5.00,NULL,'INDIVIDUAL',NULL,NULL,1,0,1000.00,0.00,0.00,700.00,NULL,NULL,1,'2026-11-08 22:46:00','2026-11-08 22:46:00',NULL,NULL,NULL),(5,2,6,NULL,'SKU-CAT-MEKHA-0002-XS',NULL,5.00,'SKU-CAT-MEKHA-0002-XS','INDIVIDUAL',NULL,NULL,1,0,1000.00,300.00,300.00,600.00,0.600,NULL,1,'2026-11-08 23:11:24','2026-11-08 23:11:24',NULL,NULL,NULL),(6,3,1,NULL,'SKU-CAT-MEKHA-0003-S',NULL,5.00,'SKU-CAT-MEKHA-0003-S','INDIVIDUAL','TTT112631','TTT1126162',20,1,1000.00,300.00,300.00,600.00,0.300,NULL,1,'2026-11-08 23:11:24','2026-11-09 17:41:59',NULL,NULL,NULL),(7,4,2,NULL,'SKU-CAT-MEKHA-0004-M',NULL,5.00,'SKU-CAT-MEKHA-0004-M','SERIES_BATCH','TTT112681','TTT1126110',30,0,2000.00,400.00,400.00,700.00,0.500,NULL,1,'2026-11-08 23:11:24','2026-11-09 16:09:22',NULL,NULL,NULL),(8,1,1,7,'GRM-STY-0001-S-NAVY',NULL,5.00,NULL,'INDIVIDUAL',NULL,NULL,1,0,1500.00,0.00,0.00,1000.00,NULL,NULL,1,'2026-11-09 15:03:37','2026-11-09 15:03:37',NULL,NULL,NULL),(9,1,2,7,'GRM-STY-0001-M-NAVY',NULL,5.00,NULL,'INDIVIDUAL',NULL,NULL,1,0,1500.00,0.00,0.00,1000.00,NULL,NULL,1,'2026-11-09 15:03:37','2026-11-09 15:03:37',NULL,NULL,NULL),(10,5,6,NULL,'SKU-CAT-MEKHA-0005-XS',NULL,5.00,'SKU-CAT-MEKHA-0005-XS','SERIES_BATCH','TTT1126223','TTT1126342',60,0,10.00,3.00,3.00,5.00,0.500,NULL,1,'2026-11-09 19:37:32','2026-11-09 19:38:34',NULL,NULL,NULL);
/*!40000 ALTER TABLE `product_variants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_batch_items`
--

DROP TABLE IF EXISTS `production_batch_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_batch_items`
--

LOCK TABLES `production_batch_items` WRITE;
/*!40000 ALTER TABLE `production_batch_items` DISABLE KEYS */;
INSERT INTO `production_batch_items` VALUES (1,1,3,45,'[\"MSF112606\",\"MSF112607\",\"MSF112608\",\"MSF112609\",\"MSF112610\",\"MSF112611\",\"MSF112612\",\"MSF112613\",\"MSF112614\",\"MSF112615\",\"MSF112616\",\"MSF112617\",\"MSF112618\",\"MSF112619\",\"MSF112620\",\"MSF112621\",\"MSF112622\",\"MSF112623\",\"MSF112624\",\"MSF112625\",\"MSF112626\",\"MSF112627\",\"MSF112628\",\"MSF112629\",\"MSF112630\",\"MSF112631\",\"MSF112632\",\"MSF112633\",\"MSF112634\",\"MSF112635\",\"MSF112636\",\"MSF112637\",\"MSF112638\",\"MSF112639\",\"MSF112640\",\"MSF112641\",\"MSF112642\",\"MSF112643\",\"MSF112644\",\"MSF112645\",\"MSF112646\",\"MSF112647\",\"MSF112648\",\"MSF112649\",\"MSF112650\"]',40,5,0,'MSF112606,MSF112607,MSF112608,MSF112609,MSF112610,MSF112611,MSF112612,MSF112613,MSF112614,MSF112615,MSF112616,MSF112617,MSF112618,MSF112619,MSF112620,MSF112621,MSF112622,MSF112623,MSF112624,MSF112625,MSF112626,MSF112627,MSF112628,MSF112629,MSF112630,MSF112631,MSF112632,MSF112633,MSF112634,MSF112635,MSF112636,MSF112637,MSF112638,MSF112639,MSF112640,MSF112641,MSF112642,MSF112643,MSF112644,MSF112645','MSF112646,MSF112647,MSF112648,MSF112649,MSF112650'),(2,1,4,60,'[\"MSF112651\",\"MSF112652\",\"MSF112653\",\"MSF112654\",\"MSF112655\",\"MSF112656\",\"MSF112657\",\"MSF112658\",\"MSF112659\",\"MSF112660\",\"MSF112661\",\"MSF112662\",\"MSF112663\",\"MSF112664\",\"MSF112665\",\"MSF112666\",\"MSF112667\",\"MSF112668\",\"MSF112669\",\"MSF112670\",\"MSF112671\",\"MSF112672\",\"MSF112673\",\"MSF112674\",\"MSF112675\",\"MSF112676\",\"MSF112677\",\"MSF112678\",\"MSF112679\",\"MSF112680\",\"MSF112681\",\"MSF112682\",\"MSF112683\",\"MSF112684\",\"MSF112685\",\"MSF112686\",\"MSF112687\",\"MSF112688\",\"MSF112689\",\"MSF112690\",\"MSF112691\",\"MSF112692\",\"MSF112693\",\"MSF112694\",\"MSF112695\",\"MSF112696\",\"MSF112697\",\"MSF112698\",\"MSF112699\",\"MSF1126100\",\"MSF1126101\",\"MSF1126102\",\"MSF1126103\",\"MSF1126104\",\"MSF1126105\",\"MSF1126106\",\"MSF1126107\",\"MSF1126108\",\"MSF1126109\",\"MSF1126110\"]',60,0,0,'MSF112651,MSF112652,MSF112653,MSF112654,MSF112655,MSF112656,MSF112657,MSF112658,MSF112659,MSF112660,MSF112661,MSF112662,MSF112663,MSF112664,MSF112665,MSF112666,MSF112667,MSF112668,MSF112669,MSF112670,MSF112671,MSF112672,MSF112673,MSF112674,MSF112675,MSF112676,MSF112677,MSF112678,MSF112679,MSF112680,MSF112681,MSF112682,MSF112683,MSF112684,MSF112685,MSF112686,MSF112687,MSF112688,MSF112689,MSF112690,MSF112691,MSF112692,MSF112693,MSF112694,MSF112695,MSF112696,MSF112697,MSF112698,MSF112699,MSF1126100,MSF1126101,MSF1126102,MSF1126103,MSF1126104,MSF1126105,MSF1126106,MSF1126107,MSF1126108,MSF1126109,MSF1126110',NULL);
/*!40000 ALTER TABLE `production_batch_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_batches`
--

DROP TABLE IF EXISTS `production_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_batches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `batch_number` varchar(50) NOT NULL,
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch_number` (`batch_number`),
  KEY `program_id` (`program_id`),
  KEY `qc_batch_id` (`qc_batch_id`),
  KEY `destination_warehouse_id` (`destination_warehouse_id`),
  KEY `prepared_by` (`prepared_by`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `production_batches_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `production_programs` (`id`),
  CONSTRAINT `production_batches_ibfk_2` FOREIGN KEY (`qc_batch_id`) REFERENCES `qc_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_batches_ibfk_3` FOREIGN KEY (`destination_warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `production_batches_ibfk_4` FOREIGN KEY (`prepared_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_batches_ibfk_5` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_batches`
--

LOCK TABLES `production_batches` WRITE;
/*!40000 ALTER TABLE `production_batches` DISABLE KEYS */;
INSERT INTO `production_batches` VALUES (1,'BAT-20261108-0001',1,1,NULL,0,105,1,1,1,'RECEIVED',1,1,'2026-11-08 22:46:37','2026-11-08 22:46:32','2026-11-09 18:07:09',0,100,5,0,0.00,0.00,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `production_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_bill_item_tags`
--

DROP TABLE IF EXISTS `production_bill_item_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_bill_item_tags`
--

LOCK TABLES `production_bill_item_tags` WRITE;
/*!40000 ALTER TABLE `production_bill_item_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `production_bill_item_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_bill_items`
--

DROP TABLE IF EXISTS `production_bill_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_bill_items`
--

LOCK TABLES `production_bill_items` WRITE;
/*!40000 ALTER TABLE `production_bill_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `production_bill_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_bills`
--

DROP TABLE IF EXISTS `production_bills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_bills` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bill_number` varchar(50) NOT NULL,
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_production_bills_number` (`bill_number`),
  KEY `idx_production_bills_date` (`bill_date`),
  KEY `idx_production_bills_company` (`company_name`),
  KEY `idx_production_bills_customer_phone` (`customer_phone`),
  KEY `production_bills_ibfk_1` (`created_by`),
  CONSTRAINT `production_bills_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_bills`
--

LOCK TABLES `production_bills` WRITE;
/*!40000 ALTER TABLE `production_bills` DISABLE KEYS */;
/*!40000 ALTER TABLE `production_bills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_deleted_label_codes`
--

DROP TABLE IF EXISTS `production_deleted_label_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_deleted_label_codes` (
  `code` varchar(50) NOT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_deleted_label_codes`
--

LOCK TABLES `production_deleted_label_codes` WRITE;
/*!40000 ALTER TABLE `production_deleted_label_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `production_deleted_label_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_expense_items`
--

DROP TABLE IF EXISTS `production_expense_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_expense_items`
--

LOCK TABLES `production_expense_items` WRITE;
/*!40000 ALTER TABLE `production_expense_items` DISABLE KEYS */;
INSERT INTO `production_expense_items` VALUES (1,'White Sewing Thread Cones 40/2','SEWING_MATERIALS','CONE',85.00,'2026-11-08 22:01:39','2026-11-08 22:01:39'),(2,'Black Sewing Thread Cones 40/2','SEWING_MATERIALS','CONE',85.00,'2026-11-08 22:01:39','2026-11-08 22:01:39'),(3,'Overlock Machine Needles #14 (Pkt 10)','SEWING_MATERIALS','PKT',120.00,'2026-11-08 22:01:39','2026-11-08 22:01:39'),(4,'Single Needle Machine Servicing & Tuning','MAINTENANCE_REPAIR','PCS',350.00,'2026-11-08 22:01:39','2026-11-08 22:01:39'),(5,'Industrial Sewing Lubricant Oil (1 Liter)','MAINTENANCE_REPAIR','LTR',180.00,'2026-11-08 22:01:39','2026-11-08 22:01:39'),(6,'Garment Floor Packing Polybags (100 pcs)','OPERATIONAL_UTILITY','PKT',220.00,'2026-11-08 22:01:39','2026-11-08 22:01:39'),(7,'Factory Floor Miscellaneous Expense','OTHER','PCS',100.00,'2026-11-08 22:01:39','2026-11-08 22:01:39');
/*!40000 ALTER TABLE `production_expense_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_expenses`
--

DROP TABLE IF EXISTS `production_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_production_expenses_number` (`expense_number`),
  KEY `idx_production_expenses_program` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_expenses`
--

LOCK TABLES `production_expenses` WRITE;
/*!40000 ALTER TABLE `production_expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `production_expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_outward_batches`
--

DROP TABLE IF EXISTS `production_outward_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_outward_batches`
--

LOCK TABLES `production_outward_batches` WRITE;
/*!40000 ALTER TABLE `production_outward_batches` DISABLE KEYS */;
INSERT INTO `production_outward_batches` VALUES (1,1,1,105,1,'2026-11-09 18:07:09');
/*!40000 ALTER TABLE `production_outward_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_outwards`
--

DROP TABLE IF EXISTS `production_outwards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_outwards` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `outward_number` (`outward_number`),
  KEY `production_batch_id` (`production_batch_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `production_outwards_ibfk_1` FOREIGN KEY (`production_batch_id`) REFERENCES `production_batches` (`id`),
  CONSTRAINT `production_outwards_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_outwards`
--

LOCK TABLES `production_outwards` WRITE;
/*!40000 ALTER TABLE `production_outwards` DISABLE KEYS */;
INSERT INTO `production_outwards` VALUES (1,1,NULL,0,NULL,1,105,1,105,'OUT-20261108-0001','2026-11-08 22:46:00',NULL,'DISPATCHED','tn39cp0015',NULL,NULL,NULL,NULL,NULL,'',1,'2026-11-08 22:46:52','2026-11-08 22:46:52',NULL);
/*!40000 ALTER TABLE `production_outwards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_program_items`
--

DROP TABLE IF EXISTS `production_program_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_program_items`
--

LOCK TABLES `production_program_items` WRITE;
/*!40000 ALTER TABLE `production_program_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `production_program_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_program_rolls`
--

DROP TABLE IF EXISTS `production_program_rolls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `color` varchar(100) DEFAULT NULL,
  `design_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ppr_program` (`production_program_id`),
  KEY `idx_ppr_roll` (`seller_roll_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_program_rolls`
--

LOCK TABLES `production_program_rolls` WRITE;
/*!40000 ALTER TABLE `production_program_rolls` DISABLE KEYS */;
INSERT INTO `production_program_rolls` VALUES (1,1,1,1,1,4,1,1,1,3.00,5000.00,'RED','RED-PANDA',NULL,'2026-11-08 22:39:10','2026-11-08 22:39:10',NULL,NULL),(2,2,2,3,1,4,1,1,1,2.00,6500.00,'NAVY BLUE','BEE',NULL,'2026-11-09 15:00:13','2026-11-09 15:00:13',NULL,NULL),(3,3,3,1,1,4,1,1,1,3.00,5000.00,'RED','RED-PANDA',NULL,'2026-11-09 22:14:15','2026-11-09 22:14:15',NULL,NULL);
/*!40000 ALTER TABLE `production_program_rolls` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_programs`
--

DROP TABLE IF EXISTS `production_programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_programs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `program_number` (`program_number`),
  KEY `idx_program_unit` (`production_unit_id`),
  KEY `idx_program_status` (`status`),
  KEY `idx_program_date` (`target_date`),
  KEY `product_id` (`product_id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `approved_by` (`approved_by`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `production_programs_ibfk_1` FOREIGN KEY (`production_unit_id`) REFERENCES `production_units` (`id`),
  CONSTRAINT `production_programs_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `production_programs_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_programs_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_programs_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_programs`
--

LOCK TABLES `production_programs` WRITE;
/*!40000 ALTER TABLE `production_programs` DISABLE KEYS */;
INSERT INTO `production_programs` VALUES (1,'PRG-20261108-0001','SHIRT PROGRAM',NULL,NULL,1,1,1,3.00,5000.00,4,1,1,1,105,'2026-11-08','2026-11-22','2026-11-22','MEDIUM',1,'IN_PROGRESS',1,'2026-11-08 22:39:10','',1,1,'2026-11-08 22:39:10','2026-11-08 22:46:37',0,NULL,NULL),(2,'PRG-20261109-0002','SECOND PROGRAM',NULL,NULL,1,3,1,2.00,6500.00,4,1,1,1,0,'2026-11-09','2026-11-23','2026-11-23','MEDIUM',NULL,'IN_PROGRESS',1,'2026-11-09 15:00:13','',1,1,'2026-11-09 15:00:13','2026-11-09 15:00:51',0,NULL,NULL),(3,'PRG-20261109-0003','TEST',NULL,NULL,1,1,1,3.00,5000.00,4,1,1,1,0,'2026-11-09','2026-11-23','2026-11-23','MEDIUM',NULL,'APPROVED',1,'2026-11-09 22:14:15','',1,1,'2026-11-09 22:14:15','2026-11-09 22:14:15',0,NULL,NULL);
/*!40000 ALTER TABLE `production_programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_purchase_items`
--

DROP TABLE IF EXISTS `production_purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_purchase_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` int(10) unsigned NOT NULL,
  `item_type` enum('FABRIC_ROLL','GENERAL_SUPPLY') NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `unit` varchar(30) NOT NULL,
  `weight_kg` decimal(10,3) DEFAULT NULL,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gst_type` varchar(10) NOT NULL DEFAULT 'EXEMPTED',
  `tax_input_type` varchar(10) NOT NULL DEFAULT 'PERCENT',
  `sgst_value` decimal(12,3) NOT NULL DEFAULT 0.000,
  `cgst_value` decimal(12,3) NOT NULL DEFAULT 0.000,
  `igst_value` decimal(12,3) NOT NULL DEFAULT 0.000,
  `sgst_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cgst_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `igst_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `roll_number` varchar(50) DEFAULT NULL,
  `color` varchar(100) DEFAULT NULL,
  `design_name` varchar(150) DEFAULT NULL,
  `design_count` int(11) DEFAULT NULL,
  `seller_roll_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_production_purchase_seller_roll` (`seller_roll_id`),
  KEY `idx_production_purchase_items_purchase` (`purchase_id`),
  KEY `idx_production_purchase_items_stock` (`item_type`,`item_name`,`unit`),
  CONSTRAINT `production_purchase_items_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `production_purchases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `production_purchase_items_ibfk_2` FOREIGN KEY (`seller_roll_id`) REFERENCES `seller_rolls` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_purchase_items`
--

LOCK TABLES `production_purchase_items` WRITE;
/*!40000 ALTER TABLE `production_purchase_items` DISABLE KEYS */;
INSERT INTO `production_purchase_items` VALUES (1,1,'FABRIC_ROLL','100% COMBED COTTON 30S SINGLE JERSEY (160 GSM)',10.000,'PCS',30.000,50000.00,50000.00,'EXEMPTED','PERCENT',0.000,0.000,0.000,0.00,0.00,0.00,'ROLL #1','RED','RED-PANDA',1,1,'2026-11-08 22:09:46'),(2,1,'FABRIC_ROLL','100% COMBED COTTON 30S SINGLE JERSEY (160 GSM)',20.000,'PCS',40.000,60000.00,60000.00,'EXEMPTED','PERCENT',0.000,0.000,0.000,0.00,0.00,0.00,'ROLL #2','BLUE','BUTTERFLY',1,2,'2026-11-08 22:09:46'),(3,1,'FABRIC_ROLL','100% COMBED COTTON 30S SINGLE JERSEY (160 GSM)',10.000,'PCS',20.000,65000.00,68250.00,'BOTH','PERCENT',2.500,2.500,0.000,1625.00,1625.00,0.00,'ROLL #3','NAVY BLUE','BEE',1,3,'2026-11-08 22:09:46'),(4,2,'FABRIC_ROLL','100% COTTON 24S SINGLE JERSEY (180 GSM)',10.000,'PCS',200.000,70000.00,70000.00,'EXEMPTED','PERCENT',0.000,0.000,0.000,0.00,0.00,0.00,'ROLL #1','YELLOW','YELLOW1',1,4,'2026-11-09 22:29:07'),(5,3,'FABRIC_ROLL','95% COTTON 5% SPANDEX/LYCRA (190 GSM)',30.000,'PCS',300.000,75000.00,75000.00,'EXEMPTED','PERCENT',0.000,0.000,0.000,0.00,0.00,0.00,'ROLL #1','NAVY','NAVY123',1,5,'2026-11-09 22:43:47');
/*!40000 ALTER TABLE `production_purchase_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_purchases`
--

DROP TABLE IF EXISTS `production_purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_purchases` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_number` varchar(50) NOT NULL,
  `seller_id` int(10) unsigned NOT NULL,
  `purchase_date` date NOT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `document_url` varchar(500) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_production_purchase_number` (`purchase_number`),
  KEY `idx_production_purchases_seller_date` (`seller_id`,`purchase_date`),
  CONSTRAINT `production_purchases_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_purchases`
--

LOCK TABLES `production_purchases` WRITE;
/*!40000 ALTER TABLE `production_purchases` DISABLE KEYS */;
INSERT INTO `production_purchases` VALUES (1,'PP-2026-000001',1,'2026-11-08','27AAACR5055K1Z8','https://res.cloudinary.com/cohbobmx/image/upload/v1794155984/grm_inventory/production/purchases/kfjgmmagpca9mz7feoxp.png','testing',178250.00,NULL,'2026-11-08 22:09:46','2026-11-08 22:09:46'),(2,'PP-2026-000002',1,'2026-11-09',NULL,NULL,NULL,70000.00,NULL,'2026-11-09 22:29:07','2026-11-09 22:29:07'),(3,'PP-2026-000003',1,'2026-11-09',NULL,'https://res.cloudinary.com/cohbobmx/image/upload/v1794244426/grm_inventory/production/purchases/kac22mtgwwbhtstin9oi.png',NULL,75000.00,NULL,'2026-11-09 22:43:47','2026-11-09 22:43:47');
/*!40000 ALTER TABLE `production_purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_return_items`
--

DROP TABLE IF EXISTS `production_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_return_items`
--

LOCK TABLES `production_return_items` WRITE;
/*!40000 ALTER TABLE `production_return_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `production_return_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_returns`
--

DROP TABLE IF EXISTS `production_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_returns` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
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
  KEY `idx_prod_returns_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_returns`
--

LOCK TABLES `production_returns` WRITE;
/*!40000 ALTER TABLE `production_returns` DISABLE KEYS */;
/*!40000 ALTER TABLE `production_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_stock_request_items`
--

DROP TABLE IF EXISTS `production_stock_request_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `fulfilled_qty` int(10) unsigned NOT NULL DEFAULT 0,
  `status` enum('PENDING','PARTIAL','ALLOCATED','DISPATCHED') NOT NULL DEFAULT 'PENDING',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_prod_stock_req_items_req` (`request_id`),
  CONSTRAINT `fk_prod_stock_req_items_parent` FOREIGN KEY (`request_id`) REFERENCES `production_stock_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_stock_request_items`
--

LOCK TABLES `production_stock_request_items` WRITE;
/*!40000 ALTER TABLE `production_stock_request_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `production_stock_request_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_stock_requests`
--

DROP TABLE IF EXISTS `production_stock_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_stock_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `request_number` varchar(50) NOT NULL,
  `warehouse_name` varchar(150) NOT NULL DEFAULT 'Central Warehouse',
  `requested_by_name` varchar(150) NOT NULL DEFAULT 'Warehouse Incharge',
  `priority` enum('LOW','NORMAL','HIGH','URGENT') NOT NULL DEFAULT 'NORMAL',
  `required_by_date` date DEFAULT NULL,
  `total_requested_pieces` int(10) unsigned NOT NULL DEFAULT 0,
  `fulfilled_pieces` int(10) unsigned NOT NULL DEFAULT 0,
  `status` enum('PENDING','APPROVED','IN_PRODUCTION','READY_FOR_DISPATCH','FULFILLED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_prod_stock_req_num` (`request_number`),
  KEY `idx_prod_stock_req_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_stock_requests`
--

LOCK TABLES `production_stock_requests` WRITE;
/*!40000 ALTER TABLE `production_stock_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `production_stock_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_units`
--

DROP TABLE IF EXISTS `production_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_units` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `manager_id` (`manager_id`),
  CONSTRAINT `production_units_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_units`
--

LOCK TABLES `production_units` WRITE;
/*!40000 ALTER TABLE `production_units` DISABLE KEYS */;
INSERT INTO `production_units` VALUES (1,'Tirupur Apparel Unit 1','PU-01','Tirupur Manufacturing Hub, Avinashi Road, Tirupur - 641603','9876543210',NULL,NULL,'33AAAAA0000A1Z5',1,'2026-11-07 22:11:45','2026-11-08 01:42:35',NULL,NULL,0);
/*!40000 ALTER TABLE `production_units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_warehouse_reworks`
--

DROP TABLE IF EXISTS `production_warehouse_reworks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_warehouse_reworks`
--

LOCK TABLES `production_warehouse_reworks` WRITE;
/*!40000 ALTER TABLE `production_warehouse_reworks` DISABLE KEYS */;
/*!40000 ALTER TABLE `production_warehouse_reworks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'MENS - SHIRT - FULL SLEEVE','GRM-STY-0001',NULL,4,1,1,'M','S','F','MSF',NULL,NULL,'OWN_PRODUCTION',0,'','','MENS',5.00,90,0.000,10,1000,NULL,NULL,1,'2026-11-08 22:34:39','2026-11-09 21:24:06',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(2,'SKY BLUE SHIRT','CAT-MEKHA-0002','CAT-MEKHA-0002',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,'EXTERNAL_PURCHASE',0,NULL,NULL,'5525',5.00,30,0.600,0,9999,NULL,NULL,1,'2026-11-08 23:11:24','2026-11-08 23:11:24',NULL,1,NULL,7,1,1,NULL,NULL,NULL),(3,'SUMMA PRODUCTS','CAT-MEKHA-0003','CAT-MEKHA-0003',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,'EXTERNAL_PURCHASE',1,NULL,NULL,'5525',5.00,40,0.300,0,9999,NULL,NULL,1,'2026-11-08 23:11:24','2026-11-09 16:48:02',NULL,1,NULL,7,1,1,NULL,NULL,NULL),(4,'TESTING PRODUCTS','CAT-MEKHA-0004','CAT-MEKHA-0004',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,'EXTERNAL_PURCHASE',0,NULL,NULL,'5526',5.00,18,0.500,0,9999,NULL,NULL,1,'2026-11-08 23:11:24','2026-11-09 20:47:51',NULL,1,NULL,7,1,1,NULL,NULL,NULL),(5,'TEST123','CAT-MEKHA-0005','CAT-MEKHA-0005',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,'EXTERNAL_PURCHASE',0,NULL,NULL,'5208',5.00,120,0.500,0,9999,NULL,NULL,1,'2026-11-09 19:37:32','2026-11-09 19:38:34',NULL,1,NULL,7,1,1,NULL,NULL,NULL);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_inward_items`
--

DROP TABLE IF EXISTS `purchase_inward_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_inward_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_inward_id` int(10) unsigned NOT NULL,
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
  KEY `variant_id` (`variant_id`),
  KEY `location_id` (`location_id`),
  CONSTRAINT `purchase_inward_items_ibfk_1` FOREIGN KEY (`purchase_inward_id`) REFERENCES `purchase_inwards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_inward_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`),
  CONSTRAINT `purchase_inward_items_ibfk_3` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_inward_items`
--

LOCK TABLES `purchase_inward_items` WRITE;
/*!40000 ALTER TABLE `purchase_inward_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_inward_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_inwards`
--

DROP TABLE IF EXISTS `purchase_inwards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `grn_number` (`grn_number`),
  KEY `purchase_order_id` (`purchase_order_id`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `received_by` (`received_by`),
  KEY `verified_by` (`verified_by`),
  CONSTRAINT `purchase_inwards_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`),
  CONSTRAINT `purchase_inwards_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `purchase_inwards_ibfk_3` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_inwards_ibfk_4` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_inwards`
--

LOCK TABLES `purchase_inwards` WRITE;
/*!40000 ALTER TABLE `purchase_inwards` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_inwards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_items`
--

DROP TABLE IF EXISTS `purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `gst_percentage` decimal(5,2) DEFAULT 0.00,
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_items`
--

LOCK TABLES `purchase_items` WRITE;
/*!40000 ALTER TABLE `purchase_items` DISABLE KEYS */;
INSERT INTO `purchase_items` VALUES (1,1,5,30,300.00,0.00,5.00,225.00,225.00,0.00,9450.00,NULL,NULL,NULL,1000.00,0,0.00,NULL),(2,1,6,20,300.00,0.00,5.00,150.00,150.00,0.00,6300.00,NULL,NULL,NULL,1000.00,0,0.00,NULL),(3,1,7,30,400.00,0.00,5.00,300.00,300.00,0.00,12600.00,NULL,NULL,NULL,2000.00,0,0.00,NULL),(4,2,10,60,3.00,0.00,5.00,4.50,4.50,0.00,189.00,NULL,NULL,NULL,10.00,0,0.00,NULL),(5,3,7,1,700.00,65.00,5.00,16.67,16.66,0.00,700.00,'TTT112681',NULL,NULL,2000.00,0,0.00,'QR: TTT112681'),(6,3,7,1,700.00,65.00,5.00,16.67,16.66,0.00,700.00,'TTT112681',NULL,NULL,2000.00,0,0.00,'QR: TTT112681');
/*!40000 ALTER TABLE `purchase_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
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
  `warehouse_supplier_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `po_number` (`po_number`),
  KEY `seller_id` (`seller_id`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`),
  CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `purchase_orders_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
INSERT INTO `purchase_orders` VALUES (1,'PO-WH-20261108-0001',NULL,NULL,1,'2026-11-08','2026-11-08',27000.00,0.00,675.00,675.00,0.00,28350.00,'PAID','CASH','DRAFT','Inward bill products from MEKHA (Awaiting QR & Inward)',1,'2026-11-08 23:11:24','2026-11-08 23:11:24',0,'https://res.cloudinary.com/cohbobmx/image/upload/v1794159475/grm_inventory/bills/ym4okvvwe3nir6qh82ue.png','grm_inventory/bills/ym4okvvwe3nir6qh82ue',NULL,0.00,0.00,1),(2,'PO-WH-20261109-0001',NULL,NULL,1,'2026-11-09','2026-11-09',180.00,0.00,4.50,4.50,0.00,189.00,'PAID','CASH','DRAFT','Inward bill products from MEKHA (Awaiting QR & Inward)',1,'2026-11-09 19:37:32','2026-11-09 19:37:32',0,'https://res.cloudinary.com/cohbobmx/image/upload/v1794159475/grm_inventory/bills/ym4okvvwe3nir6qh82ue.png','grm_inventory/bills/ym4okvvwe3nir6qh82ue',NULL,0.00,0.00,1),(3,'DPO-20261109-0001',NULL,1,1,'2026-11-09','2026-11-09',1333.34,2600.00,33.34,33.32,0.00,1400.00,'PAID','CASH','','',1,'2026-11-09 20:47:51','2026-11-09 20:47:51',0,NULL,NULL,NULL,66.66,0.00,NULL);
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qc_batches`
--

DROP TABLE IF EXISTS `qc_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qc_batches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `batch_number` varchar(50) DEFAULT NULL,
  `qc_number` varchar(50) DEFAULT NULL,
  `program_id` int(10) unsigned NOT NULL,
  `stitching_batch_id` int(10) unsigned DEFAULT NULL,
  `label_batch_id` int(10) unsigned DEFAULT NULL,
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
  PRIMARY KEY (`id`),
  KEY `program_id` (`program_id`),
  KEY `stitching_batch_id` (`stitching_batch_id`),
  KEY `inspector_id` (`inspector_id`),
  KEY `fk_qc_label_batch` (`label_batch_id`),
  CONSTRAINT `fk_qc_label_batch` FOREIGN KEY (`label_batch_id`) REFERENCES `label_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `qc_batches_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `production_programs` (`id`),
  CONSTRAINT `qc_batches_ibfk_2` FOREIGN KEY (`stitching_batch_id`) REFERENCES `stitching_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `qc_batches_ibfk_3` FOREIGN KEY (`inspector_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qc_batches`
--

LOCK TABLES `qc_batches` WRITE;
/*!40000 ALTER TABLE `qc_batches` DISABLE KEYS */;
INSERT INTO `qc_batches` VALUES (1,'QC-20261108-0001',NULL,1,NULL,1,1,110,110,0,0,'PASSED','','[{\"index\":1,\"tag_code\":\"MSF112601\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":true,\"defect_reason\":\"Stitching Fault\",\"defect_notes\":\"\"},{\"index\":2,\"tag_code\":\"MSF112602\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":true,\"defect_reason\":\"Fabric Stain \\/ Dirt\",\"defect_notes\":\"\"},{\"index\":3,\"tag_code\":\"MSF112603\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":true,\"defect_reason\":\"Fabric Damage \\/ Hole\",\"defect_notes\":\"\"},{\"index\":4,\"tag_code\":\"MSF112604\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":true,\"defect_reason\":\"Size \\/ Pattern Mismatch\",\"defect_notes\":\"\"},{\"index\":5,\"tag_code\":\"MSF112605\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":true,\"defect_reason\":\"Fabric Damage \\/ Hole\",\"defect_notes\":\"\"},{\"index\":6,\"tag_code\":\"MSF112606\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":7,\"tag_code\":\"MSF112607\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":8,\"tag_code\":\"MSF112608\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":9,\"tag_code\":\"MSF112609\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":10,\"tag_code\":\"MSF112610\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":11,\"tag_code\":\"MSF112611\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":12,\"tag_code\":\"MSF112612\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":13,\"tag_code\":\"MSF112613\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":14,\"tag_code\":\"MSF112614\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":15,\"tag_code\":\"MSF112615\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":16,\"tag_code\":\"MSF112616\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":17,\"tag_code\":\"MSF112617\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":18,\"tag_code\":\"MSF112618\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":19,\"tag_code\":\"MSF112619\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":20,\"tag_code\":\"MSF112620\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":21,\"tag_code\":\"MSF112621\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":22,\"tag_code\":\"MSF112622\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":23,\"tag_code\":\"MSF112623\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":24,\"tag_code\":\"MSF112624\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":25,\"tag_code\":\"MSF112625\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":26,\"tag_code\":\"MSF112626\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":27,\"tag_code\":\"MSF112627\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":28,\"tag_code\":\"MSF112628\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":29,\"tag_code\":\"MSF112629\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":30,\"tag_code\":\"MSF112630\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":31,\"tag_code\":\"MSF112631\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":32,\"tag_code\":\"MSF112632\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":33,\"tag_code\":\"MSF112633\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":34,\"tag_code\":\"MSF112634\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":35,\"tag_code\":\"MSF112635\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":36,\"tag_code\":\"MSF112636\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":37,\"tag_code\":\"MSF112637\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":38,\"tag_code\":\"MSF112638\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":39,\"tag_code\":\"MSF112639\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":40,\"tag_code\":\"MSF112640\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":41,\"tag_code\":\"MSF112641\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":42,\"tag_code\":\"MSF112642\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":43,\"tag_code\":\"MSF112643\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":44,\"tag_code\":\"MSF112644\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":45,\"tag_code\":\"MSF112645\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":46,\"tag_code\":\"MSF112646\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":47,\"tag_code\":\"MSF112647\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":48,\"tag_code\":\"MSF112648\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":49,\"tag_code\":\"MSF112649\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":50,\"tag_code\":\"MSF112650\",\"item_id\":1,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":51,\"tag_code\":\"MSF112651\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":52,\"tag_code\":\"MSF112652\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":53,\"tag_code\":\"MSF112653\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":54,\"tag_code\":\"MSF112654\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":55,\"tag_code\":\"MSF112655\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":56,\"tag_code\":\"MSF112656\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":57,\"tag_code\":\"MSF112657\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":58,\"tag_code\":\"MSF112658\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":59,\"tag_code\":\"MSF112659\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":60,\"tag_code\":\"MSF112660\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":61,\"tag_code\":\"MSF112661\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":62,\"tag_code\":\"MSF112662\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":63,\"tag_code\":\"MSF112663\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":64,\"tag_code\":\"MSF112664\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":65,\"tag_code\":\"MSF112665\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":66,\"tag_code\":\"MSF112666\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":67,\"tag_code\":\"MSF112667\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":68,\"tag_code\":\"MSF112668\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":69,\"tag_code\":\"MSF112669\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":70,\"tag_code\":\"MSF112670\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":71,\"tag_code\":\"MSF112671\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":72,\"tag_code\":\"MSF112672\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":73,\"tag_code\":\"MSF112673\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":74,\"tag_code\":\"MSF112674\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":75,\"tag_code\":\"MSF112675\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":76,\"tag_code\":\"MSF112676\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":77,\"tag_code\":\"MSF112677\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":78,\"tag_code\":\"MSF112678\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":79,\"tag_code\":\"MSF112679\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":80,\"tag_code\":\"MSF112680\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":81,\"tag_code\":\"MSF112681\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":82,\"tag_code\":\"MSF112682\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":83,\"tag_code\":\"MSF112683\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":84,\"tag_code\":\"MSF112684\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":85,\"tag_code\":\"MSF112685\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":86,\"tag_code\":\"MSF112686\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":87,\"tag_code\":\"MSF112687\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":88,\"tag_code\":\"MSF112688\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":89,\"tag_code\":\"MSF112689\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":90,\"tag_code\":\"MSF112690\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":91,\"tag_code\":\"MSF112691\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":92,\"tag_code\":\"MSF112692\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":93,\"tag_code\":\"MSF112693\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":94,\"tag_code\":\"MSF112694\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":95,\"tag_code\":\"MSF112695\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":96,\"tag_code\":\"MSF112696\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":97,\"tag_code\":\"MSF112697\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":98,\"tag_code\":\"MSF112698\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":99,\"tag_code\":\"MSF112699\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":100,\"tag_code\":\"MSF1126100\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":101,\"tag_code\":\"MSF1126101\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":102,\"tag_code\":\"MSF1126102\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":103,\"tag_code\":\"MSF1126103\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":104,\"tag_code\":\"MSF1126104\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":105,\"tag_code\":\"MSF1126105\",\"item_id\":2,\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":106,\"tag_code\":\"MSF1126106\",\"item_id\":\"2\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":107,\"tag_code\":\"MSF1126107\",\"item_id\":\"2\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":108,\"tag_code\":\"MSF1126108\",\"item_id\":\"2\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":109,\"tag_code\":\"MSF1126109\",\"item_id\":\"2\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":110,\"tag_code\":\"MSF1126110\",\"item_id\":\"2\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"}]','2026-11-08 22:43:27','2026-11-09 15:03:28',0,0,0,0,'PENDING'),(2,'QC-20261109-0001',NULL,2,NULL,2,1,100,100,0,0,'PASSED','','[{\"index\":1,\"tag_code\":\"MSF1126111\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":2,\"tag_code\":\"MSF1126112\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":3,\"tag_code\":\"MSF1126113\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":4,\"tag_code\":\"MSF1126114\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":5,\"tag_code\":\"MSF1126115\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":6,\"tag_code\":\"MSF1126116\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":7,\"tag_code\":\"MSF1126117\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":8,\"tag_code\":\"MSF1126118\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":9,\"tag_code\":\"MSF1126119\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":10,\"tag_code\":\"MSF1126120\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":11,\"tag_code\":\"MSF1126121\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":12,\"tag_code\":\"MSF1126122\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":13,\"tag_code\":\"MSF1126123\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":14,\"tag_code\":\"MSF1126124\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":15,\"tag_code\":\"MSF1126125\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":16,\"tag_code\":\"MSF1126126\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":17,\"tag_code\":\"MSF1126127\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":18,\"tag_code\":\"MSF1126128\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":19,\"tag_code\":\"MSF1126129\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":20,\"tag_code\":\"MSF1126130\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":21,\"tag_code\":\"MSF1126131\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":22,\"tag_code\":\"MSF1126132\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":23,\"tag_code\":\"MSF1126133\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":24,\"tag_code\":\"MSF1126134\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":25,\"tag_code\":\"MSF1126135\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":26,\"tag_code\":\"MSF1126136\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":27,\"tag_code\":\"MSF1126137\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":28,\"tag_code\":\"MSF1126138\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":29,\"tag_code\":\"MSF1126139\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":30,\"tag_code\":\"MSF1126140\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":31,\"tag_code\":\"MSF1126141\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":32,\"tag_code\":\"MSF1126142\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":33,\"tag_code\":\"MSF1126143\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":34,\"tag_code\":\"MSF1126144\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":35,\"tag_code\":\"MSF1126145\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":36,\"tag_code\":\"MSF1126146\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":37,\"tag_code\":\"MSF1126147\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":38,\"tag_code\":\"MSF1126148\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":39,\"tag_code\":\"MSF1126149\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":40,\"tag_code\":\"MSF1126150\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":41,\"tag_code\":\"MSF1126151\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":42,\"tag_code\":\"MSF1126152\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":43,\"tag_code\":\"MSF1126153\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":44,\"tag_code\":\"MSF1126154\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":45,\"tag_code\":\"MSF1126155\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":46,\"tag_code\":\"MSF1126156\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":47,\"tag_code\":\"MSF1126157\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":48,\"tag_code\":\"MSF1126158\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":49,\"tag_code\":\"MSF1126159\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":50,\"tag_code\":\"MSF1126160\",\"item_id\":\"3\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":51,\"tag_code\":\"MSF1126161\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":52,\"tag_code\":\"MSF1126162\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":53,\"tag_code\":\"MSF1126163\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":54,\"tag_code\":\"MSF1126164\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":55,\"tag_code\":\"MSF1126165\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":56,\"tag_code\":\"MSF1126166\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":57,\"tag_code\":\"MSF1126167\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":58,\"tag_code\":\"MSF1126168\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":59,\"tag_code\":\"MSF1126169\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":60,\"tag_code\":\"MSF1126170\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":61,\"tag_code\":\"MSF1126171\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":62,\"tag_code\":\"MSF1126172\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":63,\"tag_code\":\"MSF1126173\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":64,\"tag_code\":\"MSF1126174\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":65,\"tag_code\":\"MSF1126175\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":66,\"tag_code\":\"MSF1126176\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":67,\"tag_code\":\"MSF1126177\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":68,\"tag_code\":\"MSF1126178\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":69,\"tag_code\":\"MSF1126179\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":70,\"tag_code\":\"MSF1126180\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":71,\"tag_code\":\"MSF1126181\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":72,\"tag_code\":\"MSF1126182\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":73,\"tag_code\":\"MSF1126183\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":74,\"tag_code\":\"MSF1126184\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":75,\"tag_code\":\"MSF1126185\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":76,\"tag_code\":\"MSF1126186\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":77,\"tag_code\":\"MSF1126187\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":78,\"tag_code\":\"MSF1126188\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":79,\"tag_code\":\"MSF1126189\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":80,\"tag_code\":\"MSF1126190\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":81,\"tag_code\":\"MSF1126191\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":82,\"tag_code\":\"MSF1126192\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":83,\"tag_code\":\"MSF1126193\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":84,\"tag_code\":\"MSF1126194\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":85,\"tag_code\":\"MSF1126195\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":86,\"tag_code\":\"MSF1126196\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":87,\"tag_code\":\"MSF1126197\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":88,\"tag_code\":\"MSF1126198\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":89,\"tag_code\":\"MSF1126199\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":90,\"tag_code\":\"MSF1126200\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":91,\"tag_code\":\"MSF1126201\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":92,\"tag_code\":\"MSF1126202\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":93,\"tag_code\":\"MSF1126203\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":94,\"tag_code\":\"MSF1126204\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":95,\"tag_code\":\"MSF1126205\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":96,\"tag_code\":\"MSF1126206\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":97,\"tag_code\":\"MSF1126207\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":98,\"tag_code\":\"MSF1126208\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":99,\"tag_code\":\"MSF1126209\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"},{\"index\":100,\"tag_code\":\"MSF1126210\",\"item_id\":\"4\",\"status\":\"APPROVED\",\"is_reinspected\":false,\"defect_reason\":\"\",\"defect_notes\":\"\"}]','2026-11-09 15:02:16','2026-11-09 15:02:21',0,0,0,0,'PENDING');
/*!40000 ALTER TABLE `qc_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qc_items`
--

DROP TABLE IF EXISTS `qc_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  CONSTRAINT `qc_items_ibfk_1` FOREIGN KEY (`qc_batch_id`) REFERENCES `qc_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qc_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qc_items`
--

LOCK TABLES `qc_items` WRITE;
/*!40000 ALTER TABLE `qc_items` DISABLE KEYS */;
INSERT INTO `qc_items` VALUES (1,1,3,4,1,1,'MENS ÔÇ║ SHIRT ÔÇ║ FULL SLEEVE','S','RED','MSF112601',1000.00,700.00,50,50,0,0,'Stitching Fault',NULL,'OTHER','MINOR',1,'PASSED',NULL,'2026-11-08 22:43:27','2026-11-09 15:03:28'),(2,1,4,4,1,1,'MENS ÔÇ║ SHIRT ÔÇ║ FULL SLEEVE','M','RED','MSF112651',1000.00,700.00,60,60,0,0,NULL,NULL,'OTHER','MINOR',1,'PASSED',NULL,'2026-11-08 22:43:27','2026-11-08 22:46:00'),(3,2,8,4,1,1,'MENS ÔÇ║ SHIRT ÔÇ║ FULL SLEEVE','S','NAVY BLUE','MSF1126111',1500.00,1000.00,50,50,0,0,NULL,NULL,'OTHER','MINOR',1,'PASSED',NULL,'2026-11-09 15:02:16','2026-11-09 15:03:37'),(4,2,9,4,1,1,'MENS ÔÇ║ SHIRT ÔÇ║ FULL SLEEVE','M','NAVY BLUE','MSF1126161',1500.00,1000.00,50,50,0,0,NULL,NULL,'OTHER','MINOR',1,'PASSED',NULL,'2026-11-09 15:02:16','2026-11-09 15:03:37');
/*!40000 ALTER TABLE `qc_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `return_items`
--

DROP TABLE IF EXISTS `return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `return_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `return_id` int(10) unsigned NOT NULL,
  `sale_item_id` int(10) unsigned DEFAULT NULL,
  `variant_id` int(10) unsigned NOT NULL,
  `quantity` int(11) NOT NULL,
  `item_condition` enum('GOOD','DAMAGED','DEFECTIVE') DEFAULT 'GOOD',
  `destination` enum('AVAILABLE','DAMAGE','REWORK','DISPOSAL') DEFAULT 'AVAILABLE',
  `refund_amount` decimal(10,2) DEFAULT 0.00,
  `condition_type` enum('GOOD','DAMAGED','DEFECTIVE') DEFAULT 'GOOD',
  `reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `return_id` (`return_id`),
  KEY `sale_item_id` (`sale_item_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `return_items_ibfk_1` FOREIGN KEY (`return_id`) REFERENCES `returns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `return_items_ibfk_2` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `return_items_ibfk_3` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `return_items`
--

LOCK TABLES `return_items` WRITE;
/*!40000 ALTER TABLE `return_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `return_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `returns`
--

DROP TABLE IF EXISTS `returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `returns` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `return_number` varchar(50) NOT NULL,
  `source_type` varchar(50) NOT NULL DEFAULT 'SHOP',
  `sale_id` int(10) unsigned DEFAULT NULL,
  `purchase_order_id` int(10) unsigned DEFAULT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `warehouse_id` int(10) unsigned DEFAULT NULL,
  `return_type` enum('RETURN','EXCHANGE') DEFAULT 'RETURN',
  `reason` text NOT NULL,
  `inspection_status` enum('PENDING','INSPECTING','APPROVED','REJECTED') DEFAULT 'PENDING',
  `refund_amount` decimal(10,2) DEFAULT 0.00,
  `refund_mode` enum('CASH','UPI','CARD','ONLINE','CREDIT_NOTE') DEFAULT NULL,
  `refund_status` enum('PENDING','PROCESSED','COMPLETED') DEFAULT 'PENDING',
  `inspected_by` int(10) unsigned DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('PENDING','APPROVED','REJECTED','REFUNDED') DEFAULT 'PENDING',
  `processed_by` int(10) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `return_number` (`return_number`),
  KEY `sale_id` (`sale_id`),
  KEY `customer_id` (`customer_id`),
  KEY `inspected_by` (`inspected_by`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_returns_source` (`source_type`),
  KEY `idx_returns_po` (`purchase_order_id`),
  KEY `idx_returns_warehouse` (`warehouse_id`),
  CONSTRAINT `returns_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  CONSTRAINT `returns_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `returns_ibfk_3` FOREIGN KEY (`inspected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `returns_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `returns`
--

LOCK TABLES `returns` WRITE;
/*!40000 ALTER TABLE `returns` DISABLE KEYS */;
/*!40000 ALTER TABLE `returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=723 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (355,1,80,'2026-09-23 16:46:57'),(356,2,80,'2026-09-23 16:46:57'),(357,1,79,'2026-09-23 16:46:57'),(358,2,79,'2026-09-23 16:46:57'),(359,1,83,'2026-09-23 16:46:57'),(360,2,83,'2026-09-23 16:46:57'),(361,1,82,'2026-09-23 16:46:57'),(362,2,82,'2026-09-23 16:46:57'),(363,1,81,'2026-09-23 16:46:57'),(364,2,81,'2026-09-23 16:46:57'),(365,1,78,'2026-09-23 16:46:57'),(366,2,78,'2026-09-23 16:46:57'),(367,1,88,'2026-09-23 16:46:57'),(368,2,88,'2026-09-23 16:46:57'),(369,1,89,'2026-09-23 16:46:57'),(370,2,89,'2026-09-23 16:46:57'),(371,1,90,'2026-09-23 16:46:57'),(372,2,90,'2026-09-23 16:46:57'),(373,1,87,'2026-09-23 16:46:57'),(374,2,87,'2026-09-23 16:46:57'),(375,1,150,'2026-09-23 16:46:57'),(376,2,150,'2026-09-23 16:46:57'),(377,1,149,'2026-09-23 16:46:57'),(378,2,149,'2026-09-23 16:46:57'),(379,1,147,'2026-09-23 16:46:57'),(380,2,147,'2026-09-23 16:46:57'),(381,1,148,'2026-09-23 16:46:57'),(382,2,148,'2026-09-23 16:46:57'),(383,1,146,'2026-09-23 16:46:57'),(384,2,146,'2026-09-23 16:46:57'),(385,1,142,'2026-09-23 16:46:57'),(386,2,142,'2026-09-23 16:46:57'),(387,1,141,'2026-09-23 16:46:57'),(388,2,141,'2026-09-23 16:46:57'),(389,1,145,'2026-09-23 16:46:57'),(390,2,145,'2026-09-23 16:46:57'),(391,1,144,'2026-09-23 16:46:57'),(392,2,144,'2026-09-23 16:46:57'),(393,1,143,'2026-09-23 16:46:57'),(394,2,143,'2026-09-23 16:46:57'),(395,1,138,'2026-09-23 16:46:57'),(396,2,138,'2026-09-23 16:46:57'),(397,1,140,'2026-09-23 16:46:57'),(398,2,140,'2026-09-23 16:46:57'),(399,1,139,'2026-09-23 16:46:57'),(400,2,139,'2026-09-23 16:46:57'),(401,1,137,'2026-09-23 16:46:57'),(402,2,137,'2026-09-23 16:46:57'),(403,1,170,'2026-09-23 16:46:57'),(404,2,170,'2026-09-23 16:46:57'),(405,1,169,'2026-09-23 16:46:57'),(406,2,169,'2026-09-23 16:46:57'),(407,1,165,'2026-09-23 16:46:57'),(408,2,165,'2026-09-23 16:46:57'),(409,1,166,'2026-09-23 16:46:57'),(410,2,166,'2026-09-23 16:46:57'),(411,1,167,'2026-09-23 16:46:57'),(412,2,167,'2026-09-23 16:46:57'),(413,1,168,'2026-09-23 16:46:57'),(414,2,168,'2026-09-23 16:46:57'),(415,1,164,'2026-09-23 16:46:57'),(416,2,164,'2026-09-23 16:46:57'),(417,1,55,'2026-09-23 16:46:57'),(418,2,55,'2026-09-23 16:46:57'),(419,1,54,'2026-09-23 16:46:57'),(420,2,54,'2026-09-23 16:46:57'),(421,1,56,'2026-09-23 16:46:57'),(422,2,56,'2026-09-23 16:46:57'),(423,1,53,'2026-09-23 16:46:57'),(424,2,53,'2026-09-23 16:46:57'),(425,1,12,'2026-09-23 16:46:57'),(426,2,12,'2026-09-23 16:46:57'),(427,1,14,'2026-09-23 16:46:57'),(428,2,14,'2026-09-23 16:46:57'),(429,1,13,'2026-09-23 16:46:57'),(430,2,13,'2026-09-23 16:46:57'),(431,1,11,'2026-09-23 16:46:57'),(432,2,11,'2026-09-23 16:46:57'),(433,1,127,'2026-09-23 16:46:57'),(434,2,127,'2026-09-23 16:46:57'),(435,1,128,'2026-09-23 16:46:57'),(436,2,128,'2026-09-23 16:46:57'),(437,1,126,'2026-09-23 16:46:57'),(438,2,126,'2026-09-23 16:46:57'),(439,1,21,'2026-09-23 16:46:57'),(440,2,21,'2026-09-23 16:46:57'),(441,1,23,'2026-09-23 16:46:57'),(442,2,23,'2026-09-23 16:46:57'),(443,1,22,'2026-09-23 16:46:57'),(444,2,22,'2026-09-23 16:46:57'),(445,1,20,'2026-09-23 16:46:57'),(446,2,20,'2026-09-23 16:46:57'),(447,1,92,'2026-09-23 16:46:57'),(448,2,92,'2026-09-23 16:46:57'),(449,1,93,'2026-09-23 16:46:57'),(450,2,93,'2026-09-23 16:46:57'),(451,1,91,'2026-09-23 16:46:57'),(452,2,91,'2026-09-23 16:46:57'),(453,1,8,'2026-09-23 16:46:57'),(454,2,8,'2026-09-23 16:46:57'),(455,1,10,'2026-09-23 16:46:57'),(456,2,10,'2026-09-23 16:46:57'),(457,1,9,'2026-09-23 16:46:57'),(458,2,9,'2026-09-23 16:46:57'),(459,1,7,'2026-09-23 16:46:57'),(460,2,7,'2026-09-23 16:46:57'),(461,1,1,'2026-09-23 16:46:57'),(462,2,1,'2026-09-23 16:46:57'),(463,1,52,'2026-09-23 16:46:57'),(464,2,52,'2026-09-23 16:46:57'),(465,1,62,'2026-09-23 16:46:57'),(466,2,62,'2026-09-23 16:46:57'),(467,1,64,'2026-09-23 16:46:57'),(468,2,64,'2026-09-23 16:46:57'),(469,1,63,'2026-09-23 16:46:57'),(470,2,63,'2026-09-23 16:46:57'),(471,1,61,'2026-09-23 16:46:57'),(472,2,61,'2026-09-23 16:46:57'),(473,1,44,'2026-09-23 16:46:57'),(474,2,44,'2026-09-23 16:46:57'),(475,1,45,'2026-09-23 16:46:57'),(476,2,45,'2026-09-23 16:46:57'),(477,1,46,'2026-09-23 16:46:57'),(478,2,46,'2026-09-23 16:46:57'),(479,1,43,'2026-09-23 16:46:57'),(480,2,43,'2026-09-23 16:46:57'),(481,1,175,'2026-09-23 16:46:57'),(482,2,175,'2026-09-23 16:46:57'),(483,1,177,'2026-09-23 16:46:57'),(484,2,177,'2026-09-23 16:46:57'),(485,1,176,'2026-09-23 16:46:57'),(486,2,176,'2026-09-23 16:46:57'),(487,1,174,'2026-09-23 16:46:57'),(488,2,174,'2026-09-23 16:46:57'),(489,1,172,'2026-09-23 16:46:57'),(490,2,172,'2026-09-23 16:46:57'),(491,1,173,'2026-09-23 16:46:57'),(492,2,173,'2026-09-23 16:46:57'),(493,1,171,'2026-09-23 16:46:57'),(494,2,171,'2026-09-23 16:46:57'),(495,1,58,'2026-09-23 16:46:57'),(496,2,58,'2026-09-23 16:46:57'),(497,1,59,'2026-09-23 16:46:57'),(498,2,59,'2026-09-23 16:46:57'),(499,1,57,'2026-09-23 16:46:57'),(500,2,57,'2026-09-23 16:46:57'),(501,1,122,'2026-09-23 16:46:57'),(502,2,122,'2026-09-23 16:46:57'),(503,1,123,'2026-09-23 16:46:57'),(504,2,123,'2026-09-23 16:46:57'),(505,1,121,'2026-09-23 16:46:57'),(506,2,121,'2026-09-23 16:46:57'),(507,1,2,'2026-09-23 16:46:57'),(508,2,2,'2026-09-23 16:46:57'),(509,1,39,'2026-09-23 16:46:57'),(510,2,39,'2026-09-23 16:46:57'),(511,1,41,'2026-09-23 16:46:57'),(512,2,41,'2026-09-23 16:46:57'),(513,1,40,'2026-09-23 16:46:57'),(514,2,40,'2026-09-23 16:46:57'),(515,1,42,'2026-09-23 16:46:57'),(516,2,42,'2026-09-23 16:46:57'),(517,1,38,'2026-09-23 16:46:57'),(518,2,38,'2026-09-23 16:46:57'),(519,1,19,'2026-09-23 16:46:57'),(520,2,19,'2026-09-23 16:46:57'),(521,1,16,'2026-09-23 16:46:57'),(522,2,16,'2026-09-23 16:46:57'),(523,1,18,'2026-09-23 16:46:57'),(524,2,18,'2026-09-23 16:46:57'),(525,1,17,'2026-09-23 16:46:57'),(526,2,17,'2026-09-23 16:46:57'),(527,1,15,'2026-09-23 16:46:57'),(528,2,15,'2026-09-23 16:46:57'),(529,1,75,'2026-09-23 16:46:57'),(530,2,75,'2026-09-23 16:46:57'),(531,1,76,'2026-09-23 16:46:57'),(532,2,76,'2026-09-23 16:46:57'),(533,1,77,'2026-09-23 16:46:57'),(534,2,77,'2026-09-23 16:46:57'),(535,1,74,'2026-09-23 16:46:57'),(536,2,74,'2026-09-23 16:46:57'),(537,1,51,'2026-09-23 16:46:57'),(538,2,51,'2026-09-23 16:46:57'),(539,1,48,'2026-09-23 16:46:57'),(540,2,48,'2026-09-23 16:46:57'),(541,1,50,'2026-09-23 16:46:57'),(542,2,50,'2026-09-23 16:46:57'),(543,1,49,'2026-09-23 16:46:57'),(544,2,49,'2026-09-23 16:46:57'),(545,1,47,'2026-09-23 16:46:57'),(546,2,47,'2026-09-23 16:46:57'),(547,1,152,'2026-09-23 16:46:57'),(548,2,152,'2026-09-23 16:46:57'),(549,1,153,'2026-09-23 16:46:57'),(550,2,153,'2026-09-23 16:46:57'),(551,1,151,'2026-09-23 16:46:57'),(552,2,151,'2026-09-23 16:46:57'),(553,1,134,'2026-09-23 16:46:57'),(554,2,134,'2026-09-23 16:46:57'),(555,1,135,'2026-09-23 16:46:57'),(556,2,135,'2026-09-23 16:46:57'),(557,1,136,'2026-09-23 16:46:57'),(558,2,136,'2026-09-23 16:46:57'),(559,1,133,'2026-09-23 16:46:57'),(560,2,133,'2026-09-23 16:46:57'),(561,1,97,'2026-09-23 16:46:57'),(562,2,97,'2026-09-23 16:46:57'),(563,1,96,'2026-09-23 16:46:57'),(564,2,96,'2026-09-23 16:46:57'),(565,1,95,'2026-09-23 16:46:57'),(566,2,95,'2026-09-23 16:46:57'),(567,1,94,'2026-09-23 16:46:57'),(568,2,94,'2026-09-23 16:46:57'),(569,1,159,'2026-09-23 16:46:57'),(570,2,159,'2026-09-23 16:46:57'),(571,1,161,'2026-09-23 16:46:57'),(572,2,161,'2026-09-23 16:46:57'),(573,1,160,'2026-09-23 16:46:57'),(574,2,160,'2026-09-23 16:46:57'),(575,1,158,'2026-09-23 16:46:57'),(576,2,158,'2026-09-23 16:46:57'),(577,1,125,'2026-09-23 16:46:57'),(578,2,125,'2026-09-23 16:46:57'),(579,1,124,'2026-09-23 16:46:57'),(580,2,124,'2026-09-23 16:46:57'),(581,1,105,'2026-09-23 16:46:57'),(582,2,105,'2026-09-23 16:46:57'),(583,1,106,'2026-09-23 16:46:57'),(584,2,106,'2026-09-23 16:46:57'),(585,1,104,'2026-09-23 16:46:57'),(586,2,104,'2026-09-23 16:46:57'),(587,1,4,'2026-09-23 16:46:57'),(588,2,4,'2026-09-23 16:46:57'),(589,1,6,'2026-09-23 16:46:57'),(590,2,6,'2026-09-23 16:46:57'),(591,1,5,'2026-09-23 16:46:57'),(592,2,5,'2026-09-23 16:46:57'),(593,1,3,'2026-09-23 16:46:57'),(594,2,3,'2026-09-23 16:46:57'),(595,1,163,'2026-09-23 16:46:57'),(596,2,163,'2026-09-23 16:46:57'),(597,1,162,'2026-09-23 16:46:57'),(598,2,162,'2026-09-23 16:46:57'),(599,1,110,'2026-09-23 16:46:57'),(600,2,110,'2026-09-23 16:46:57'),(601,1,113,'2026-09-23 16:46:57'),(602,2,113,'2026-09-23 16:46:57'),(603,1,112,'2026-09-23 16:46:57'),(604,2,112,'2026-09-23 16:46:57'),(605,1,111,'2026-09-23 16:46:57'),(606,2,111,'2026-09-23 16:46:57'),(607,1,114,'2026-09-23 16:46:57'),(608,2,114,'2026-09-23 16:46:57'),(609,1,118,'2026-09-23 16:46:57'),(610,2,118,'2026-09-23 16:46:57'),(611,1,116,'2026-09-23 16:46:57'),(612,2,116,'2026-09-23 16:46:57'),(613,1,117,'2026-09-23 16:46:57'),(614,2,117,'2026-09-23 16:46:57'),(615,1,115,'2026-09-23 16:46:57'),(616,2,115,'2026-09-23 16:46:57'),(617,1,103,'2026-09-23 16:46:57'),(618,2,103,'2026-09-23 16:46:57'),(619,1,102,'2026-09-23 16:46:57'),(620,2,102,'2026-09-23 16:46:57'),(621,1,101,'2026-09-23 16:46:57'),(622,2,101,'2026-09-23 16:46:57'),(623,1,130,'2026-09-23 16:46:57'),(624,2,130,'2026-09-23 16:46:57'),(625,1,132,'2026-09-23 16:46:57'),(626,2,132,'2026-09-23 16:46:57'),(627,1,131,'2026-09-23 16:46:57'),(628,2,131,'2026-09-23 16:46:57'),(629,1,129,'2026-09-23 16:46:57'),(630,2,129,'2026-09-23 16:46:57'),(631,1,120,'2026-09-23 16:46:57'),(632,2,120,'2026-09-23 16:46:57'),(633,1,119,'2026-09-23 16:46:57'),(634,2,119,'2026-09-23 16:46:57'),(635,1,155,'2026-09-23 16:46:57'),(636,2,155,'2026-09-23 16:46:57'),(637,1,157,'2026-09-23 16:46:57'),(638,2,157,'2026-09-23 16:46:57'),(639,1,156,'2026-09-23 16:46:57'),(640,2,156,'2026-09-23 16:46:57'),(641,1,154,'2026-09-23 16:46:57'),(642,2,154,'2026-09-23 16:46:57'),(643,1,30,'2026-09-23 16:46:57'),(644,2,30,'2026-09-23 16:46:57'),(645,1,32,'2026-09-23 16:46:57'),(646,2,32,'2026-09-23 16:46:57'),(647,1,31,'2026-09-23 16:46:57'),(648,2,31,'2026-09-23 16:46:57'),(649,1,29,'2026-09-23 16:46:57'),(650,2,29,'2026-09-23 16:46:57'),(651,1,34,'2026-09-23 16:46:57'),(652,2,34,'2026-09-23 16:46:57'),(653,1,36,'2026-09-23 16:46:57'),(654,2,36,'2026-09-23 16:46:57'),(655,1,35,'2026-09-23 16:46:57'),(656,2,35,'2026-09-23 16:46:57'),(657,1,37,'2026-09-23 16:46:57'),(658,2,37,'2026-09-23 16:46:57'),(659,1,33,'2026-09-23 16:46:57'),(660,2,33,'2026-09-23 16:46:57'),(661,1,66,'2026-09-23 16:46:57'),(662,2,66,'2026-09-23 16:46:57'),(663,1,65,'2026-09-23 16:46:57'),(664,2,65,'2026-09-23 16:46:57'),(665,1,60,'2026-09-23 16:46:57'),(666,2,60,'2026-09-23 16:46:57'),(667,1,25,'2026-09-23 16:46:57'),(668,2,25,'2026-09-23 16:46:57'),(669,1,27,'2026-09-23 16:46:57'),(670,2,27,'2026-09-23 16:46:57'),(671,1,26,'2026-09-23 16:46:57'),(672,2,26,'2026-09-23 16:46:57'),(673,1,28,'2026-09-23 16:46:57'),(674,2,28,'2026-09-23 16:46:57'),(675,1,24,'2026-09-23 16:46:57'),(676,2,24,'2026-09-23 16:46:57'),(677,1,108,'2026-09-23 16:46:57'),(678,2,108,'2026-09-23 16:46:57'),(679,1,109,'2026-09-23 16:46:57'),(680,2,109,'2026-09-23 16:46:57'),(681,1,107,'2026-09-23 16:46:57'),(682,2,107,'2026-09-23 16:46:57'),(683,1,85,'2026-09-23 16:46:57'),(684,2,85,'2026-09-23 16:46:57'),(685,1,86,'2026-09-23 16:46:57'),(686,2,86,'2026-09-23 16:46:57'),(687,1,84,'2026-09-23 16:46:57'),(688,2,84,'2026-09-23 16:46:57'),(689,1,71,'2026-09-23 16:46:57'),(690,2,71,'2026-09-23 16:46:57'),(691,1,73,'2026-09-23 16:46:57'),(692,2,73,'2026-09-23 16:46:57'),(693,1,72,'2026-09-23 16:46:57'),(694,2,72,'2026-09-23 16:46:57'),(695,1,70,'2026-09-23 16:46:57'),(696,2,70,'2026-09-23 16:46:57'),(697,1,99,'2026-09-23 16:46:57'),(698,2,99,'2026-09-23 16:46:57'),(699,1,100,'2026-09-23 16:46:57'),(700,2,100,'2026-09-23 16:46:57'),(701,1,98,'2026-09-23 16:46:57'),(702,2,98,'2026-09-23 16:46:57'),(703,1,68,'2026-09-23 16:46:57'),(704,2,68,'2026-09-23 16:46:57'),(705,1,69,'2026-09-23 16:46:57'),(706,2,69,'2026-09-23 16:46:57'),(707,1,67,'2026-09-23 16:46:57'),(708,2,67,'2026-09-23 16:46:57'),(709,3,53,'2026-11-08 01:56:35'),(710,3,54,'2026-11-08 01:56:35'),(711,3,55,'2026-11-08 01:56:35'),(712,3,56,'2026-11-08 01:56:35'),(713,3,57,'2026-11-08 01:56:35'),(714,3,58,'2026-11-08 01:56:35'),(715,3,59,'2026-11-08 01:56:35'),(716,4,53,'2026-11-08 01:56:35'),(717,4,54,'2026-11-08 01:56:35'),(718,4,55,'2026-11-08 01:56:35'),(719,4,56,'2026-11-08 01:56:35'),(720,4,57,'2026-11-08 01:56:35'),(721,4,58,'2026-11-08 01:56:35'),(722,4,59,'2026-11-08 01:56:35');
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_system` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Super Admin','super-admin','Full system access and user administration',1,'2026-09-23 16:46:57','2026-09-23 16:51:15',0),(2,'Admin','admin','Business administration and managerial approvals',1,'2026-09-23 16:46:57','2026-09-23 16:51:15',0),(3,'Production Manager','production-manager','Production planning and batch tracking',1,'2026-09-23 16:46:57','2026-09-23 16:51:15',0),(4,'Production Staff','production-staff','Floor operations and cutting entries',1,'2026-09-23 16:46:57','2026-09-23 16:51:15',0),(5,'Warehouse Manager','warehouse-manager','Warehouse receiving and allocations',1,'2026-09-23 16:46:57','2026-09-23 16:51:15',0),(6,'Warehouse Staff','warehouse-staff','Picking, packing, and dispatch',1,'2026-09-23 16:46:57','2026-09-23 16:51:15',0),(7,'Shop Manager','shop-manager','Store inventory and counter sales',1,'2026-09-23 16:46:57','2026-09-23 16:51:15',0),(8,'Billing Staff','billing-staff','POS retail checkout and scanning',1,'2026-09-23 16:46:57','2026-09-23 16:51:15',0),(9,'Accountant','accountant','Financial auditing and ledgers',1,'2026-09-23 16:46:57','2026-09-23 16:51:15',0),(10,'Report Viewer','report-viewer','Read-only reporting',1,'2026-09-23 16:46:57','2026-09-23 16:51:15',0);
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roll_damage_reasons`
--

DROP TABLE IF EXISTS `roll_damage_reasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roll_damage_reasons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reason` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_roll_damage_reasons_reason` (`reason`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roll_damage_reasons`
--

LOCK TABLES `roll_damage_reasons` WRITE;
/*!40000 ALTER TABLE `roll_damage_reasons` DISABLE KEYS */;
/*!40000 ALTER TABLE `roll_damage_reasons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roll_damages`
--

DROP TABLE IF EXISTS `roll_damages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roll_damages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
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
  `status` enum('LOGGED','INVESTIGATING','CLAIMED','SETTLED','REJECTED','CLOSED','CANCELLED') NOT NULL DEFAULT 'LOGGED',
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_roll_damages_number` (`damage_number`),
  UNIQUE KEY `uniq_roll_damages_qr_code` (`qr_code`),
  KEY `idx_roll_damages_seller` (`seller_id`),
  KEY `idx_roll_damages_roll` (`seller_roll_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roll_damages`
--

LOCK TABLES `roll_damages` WRITE;
/*!40000 ALTER TABLE `roll_damages` DISABLE KEYS */;
INSERT INTO `roll_damages` VALUES (2,'DMG-PRD-2026-000001-ID1','PRODUCT',NULL,NULL,1,3,'MENS - SHIRT - FULL SLEEVE','Size: S Color: RED','MSF112601',1,0.25,700.00,'Fabric Tear on Sleeve','Found during QC packing','https://example.com/photo.jpg','LOGGED','NONE',0.00,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-08 23:49:14','2026-11-08 23:49:14');
/*!40000 ALTER TABLE `roll_damages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_items`
--

DROP TABLE IF EXISTS `sale_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sale_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `cgst_amount` decimal(10,2) DEFAULT 0.00,
  `sgst_amount` decimal(10,2) DEFAULT 0.00,
  `igst_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL,
  `scanned_qr_codes` text DEFAULT NULL,
  `mrp` decimal(10,2) NOT NULL,
  `gst_percentage` decimal(5,2) DEFAULT 0.00,
  `qr_code` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_items`
--

LOCK TABLES `sale_items` WRITE;
/*!40000 ALTER TABLE `sale_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `sale_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `sale_type` enum('SHOP','WEBSITE','WHATSAPP') NOT NULL,
  `shop_id` int(10) unsigned DEFAULT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `gst_percentage` decimal(5,2) NOT NULL DEFAULT 5.00,
  `gst_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cgst_amount` decimal(10,2) DEFAULT 0.00,
  `sgst_amount` decimal(10,2) DEFAULT 0.00,
  `igst_amount` decimal(10,2) DEFAULT 0.00,
  `shipping_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_mode` varchar(50) DEFAULT 'CASH',
  `payment_status` enum('PENDING','PAID','PARTIAL','REFUNDED') DEFAULT 'PENDING',
  `status` enum('DRAFT','CONFIRMED','PAID','PACKED','SHIPPED','DELIVERED','CANCELLED','RETURNED') DEFAULT 'DRAFT',
  `sales_channel` varchar(100) DEFAULT NULL,
  `billing_staff_id` int(10) unsigned DEFAULT NULL,
  `biller_id` int(10) unsigned DEFAULT NULL,
  `biller_name` varchar(200) DEFAULT NULL,
  `employee_name` varchar(200) DEFAULT NULL,
  `employee_code` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `taxable_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_type` enum('PERCENTAGE','FIXED') DEFAULT 'FIXED',
  `discount_value` decimal(10,2) DEFAULT 0.00,
  `round_off` decimal(5,2) DEFAULT 0.00,
  `change_amount` decimal(10,2) DEFAULT 0.00,
  `payment_method` enum('CASH','UPI','CARD','SPLIT','CREDIT') DEFAULT 'CASH',
  `customer_name` varchar(150) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `created_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `idx_sale_type` (`sale_type`),
  KEY `idx_sale_shop` (`shop_id`),
  KEY `idx_sale_date` (`created_at`),
  KEY `idx_sale_status` (`status`),
  KEY `customer_id` (`customer_id`),
  KEY `billing_staff_id` (`billing_staff_id`),
  CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_ibfk_3` FOREIGN KEY (`billing_staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_channels`
--

DROP TABLE IF EXISTS `sales_channels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_channels`
--

LOCK TABLES `sales_channels` WRITE;
/*!40000 ALTER TABLE `sales_channels` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_channels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_roll_deleted_labels`
--

DROP TABLE IF EXISTS `seller_roll_deleted_labels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seller_roll_deleted_labels` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `seller_roll_id` int(10) unsigned NOT NULL,
  `sequence` int(10) unsigned NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_seller_roll_deleted_sequence` (`seller_roll_id`,`sequence`),
  CONSTRAINT `seller_roll_deleted_labels_ibfk_1` FOREIGN KEY (`seller_roll_id`) REFERENCES `seller_rolls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_roll_deleted_labels`
--

LOCK TABLES `seller_roll_deleted_labels` WRITE;
/*!40000 ALTER TABLE `seller_roll_deleted_labels` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_roll_deleted_labels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_rolls`
--

DROP TABLE IF EXISTS `seller_rolls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seller_rolls` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
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
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_seller_rolls_seller` (`seller_id`),
  CONSTRAINT `seller_rolls_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_rolls`
--

LOCK TABLES `seller_rolls` WRITE;
/*!40000 ALTER TABLE `seller_rolls` DISABLE KEYS */;
INSERT INTO `seller_rolls` VALUES (1,1,'ROLL #1','RED','RED-PANDA',1,10,30.00,50000.00,'100% COMBED COTTON 30S SINGLE JERSEY (160 GSM)','https://res.cloudinary.com/cohbobmx/image/upload/v1794155951/grm_inventory/sellers/rolls/ptagxrzs7jj3m9dn3isz.png',1,10,'FB112601','FB112610','FB112601-FB112610',NULL,'2026-11-08 22:09:46','2026-11-08 22:09:46'),(2,1,'ROLL #2','BLUE','BUTTERFLY',1,20,40.00,60000.00,'100% COMBED COTTON 30S SINGLE JERSEY (160 GSM)','https://res.cloudinary.com/cohbobmx/image/upload/v1794155959/grm_inventory/sellers/rolls/wl7lsi0ygymyczr7tfgy.png',11,30,'FB112611','FB112630','FB112611-FB112630',NULL,'2026-11-08 22:09:46','2026-11-08 22:09:46'),(3,1,'ROLL #3','NAVY BLUE','BEE',1,10,20.00,65000.00,'100% COMBED COTTON 30S SINGLE JERSEY (160 GSM)','https://res.cloudinary.com/cohbobmx/image/upload/v1794155975/grm_inventory/sellers/rolls/vsnji32s7dcuevgjwso7.png',31,40,'FB112631','FB112640','FB112631-FB112640',NULL,'2026-11-08 22:09:46','2026-11-08 22:09:46'),(4,1,'ROLL #1','YELLOW','YELLOW1',1,10,200.00,70000.00,'100% COTTON 24S SINGLE JERSEY (180 GSM)','https://res.cloudinary.com/cohbobmx/image/upload/v1794243547/grm_inventory/sellers/rolls/f0pcjtm701wxguns37dl.png',41,50,'FB112641','FB112650','FB112641-FB112650',NULL,'2026-11-09 22:29:07','2026-11-09 22:29:07'),(5,1,'ROLL #1','NAVY','NAVY123',1,30,300.00,75000.00,'95% COTTON 5% SPANDEX/LYCRA (190 GSM)',NULL,51,80,'FB112651','FB112680','FB112651-FB112680',NULL,'2026-11-09 22:43:47','2026-11-09 22:43:47');
/*!40000 ALTER TABLE `seller_rolls` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sellers`
--

DROP TABLE IF EXISTS `sellers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sellers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
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
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `gstin` varchar(20) DEFAULT NULL,
  `company_name` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sellers`
--

LOCK TABLES `sellers` WRITE;
/*!40000 ALTER TABLE `sellers` DISABLE KEYS */;
INSERT INTO `sellers` VALUES (1,'ABI','GRM','27AAACR5055K1Z7','9789660115','abiprasath6@gmail.com','TIRUUPUR','TIRUPPUR','TAMIL NADU','400',NULL,NULL,NULL,80,0,0,0,590.00,320000.00,NULL,'MATERIAL_SUPPLIER',1,'2026-11-08 22:04:01','2026-11-09 22:43:47',NULL,NULL);
/*!40000 ALTER TABLE `sellers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shop_damages`
--

DROP TABLE IF EXISTS `shop_damages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_damages` (
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
  `status` enum('LOGGED','INSPECTED','SALVAGED','WRITTEN_OFF','REPAIRED','RETURN_TO_WH','REWORK') DEFAULT 'LOGGED',
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shop_damages`
--

LOCK TABLES `shop_damages` WRITE;
/*!40000 ALTER TABLE `shop_damages` DISABLE KEYS */;
INSERT INTO `shop_damages` VALUES (1,'SH-DMG-20261109-0001',1,4,'MSF112655','INWARD_TRANSIT',2,2,'TRANSIT_DAMAGE',1,700.00,700.00,700.00,'Transit / Inward Verification Damage','Quarantined during Shop Inward for Allocation #ALC-20261109-0002 (Damaged Piece Tags: MSF112655)',NULL,'LOGGED',NULL,1,NULL,'2026-11-09 21:26:20','2026-11-09 21:26:20',NULL);
/*!40000 ALTER TABLE `shop_damages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shops`
--

DROP TABLE IF EXISTS `shops`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shops` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
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
  CONSTRAINT `shops_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shops`
--

LOCK TABLES `shops` WRITE;
/*!40000 ALTER TABLE `shops` DISABLE KEYS */;
INSERT INTO `shops` VALUES (1,'GRM Retail Flagship Store','SH-01','Main Bazaar Road, Tirupur, Tamil Nadu','9876543210',NULL,'Store Manager','33AAAAA0000A1Z5',1,'2026-09-23 16:46:57','2026-09-23 16:46:57');
/*!40000 ALTER TABLE `shops` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sizes`
--

DROP TABLE IF EXISTS `sizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sizes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `code` varchar(20) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sizes`
--

LOCK TABLES `sizes` WRITE;
/*!40000 ALTER TABLE `sizes` DISABLE KEYS */;
INSERT INTO `sizes` VALUES (1,'S','S',1,1,'2026-09-23 17:19:59','2026-09-23 17:19:59'),(2,'M','M',2,1,'2026-09-23 17:19:59','2026-09-23 17:19:59'),(3,'L','L',3,1,'2026-09-23 17:19:59','2026-09-23 17:19:59'),(4,'XL','XL',4,1,'2026-09-23 17:19:59','2026-09-23 17:19:59'),(5,'XXL','XXL',5,1,'2026-09-23 17:19:59','2026-09-23 17:19:59'),(6,'XS','XS',0,1,'2026-11-08 23:11:24','2026-11-08 23:11:24');
/*!40000 ALTER TABLE `sizes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stitching_batches`
--

DROP TABLE IF EXISTS `stitching_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stitching_batches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`id`),
  KEY `cutting_batch_id` (`cutting_batch_id`),
  KEY `program_id` (`program_id`),
  KEY `worker_id` (`worker_id`),
  CONSTRAINT `stitching_batches_ibfk_1` FOREIGN KEY (`cutting_batch_id`) REFERENCES `cutting_batches` (`id`),
  CONSTRAINT `stitching_batches_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `production_programs` (`id`),
  CONSTRAINT `stitching_batches_ibfk_3` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stitching_batches`
--

LOCK TABLES `stitching_batches` WRITE;
/*!40000 ALTER TABLE `stitching_batches` DISABLE KEYS */;
INSERT INTO `stitching_batches` VALUES (1,'PROGRAM STITCHING','STCH-20261108-0001',1,1,4,1,1,1,110,110,2.97,0,0,NULL,NULL,'IN_PROGRESS','',1,'2026-11-08 22:41:03','2026-11-08 22:41:03',NULL,NULL,NULL,0,0,0,0,NULL,NULL),(2,'SECOND STITCHING','STCH-20261109-0002',2,2,4,1,1,1,100,100,2.00,0,0,NULL,NULL,'IN_PROGRESS','',1,'2026-11-09 15:01:29','2026-11-09 15:01:29',NULL,NULL,NULL,0,0,0,0,NULL,NULL);
/*!40000 ALTER TABLE `stitching_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stitching_items`
--

DROP TABLE IF EXISTS `stitching_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `weight_per_piece` decimal(10,3) DEFAULT 0.000,
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stitching_items`
--

LOCK TABLES `stitching_items` WRITE;
/*!40000 ALTER TABLE `stitching_items` DISABLE KEYS */;
INSERT INTO `stitching_items` VALUES (1,1,NULL,4,1,1,'MENS ÔÇ║ SHIRT ÔÇ║ FULL SLEEVE','S','RED',50,50,0.027,1.35,1000.00,700.00,0,0,0,0,0,0),(2,1,NULL,4,1,1,'MENS ÔÇ║ SHIRT ÔÇ║ FULL SLEEVE','M','RED',60,60,0.027,1.62,1000.00,700.00,0,0,0,0,0,0),(3,2,NULL,4,1,1,'MENS ÔÇ║ SHIRT ÔÇ║ FULL SLEEVE','S','NAVY BLUE',50,50,0.020,1.00,1500.00,1000.00,0,0,0,0,0,0),(4,2,NULL,4,1,1,'MENS ÔÇ║ SHIRT ÔÇ║ FULL SLEEVE','M','NAVY BLUE',50,50,0.020,1.00,1500.00,1000.00,0,0,0,0,0,0);
/*!40000 ALTER TABLE `stitching_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stitching_wastage_sales`
--

DROP TABLE IF EXISTS `stitching_wastage_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stitching_wastage_sales` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
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
  KEY `idx_stitching_wastage_sales_program` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stitching_wastage_sales`
--

LOCK TABLES `stitching_wastage_sales` WRITE;
/*!40000 ALTER TABLE `stitching_wastage_sales` DISABLE KEYS */;
/*!40000 ALTER TABLE `stitching_wastage_sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_adjustments`
--

DROP TABLE IF EXISTS `stock_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `adjustment_number` (`adjustment_number`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `variant_id` (`variant_id`),
  KEY `requested_by` (`requested_by`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `stock_adjustments_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `stock_adjustments_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`),
  CONSTRAINT `stock_adjustments_ibfk_3` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_adjustments_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_adjustments`
--

LOCK TABLES `stock_adjustments` WRITE;
/*!40000 ALTER TABLE `stock_adjustments` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_balances`
--

DROP TABLE IF EXISTS `stock_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_stock_balance` (`variant_id`,`location_type`,`location_id`,`stock_state`),
  KEY `idx_stock_location` (`location_type`,`location_id`),
  KEY `idx_stock_variant` (`variant_id`),
  CONSTRAINT `stock_balances_ibfk_1` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_balances`
--

LOCK TABLES `stock_balances` WRITE;
/*!40000 ALTER TABLE `stock_balances` DISABLE KEYS */;
INSERT INTO `stock_balances` VALUES (1,3,'WAREHOUSE',1,'AVAILABLE',40.00,'2026-11-08 22:50:49','2026-11-08 22:50:49','2026-11-08 22:50:49'),(2,3,'WAREHOUSE',1,'DAMAGED',5.00,'2026-11-08 22:50:49','2026-11-08 22:50:49','2026-11-08 22:50:49'),(3,4,'WAREHOUSE',1,'AVAILABLE',50.00,'2026-11-09 21:24:06','2026-11-08 22:50:49','2026-11-09 21:24:06'),(4,7,'WAREHOUSE',1,'AVAILABLE',18.00,'2026-11-09 20:47:51','2026-11-08 23:12:26','2026-11-09 20:47:51'),(6,7,'WAREHOUSE',1,'DAMAGED',5.00,'2026-11-09 13:16:39','2026-11-09 13:16:39','2026-11-09 13:16:39'),(12,6,'WAREHOUSE',1,'AVAILABLE',40.00,'2026-11-09 16:48:02','2026-11-09 16:48:02','2026-11-09 16:48:02'),(13,10,'WAREHOUSE',1,'AVAILABLE',120.00,'2026-11-09 19:38:34','2026-11-09 19:38:34','2026-11-09 19:38:34'),(26,4,'SHOP',1,'AVAILABLE',9.00,'2026-11-09 21:26:20','2026-11-09 21:26:20','2026-11-09 21:26:20'),(27,4,'SHOP',1,'DAMAGED',0.00,'2026-11-09 21:28:11','2026-11-09 21:26:20','2026-11-09 21:28:11');
/*!40000 ALTER TABLE `stock_balances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_transactions`
--

DROP TABLE IF EXISTS `stock_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `variant_id` int(10) unsigned NOT NULL,
  `location_type` enum('WAREHOUSE','SHOP','PRODUCTION_UNIT','CHANNEL','OFFICE') NOT NULL,
  `location_id` int(10) unsigned NOT NULL,
  `stock_state` enum('AVAILABLE','RESERVED','DAMAGED','RETURNED') NOT NULL,
  `transaction_type` varchar(50) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
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
  `created_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_txn_variant` (`variant_id`),
  KEY `idx_txn_location` (`location_type`,`location_id`),
  KEY `idx_txn_reference` (`reference_type`,`reference_id`),
  KEY `idx_txn_type` (`transaction_type`),
  KEY `idx_txn_date` (`created_at`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `stock_transactions_ibfk_1` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`),
  CONSTRAINT `stock_transactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_transactions`
--

LOCK TABLES `stock_transactions` WRITE;
/*!40000 ALTER TABLE `stock_transactions` DISABLE KEYS */;
INSERT INTO `stock_transactions` VALUES (1,3,'WAREHOUSE',1,'AVAILABLE','PRODUCTION_GRN',40.00,40.00,'production_batch',1,'Production Inward GRN from Batch #BAT-20261108-0001',1,'2026-11-08 22:50:49',NULL,NULL,NULL,NULL,NULL),(2,3,'WAREHOUSE',1,'DAMAGED','PRODUCTION_GRN',5.00,5.00,'warehouse_damage',1,'Damaged in transit from Batch #BAT-20261108-0001 (Case #DMG-20261108-0001)',1,'2026-11-08 22:50:49',NULL,NULL,NULL,NULL,NULL),(3,4,'WAREHOUSE',1,'AVAILABLE','PRODUCTION_GRN',60.00,60.00,'production_batch',1,'Production Inward GRN from Batch #BAT-20261108-0001',1,'2026-11-08 22:50:49',NULL,NULL,NULL,NULL,NULL),(4,7,'WAREHOUSE',1,'AVAILABLE','PURCHASE_INWARD',30.00,30.00,'THIRD_PARTY_STICKERED',1,'Third-Party Stickered QR Inward (+30 pcs) for Master Series Pack SKU SKU-CAT-MEKHA-0004-M. Tags: TTT112681',NULL,'2026-11-08 23:12:26',NULL,NULL,NULL,NULL,NULL),(5,7,'WAREHOUSE',1,'AVAILABLE','DAMAGE_OUT',-5.00,25.00,'warehouse_damage',2,'Stock damaged: DMG-20261109-0002 - Fabric Tear / Cut [Tag: TTT112681]',1,'2026-11-09 13:16:39',NULL,NULL,NULL,NULL,NULL),(6,7,'WAREHOUSE',1,'DAMAGED','DAMAGE_IN',5.00,5.00,'warehouse_damage',2,'Quarantine damaged stock: DMG-20261109-0002 [Tag: TTT112681]',1,'2026-11-09 13:16:39',NULL,NULL,NULL,NULL,NULL),(7,7,'WAREHOUSE',1,'AVAILABLE','ALLOCATION_DISPATCH',-5.00,20.00,'allocation_outwards',1,'Outward Dispatch #OUT-ALC-20261109-0001 to GRM Retail Flagship Store (SH-01)',NULL,'2026-11-09 15:08:59',NULL,NULL,NULL,NULL,NULL),(8,6,'WAREHOUSE',1,'AVAILABLE','PURCHASE_INWARD',40.00,40.00,'THIRD_PARTY_STICKERED',1,'Third-Party Stickered QR Inward (+40 pcs) for Complementary Product SKU SKU-CAT-MEKHA-0003-S. Tags: TTT112631 - TTT112651 (total 2)',NULL,'2026-11-09 16:48:02',NULL,NULL,NULL,NULL,NULL),(9,10,'WAREHOUSE',1,'AVAILABLE','PURCHASE_INWARD',120.00,120.00,'THIRD_PARTY_STICKERED',2,'Third-Party Stickered QR Inward (+120 pcs) for Master Series Pack SKU SKU-CAT-MEKHA-0005-XS. Tags: TTT1126223 - TTT1126283 (total 2)',NULL,'2026-11-09 19:38:34',NULL,NULL,NULL,NULL,NULL),(10,7,'WAREHOUSE',1,'AVAILABLE','WAREHOUSE_DIRECT_SALE',-1.00,19.00,'purchase_orders',3,'Customer Direct Purchase #DPO-20261109-0001',1,'2026-11-09 20:47:51',NULL,NULL,NULL,NULL,NULL),(11,7,'WAREHOUSE',1,'AVAILABLE','WAREHOUSE_DIRECT_SALE',-1.00,18.00,'purchase_orders',3,'Customer Direct Purchase #DPO-20261109-0001',1,'2026-11-09 20:47:51',NULL,NULL,NULL,NULL,NULL),(12,4,'WAREHOUSE',1,'AVAILABLE','ALLOCATION_DISPATCH',-1.00,59.00,'allocation_outwards',2,'Outward Dispatch #OUT-ALC-20261109-0002 to GRM Retail Flagship Store (SH-01)',NULL,'2026-11-09 21:24:06',NULL,NULL,NULL,NULL,NULL),(13,4,'WAREHOUSE',1,'AVAILABLE','ALLOCATION_DISPATCH',-1.00,58.00,'allocation_outwards',2,'Outward Dispatch #OUT-ALC-20261109-0002 to GRM Retail Flagship Store (SH-01)',NULL,'2026-11-09 21:24:06',NULL,NULL,NULL,NULL,NULL),(14,4,'WAREHOUSE',1,'AVAILABLE','ALLOCATION_DISPATCH',-1.00,57.00,'allocation_outwards',2,'Outward Dispatch #OUT-ALC-20261109-0002 to GRM Retail Flagship Store (SH-01)',NULL,'2026-11-09 21:24:06',NULL,NULL,NULL,NULL,NULL),(15,4,'WAREHOUSE',1,'AVAILABLE','ALLOCATION_DISPATCH',-1.00,56.00,'allocation_outwards',2,'Outward Dispatch #OUT-ALC-20261109-0002 to GRM Retail Flagship Store (SH-01)',NULL,'2026-11-09 21:24:06',NULL,NULL,NULL,NULL,NULL),(16,4,'WAREHOUSE',1,'AVAILABLE','ALLOCATION_DISPATCH',-1.00,55.00,'allocation_outwards',2,'Outward Dispatch #OUT-ALC-20261109-0002 to GRM Retail Flagship Store (SH-01)',NULL,'2026-11-09 21:24:06',NULL,NULL,NULL,NULL,NULL),(17,4,'WAREHOUSE',1,'AVAILABLE','ALLOCATION_DISPATCH',-1.00,54.00,'allocation_outwards',2,'Outward Dispatch #OUT-ALC-20261109-0002 to GRM Retail Flagship Store (SH-01)',NULL,'2026-11-09 21:24:06',NULL,NULL,NULL,NULL,NULL),(18,4,'WAREHOUSE',1,'AVAILABLE','ALLOCATION_DISPATCH',-1.00,53.00,'allocation_outwards',2,'Outward Dispatch #OUT-ALC-20261109-0002 to GRM Retail Flagship Store (SH-01)',NULL,'2026-11-09 21:24:06',NULL,NULL,NULL,NULL,NULL),(19,4,'WAREHOUSE',1,'AVAILABLE','ALLOCATION_DISPATCH',-1.00,52.00,'allocation_outwards',2,'Outward Dispatch #OUT-ALC-20261109-0002 to GRM Retail Flagship Store (SH-01)',NULL,'2026-11-09 21:24:06',NULL,NULL,NULL,NULL,NULL),(20,4,'WAREHOUSE',1,'AVAILABLE','ALLOCATION_DISPATCH',-1.00,51.00,'allocation_outwards',2,'Outward Dispatch #OUT-ALC-20261109-0002 to GRM Retail Flagship Store (SH-01)',NULL,'2026-11-09 21:24:06',NULL,NULL,NULL,NULL,NULL),(21,4,'WAREHOUSE',1,'AVAILABLE','ALLOCATION_DISPATCH',-1.00,50.00,'allocation_outwards',2,'Outward Dispatch #OUT-ALC-20261109-0002 to GRM Retail Flagship Store (SH-01)',NULL,'2026-11-09 21:24:06',NULL,NULL,NULL,NULL,NULL),(22,4,'SHOP',1,'AVAILABLE','SHOP_ALLOCATION_INWARD',9.00,9.00,'warehouse_allocations',2,'Allocation #ALC-20261109-0002 received into Shop Inventory',1,'2026-11-09 21:26:20',NULL,NULL,NULL,NULL,NULL),(23,4,'SHOP',1,'DAMAGED','RETURN_DAMAGED',1.00,1.00,'shop_damages',1,'Transit Damaged from Allocation #ALC-20261109-0002 (Case #SH-DMG-20261109-0001). ',1,'2026-11-09 21:26:20',NULL,NULL,NULL,NULL,NULL),(24,4,'SHOP',1,'DAMAGED','TRANSFER_OUT',-1.00,0.00,'warehouse_returns',1,'Shop Return to Warehouse #RET-20261109-0001 (DAMAGED)',1,'2026-11-09 21:28:11',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `stock_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sub_subcategories`
--

DROP TABLE IF EXISTS `sub_subcategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sub_subcategories`
--

LOCK TABLES `sub_subcategories` WRITE;
/*!40000 ALTER TABLE `sub_subcategories` DISABLE KEYS */;
INSERT INTO `sub_subcategories` VALUES (1,1,'FULL SLEEVE','FUL','full-sleeve',1,'2026-11-08 22:34:06','2026-11-08 22:34:06',NULL,NULL,NULL,0);
/*!40000 ALTER TABLE `sub_subcategories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subcategories`
--

DROP TABLE IF EXISTS `subcategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subcategories`
--

LOCK TABLES `subcategories` WRITE;
/*!40000 ALTER TABLE `subcategories` DISABLE KEYS */;
INSERT INTO `subcategories` VALUES (1,4,'SHIRT','SHI','shirt',1,0,'2026-11-08 22:33:58','2026-11-08 22:33:58',NULL,NULL,NULL);
/*!40000 ALTER TABLE `subcategories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transfer_items`
--

DROP TABLE IF EXISTS `transfer_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transfer_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `transfer_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned NOT NULL,
  `quantity` int(11) NOT NULL,
  `received_qty` int(11) DEFAULT 0,
  `damaged_qty` int(11) DEFAULT 0,
  `requested_qty` int(11) NOT NULL,
  `transferred_qty` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `transfer_id` (`transfer_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `transfer_items_ibfk_1` FOREIGN KEY (`transfer_id`) REFERENCES `warehouse_transfers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transfer_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transfer_items`
--

LOCK TABLES `transfer_items` WRITE;
/*!40000 ALTER TABLE `transfer_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `transfer_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `units`
--

DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `units` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `abbreviation` varchar(10) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `units`
--

LOCK TABLES `units` WRITE;
/*!40000 ALTER TABLE `units` DISABLE KEYS */;
INSERT INTO `units` VALUES (1,'Pieces','PCS',1,'2026-11-09 11:37:24','2026-11-09 11:37:24'),(2,'Cones','CONE',1,'2026-11-09 11:37:24','2026-11-09 11:37:24'),(3,'Packets','PKT',1,'2026-11-09 11:37:24','2026-11-09 11:37:24'),(4,'Boxes','BOX',1,'2026-11-09 11:37:24','2026-11-09 11:37:24'),(5,'Litres','LTR',1,'2026-11-09 11:37:24','2026-11-09 11:37:24'),(6,'Metres','MTR',1,'2026-11-09 11:37:24','2026-11-09 11:37:24'),(7,'Sets','SET',1,'2026-11-09 11:37:24','2026-11-09 11:37:24'),(8,'Rolls','ROLL',1,'2026-11-09 11:37:24','2026-11-09 11:37:24'),(9,'Kilograms','KG',1,'2026-11-09 11:37:24','2026-11-09 11:37:24'),(10,'Pairs','PAIR',1,'2026-11-09 11:37:24','2026-11-09 11:37:24'),(11,'Dozens','DOZEN',1,'2026-11-09 11:37:24','2026-11-09 11:37:24');
/*!40000 ALTER TABLE `units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_permission_overrides`
--

DROP TABLE IF EXISTS `user_permission_overrides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_permission_overrides` (
  `user_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_permission_overrides`
--

LOCK TABLES `user_permission_overrides` WRITE;
/*!40000 ALTER TABLE `user_permission_overrides` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_permission_overrides` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_permissions`
--

DROP TABLE IF EXISTS `user_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_permissions` (
  `user_id` int(10) unsigned NOT NULL,
  `permission_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`permission_id`),
  KEY `idx_user_perms` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_permissions`
--

LOCK TABLES `user_permissions` WRITE;
/*!40000 ALTER TABLE `user_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `production_unit_id` int(10) unsigned DEFAULT NULL,
  `warehouse_id` int(10) unsigned DEFAULT NULL,
  `shop_id` int(10) unsigned DEFAULT NULL,
  `channel_id` int(10) unsigned DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','EMP-001','admin@grm.com','$2y$10$WXeV9abEWzjWySfggVzhTOp4j7pzF7UuFNUJ1lrLbWe9NDL7Cz7UC','System Administrator','9876543210',1,'ADMINISTRATION',NULL,NULL,NULL,NULL,'ALL',1,0,'2026-11-09 22:44:15','2026-09-23 16:46:57','2026-11-09 22:44:15',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouse_allocations`
--

DROP TABLE IF EXISTS `warehouse_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_allocations`
--

LOCK TABLES `warehouse_allocations` WRITE;
/*!40000 ALTER TABLE `warehouse_allocations` DISABLE KEYS */;
INSERT INTO `warehouse_allocations` VALUES (1,'ALC-20261109-0001',1,'SHOP',1,1,NULL,NULL,NULL,'DISPATCHED','','2026-11-09 14:58:02','2026-11-09 15:08:59','2026-11-09 14:58:02'),(2,'ALC-20261109-0002',1,'SHOP',1,1,NULL,NULL,1,'RECEIVED','','2026-11-09 21:23:49','2026-11-09 21:26:20','2026-11-09 21:26:20');
/*!40000 ALTER TABLE `warehouse_allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouse_categories`
--

DROP TABLE IF EXISTS `warehouse_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_categories`
--

LOCK TABLES `warehouse_categories` WRITE;
/*!40000 ALTER TABLE `warehouse_categories` DISABLE KEYS */;
INSERT INTO `warehouse_categories` VALUES (1,'Men\'s Wear','NORMAL','MEN','mens-wear',NULL,'6109',5.00,NULL,NULL,1,1,'2026-11-08 14:54:07','2026-11-08 14:56:11'),(3,'Women\'s Wear','NORMAL','WOM','womens-wear',NULL,'6204',5.00,NULL,NULL,2,1,'2026-11-08 14:56:11','2026-11-08 14:56:11'),(4,'Kids\' Wear','NORMAL','KID','kids-wear',NULL,'6109',5.00,NULL,NULL,3,1,'2026-11-08 14:56:11','2026-11-08 14:56:11'),(5,'Fabrics & Textiles','NORMAL','FAB','fabrics-textiles',NULL,'5208',5.00,NULL,NULL,4,1,'2026-11-08 14:56:11','2026-11-08 14:56:11'),(6,'Home & Linen','NORMAL','LIN','home-linen',NULL,'6302',5.00,NULL,NULL,5,1,'2026-11-08 14:56:11','2026-11-08 14:56:11'),(7,'TEST1','NORMAL','TES','test1',NULL,NULL,5.00,NULL,NULL,6,1,'2026-11-08 23:06:04','2026-11-08 23:06:04');
/*!40000 ALTER TABLE `warehouse_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouse_damages`
--

DROP TABLE IF EXISTS `warehouse_damages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_damages`
--

LOCK TABLES `warehouse_damages` WRITE;
/*!40000 ALTER TABLE `warehouse_damages` DISABLE KEYS */;
INSERT INTO `warehouse_damages` VALUES (1,'DMG-20261108-0001',1,3,'MSF112646,MSF112647,MSF112648,MSF112649,MSF112650','PRODUCTION_INWARD',1,5,700.00,3500.00,'Transit / Inward Verification Damage','Quarantined during Inward GRN for Batch #BAT-20261108-0001 (Program: PRG-20261108-0001, Facility: Main Production Facility)',NULL,'LOGGED',1,'2026-11-08 22:50:49','2026-11-08 22:50:49',NULL,NULL,'OTHER',0.00,NULL,NULL,1,0.00,NULL,NULL,NULL),(2,'DMG-20261109-0002',1,7,'TTT112651','STORAGE_HANDLING',NULL,5,700.00,3500.00,'Fabric Tear / Cut','',NULL,'LOGGED',1,'2026-11-09 13:16:39','2026-11-09 13:16:39',NULL,NULL,'OTHER',0.00,NULL,NULL,1,0.00,NULL,NULL,NULL);
/*!40000 ALTER TABLE `warehouse_damages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouse_deleted_label_codes`
--

DROP TABLE IF EXISTS `warehouse_deleted_label_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warehouse_deleted_label_codes` (
  `code` varchar(100) NOT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`code`),
  KEY `idx_wdlc_variant` (`variant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_deleted_label_codes`
--

LOCK TABLES `warehouse_deleted_label_codes` WRITE;
/*!40000 ALTER TABLE `warehouse_deleted_label_codes` DISABLE KEYS */;
INSERT INTO `warehouse_deleted_label_codes` VALUES ('TTT1126141',5,NULL,'2026-11-09 19:27:21'),('TTT1126142',5,NULL,'2026-11-09 19:27:09'),('TTT1126163',5,1,'2026-11-09 19:18:12');
/*!40000 ALTER TABLE `warehouse_deleted_label_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouse_qr_codes`
--

DROP TABLE IF EXISTS `warehouse_qr_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_qr_codes`
--

LOCK TABLES `warehouse_qr_codes` WRITE;
/*!40000 ALTER TABLE `warehouse_qr_codes` DISABLE KEYS */;
INSERT INTO `warehouse_qr_codes` VALUES (1,'TTT112631',6,1,NULL,'2026-11-08 17:41:35','2026-11-09 11:18:02',20,'TTT112650',0,NULL),(2,'TTT112681',7,1,NULL,'2026-11-08 17:42:21','2026-11-08 17:42:26',30,'TTT1126110',0,NULL),(9,'TTT112651',6,1,NULL,'2026-11-09 11:17:12','2026-11-09 11:18:02',20,'TTT112670',1,'Additional Sequential Pack (+20 pcs)'),(10,'TTT1126111',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,0,NULL),(11,'TTT1126112',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(12,'TTT1126113',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(13,'TTT1126114',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(14,'TTT1126115',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(15,'TTT1126116',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(16,'TTT1126117',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(17,'TTT1126118',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(18,'TTT1126119',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(19,'TTT1126120',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(20,'TTT1126121',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(21,'TTT1126122',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(22,'TTT1126123',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(23,'TTT1126124',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(24,'TTT1126125',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(25,'TTT1126126',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(26,'TTT1126127',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(27,'TTT1126128',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(28,'TTT1126129',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(29,'TTT1126130',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(30,'TTT1126131',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(31,'TTT1126132',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(32,'TTT1126133',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(33,'TTT1126134',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(34,'TTT1126135',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(35,'TTT1126136',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(36,'TTT1126137',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(37,'TTT1126138',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(38,'TTT1126139',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(39,'TTT1126140',5,1,NULL,'2026-11-09 11:23:01',NULL,1,NULL,1,NULL),(45,'TTT1126163',10,1,NULL,'2026-11-09 14:07:39',NULL,60,'TTT1126222',0,NULL),(46,'TTT1126223',10,1,NULL,'2026-11-09 14:07:45','2026-11-09 14:08:34',60,'TTT1126282',1,'Additional Sequential Pack (+60 pcs, 223 to 282)'),(47,'TTT1126283',10,1,NULL,'2026-11-09 14:08:23','2026-11-09 14:08:34',60,'TTT1126342',1,'Additional Sequential Pack (+60 pcs, 283 to 342)'),(48,'RMSF112601',4,1,NULL,'2026-11-09 15:59:34',NULL,1,NULL,0,NULL);
/*!40000 ALTER TABLE `warehouse_qr_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouse_return_items`
--

DROP TABLE IF EXISTS `warehouse_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warehouse_return_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_return_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned NOT NULL,
  `returned_qty` int(11) NOT NULL,
  `good_qty` int(11) DEFAULT 0,
  `damaged_qty` int(11) DEFAULT 0,
  `defective_qty` int(11) DEFAULT 0,
  `classification` enum('GOOD','DAMAGED','DEFECTIVE','WRONG_PRODUCT','WRONG_SIZE','MISSING') DEFAULT 'GOOD',
  `scanned_qr_codes` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warehouse_return_id` (`warehouse_return_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `warehouse_return_items_ibfk_1` FOREIGN KEY (`warehouse_return_id`) REFERENCES `warehouse_returns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `warehouse_return_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_return_items`
--

LOCK TABLES `warehouse_return_items` WRITE;
/*!40000 ALTER TABLE `warehouse_return_items` DISABLE KEYS */;
INSERT INTO `warehouse_return_items` VALUES (1,1,4,1,0,1,0,'DAMAGED',NULL);
/*!40000 ALTER TABLE `warehouse_return_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouse_returns`
--

DROP TABLE IF EXISTS `warehouse_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warehouse_returns` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `return_number` varchar(50) NOT NULL,
  `source_type` enum('SHOP','WEBSITE','WHATSAPP') NOT NULL,
  `source_id` int(10) unsigned DEFAULT NULL,
  `warehouse_id` int(10) unsigned NOT NULL,
  `reason` text DEFAULT NULL,
  `inspected_by` int(10) unsigned DEFAULT NULL,
  `status` enum('RECEIVED','INSPECTING','CLASSIFIED','COMPLETED') DEFAULT 'RECEIVED',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `destination_warehouse_id` int(10) unsigned NOT NULL,
  `received_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `return_number` (`return_number`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `inspected_by` (`inspected_by`),
  CONSTRAINT `warehouse_returns_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `warehouse_returns_ibfk_2` FOREIGN KEY (`inspected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_returns`
--

LOCK TABLES `warehouse_returns` WRITE;
/*!40000 ALTER TABLE `warehouse_returns` DISABLE KEYS */;
INSERT INTO `warehouse_returns` VALUES (1,'RET-20261109-0001','SHOP',1,1,'Damaged & Defective Stock Return',NULL,'RECEIVED','{\"carrier_name\":\"GRM Dedicated Logistics\",\"vehicle_no\":\"GH67HBJ\",\"driver_name\":\"kjh\",\"driver_phone\":\"\",\"tracking_no\":\"ihu\",\"carton_count\":1,\"dispatch_date\":\"2026-11-09 21:28:11\",\"remarks\":\"jhgjl\"}','2026-11-09 21:28:11','2026-11-09 21:28:11',1,NULL);
/*!40000 ALTER TABLE `warehouse_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouse_sub_subcategories`
--

DROP TABLE IF EXISTS `warehouse_sub_subcategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_sub_subcategories`
--

LOCK TABLES `warehouse_sub_subcategories` WRITE;
/*!40000 ALTER TABLE `warehouse_sub_subcategories` DISABLE KEYS */;
INSERT INTO `warehouse_sub_subcategories` VALUES (1,1,'TEST3','TES','test3',NULL,NULL,NULL,1,1,'2026-11-08 23:06:04','2026-11-08 23:06:04');
/*!40000 ALTER TABLE `warehouse_sub_subcategories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouse_subcategories`
--

DROP TABLE IF EXISTS `warehouse_subcategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_subcategories`
--

LOCK TABLES `warehouse_subcategories` WRITE;
/*!40000 ALTER TABLE `warehouse_subcategories` DISABLE KEYS */;
INSERT INTO `warehouse_subcategories` VALUES (1,7,'TEST2','TES','test2',NULL,NULL,NULL,1,1,'2026-11-08 23:06:04','2026-11-08 23:06:04');
/*!40000 ALTER TABLE `warehouse_subcategories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouse_suppliers`
--

DROP TABLE IF EXISTS `warehouse_suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warehouse_suppliers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `company` varchar(200) DEFAULT NULL,
  `brand_id` int(10) unsigned DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `gst_number` varchar(20) DEFAULT NULL,
  `seller_type` varchar(50) DEFAULT 'PRODUCT_SELLER',
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `payment_terms` varchar(100) DEFAULT NULL,
  `material_type` varchar(100) DEFAULT NULL,
  `document_url` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_warehouse_suppliers_brand` (`brand_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_suppliers`
--

LOCK TABLES `warehouse_suppliers` WRITE;
/*!40000 ALTER TABLE `warehouse_suppliers` DISABLE KEYS */;
INSERT INTO `warehouse_suppliers` VALUES (1,'MEKHA','GRM\'S',1,'9500903648','mekharamesh3@gmail.com','27AAACR5055K1Z7','PRODUCT_SELLER','erode','erode','Tamil Nadu',NULL,'Third-Party Garments & Products','https://res.cloudinary.com/cohbobmx/image/upload/v1794159475/grm_inventory/bills/ym4okvvwe3nir6qh82ue.png',NULL,1,'2026-11-08 23:07:56','2026-11-09 19:35:54');
/*!40000 ALTER TABLE `warehouse_suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouse_transfers`
--

DROP TABLE IF EXISTS `warehouse_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warehouse_transfers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `transfer_number` varchar(50) NOT NULL,
  `source_type` enum('WAREHOUSE','SHOP') NOT NULL,
  `source_id` int(10) unsigned NOT NULL,
  `destination_type` enum('WAREHOUSE','SHOP') NOT NULL,
  `destination_id` int(10) unsigned NOT NULL,
  `requested_by` int(10) unsigned DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `status` enum('REQUESTED','APPROVED','IN_TRANSIT','RECEIVED','COMPLETED') DEFAULT 'REQUESTED',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approved_at` datetime DEFAULT NULL,
  `source_warehouse_id` int(10) unsigned DEFAULT NULL,
  `dest_warehouse_id` int(10) unsigned DEFAULT NULL,
  `vehicle_number` varchar(50) DEFAULT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transfer_number` (`transfer_number`),
  KEY `requested_by` (`requested_by`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `warehouse_transfers_ibfk_1` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warehouse_transfers_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_transfers`
--

LOCK TABLES `warehouse_transfers` WRITE;
/*!40000 ALTER TABLE `warehouse_transfers` DISABLE KEYS */;
/*!40000 ALTER TABLE `warehouse_transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `manager_id` (`manager_id`),
  CONSTRAINT `warehouses_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouses`
--

LOCK TABLES `warehouses` WRITE;
/*!40000 ALTER TABLE `warehouses` DISABLE KEYS */;
INSERT INTO `warehouses` VALUES (1,'Central Distribution Warehouse','WH-01','Warehouse Complex, Ring Road, Tirupur, Tamil Nadu','+91 98765 00002',NULL,NULL,NULL,1,'2026-09-23 16:46:57','2026-09-23 16:46:57');
/*!40000 ALTER TABLE `warehouses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wastage_sales`
--

DROP TABLE IF EXISTS `wastage_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wastage_sales` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
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
  KEY `idx_wastage_sales_program` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wastage_sales`
--

LOCK TABLES `wastage_sales` WRITE;
/*!40000 ALTER TABLE `wastage_sales` DISABLE KEYS */;
/*!40000 ALTER TABLE `wastage_sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wh_category_attributes`
--

DROP TABLE IF EXISTS `wh_category_attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wh_category_attributes`
--

LOCK TABLES `wh_category_attributes` WRITE;
/*!40000 ALTER TABLE `wh_category_attributes` DISABLE KEYS */;
INSERT INTO `wh_category_attributes` VALUES (1,7,1,1,'SIZE','SELECT','XS,S,M,L',0,1,1,'2026-11-08 23:06:04','2026-11-08 23:06:04');
/*!40000 ALTER TABLE `wh_category_attributes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wh_category_default_products`
--

DROP TABLE IF EXISTS `wh_category_default_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wh_category_default_products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `subcategory_id` int(10) unsigned DEFAULT NULL,
  `sub_subcategory_id` int(10) unsigned DEFAULT NULL,
  `product_template_name` varchar(255) NOT NULL,
  `hsn_code` varchar(20) DEFAULT NULL,
  `default_gst_pct` decimal(5,2) NOT NULL DEFAULT 5.00,
  `default_weight_kg` decimal(8,3) NOT NULL DEFAULT 0.000,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cat_defaults` (`category_id`,`subcategory_id`,`sub_subcategory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wh_category_default_products`
--

LOCK TABLES `wh_category_default_products` WRITE;
/*!40000 ALTER TABLE `wh_category_default_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `wh_category_default_products` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-15 14:04:56
