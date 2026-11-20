-- Migration: Modify hsn_codes to allow nullable GST rate and text description
-- Created: 2026-11-18

ALTER TABLE `hsn_codes`
  MODIFY COLUMN `description` TEXT NOT NULL,
  MODIFY COLUMN `gst_rate` DECIMAL(5,2) DEFAULT NULL;
