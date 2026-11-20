-- Remove the Production Label Management module from existing databases.
-- Destructive: label_batches, label_items, qc_batches.label_batch_id and all
-- labels.* permissions are removed. Historical audit references are purged.

SET @has_qc = (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'qc_batches'
);
SET @has_labels = (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'label_batches'
);

-- Preserve QC-to-stitching links before dropping the label foreign key.
SET @sql = IF(@has_qc = 1 AND @has_labels = 1,
    'UPDATE qc_batches qb JOIN label_batches lb ON qb.label_batch_id = lb.id SET qb.stitching_batch_id = lb.stitching_batch_id WHERE qb.stitching_batch_id IS NULL AND lb.stitching_batch_id IS NOT NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'qc_batches'
      AND CONSTRAINT_NAME = 'fk_qc_label_batch' AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);
SET @sql = IF(@fk_exists > 0,
    'ALTER TABLE qc_batches DROP FOREIGN KEY fk_qc_label_batch',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'qc_batches'
      AND INDEX_NAME = 'fk_qc_label_batch'
);
SET @sql = IF(@idx_exists > 0,
    'ALTER TABLE qc_batches DROP INDEX fk_qc_label_batch',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'qc_batches'
      AND COLUMN_NAME = 'label_batch_id'
);
SET @sql = IF(@column_exists > 0,
    'ALTER TABLE qc_batches DROP COLUMN label_batch_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

DROP TABLE IF EXISTS label_items;
DROP TABLE IF EXISTS label_batches;

DELETE FROM permissions WHERE module = 'labels';
DELETE FROM audit_logs WHERE reference_type IN ('label_batch', 'label_item');
