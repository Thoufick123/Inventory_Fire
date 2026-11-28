-- -------------------------------------------------------------
-- Migration: Add Cutting Outward module (cutting_outwards + cutting_outward_items)
-- -------------------------------------------------------------

ALTER TABLE `cutting_batches`
  MODIFY COLUMN `status` ENUM('PENDING','IN_PROGRESS','COMPLETED','HOLD','CANCELLED','OUTWARDED') DEFAULT 'PENDING';

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cutting_batches' AND COLUMN_NAME = 'cutting_outward_id'),
  'SELECT 1',
  'ALTER TABLE `cutting_batches` ADD COLUMN `cutting_outward_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cutting_batches' AND COLUMN_NAME = 'outward_concern_id'),
  'SELECT 1',
  'ALTER TABLE `cutting_batches` ADD COLUMN `outward_concern_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

CREATE TABLE IF NOT EXISTS `cutting_outwards` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `outward_number` varchar(50) NOT NULL,
  `concern_id` int(10) unsigned DEFAULT NULL,
  `destination_concern_id` int(10) unsigned NOT NULL,
  `vehicle_number` varchar(60) DEFAULT NULL,
  `driver_name` varchar(120) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `total_batches` int(11) NOT NULL DEFAULT 0,
  `total_cut_qty` int(11) NOT NULL DEFAULT 0,
  `outward_date` date NOT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_cutting_outward_number` (`outward_number`),
  KEY `idx_cutting_outward_concern` (`concern_id`),
  KEY `idx_cutting_outward_dest` (`destination_concern_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cutting_outward_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cutting_outward_id` int(10) unsigned NOT NULL,
  `cutting_batch_id` int(10) unsigned NOT NULL,
  `batch_number` varchar(50) NOT NULL,
  `batch_name` varchar(255) DEFAULT NULL,
  `program_number` varchar(50) DEFAULT NULL,
  `product_name` varchar(300) DEFAULT NULL,
  `cut_qty` int(11) NOT NULL DEFAULT 0,
  `used_weight` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_cutting_outward_batch` (`cutting_batch_id`),
  KEY `idx_cutting_outward_items_parent` (`cutting_outward_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
