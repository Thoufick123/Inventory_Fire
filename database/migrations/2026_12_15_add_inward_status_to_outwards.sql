-- -------------------------------------------------------------
-- Migration: Add inward receiving status to damage_outwards and cutting_outwards
-- -------------------------------------------------------------

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'damage_outwards' AND COLUMN_NAME = 'inward_status'),
  'SELECT 1',
  'ALTER TABLE `damage_outwards` ADD COLUMN `inward_status` ENUM(''PENDING'',''RECEIVED'') NOT NULL DEFAULT ''PENDING'' AFTER `notes`'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'damage_outwards' AND COLUMN_NAME = 'inwarded_by'),
  'SELECT 1',
  'ALTER TABLE `damage_outwards` ADD COLUMN `inwarded_by` int(10) unsigned DEFAULT NULL AFTER `inward_status`'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'damage_outwards' AND COLUMN_NAME = 'inwarded_at'),
  'SELECT 1',
  'ALTER TABLE `damage_outwards` ADD COLUMN `inwarded_at` datetime DEFAULT NULL AFTER `inwarded_by`'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cutting_outwards' AND COLUMN_NAME = 'inward_status'),
  'SELECT 1',
  'ALTER TABLE `cutting_outwards` ADD COLUMN `inward_status` ENUM(''PENDING'',''RECEIVED'') NOT NULL DEFAULT ''PENDING'' AFTER `notes`'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cutting_outwards' AND COLUMN_NAME = 'inwarded_by'),
  'SELECT 1',
  'ALTER TABLE `cutting_outwards` ADD COLUMN `inwarded_by` int(10) unsigned DEFAULT NULL AFTER `inward_status`'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;

SET @schema_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cutting_outwards' AND COLUMN_NAME = 'inwarded_at'),
  'SELECT 1',
  'ALTER TABLE `cutting_outwards` ADD COLUMN `inwarded_at` datetime DEFAULT NULL AFTER `inwarded_by`'
);
PREPARE schema_stmt FROM @schema_sql;
EXECUTE schema_stmt;
DEALLOCATE PREPARE schema_stmt;
