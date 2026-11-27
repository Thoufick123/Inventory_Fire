-- -------------------------------------------------------------
-- Migration: Add Damage Outward module (damage_outwards + damage_outward_items)
-- -------------------------------------------------------------

ALTER TABLE `roll_damages`
  MODIFY COLUMN `status` ENUM('LOGGED','INVESTIGATING','CLAIMED','SETTLED','REJECTED','CLOSED','CANCELLED','REPLACEMENT_RECEIVED','RETURN_INITIATED','RETURNED_TO_SELLER','REFUND_RECEIVED','OUTWARDED') NOT NULL DEFAULT 'LOGGED';

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roll_damages' AND COLUMN_NAME = 'damage_outward_id'),
  'SELECT 1',
  'ALTER TABLE `roll_damages` ADD COLUMN `damage_outward_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roll_damages' AND COLUMN_NAME = 'outward_concern_id'),
  'SELECT 1',
  'ALTER TABLE `roll_damages` ADD COLUMN `outward_concern_id` int(10) unsigned DEFAULT NULL'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

CREATE TABLE IF NOT EXISTS `damage_outwards` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `outward_number` varchar(50) NOT NULL,
  `concern_id` int(10) unsigned DEFAULT NULL,
  `destination_concern_id` int(10) unsigned NOT NULL,
  `vehicle_number` varchar(60) DEFAULT NULL,
  `driver_name` varchar(120) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `total_items` int(11) NOT NULL DEFAULT 0,
  `outward_date` date NOT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_damage_outward_number` (`outward_number`),
  KEY `idx_damage_outward_concern` (`concern_id`),
  KEY `idx_damage_outward_dest` (`destination_concern_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `damage_outward_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `damage_outward_id` int(10) unsigned NOT NULL,
  `roll_damage_id` int(10) unsigned NOT NULL,
  `damage_number` varchar(50) NOT NULL,
  `qr_code` varchar(100) DEFAULT NULL,
  `product_name` varchar(300) DEFAULT NULL,
  `damaged_quantity` int(11) NOT NULL DEFAULT 0,
  `damaged_weight` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_damage_outward_roll_damage` (`roll_damage_id`),
  KEY `idx_damage_outward_items_parent` (`damage_outward_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
