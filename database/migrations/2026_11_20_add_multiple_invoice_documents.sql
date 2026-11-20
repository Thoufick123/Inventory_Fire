-- Migration: Add Multiple Invoice Documents Support
-- Supports multiple invoice attachments per purchase with AVIF conversion

CREATE TABLE IF NOT EXISTS `production_purchase_documents` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `purchase_id` INT UNSIGNED NOT NULL,
  `document_url` VARCHAR(500) NOT NULL,
  `file_name` VARCHAR(255) NULL,
  `file_type` VARCHAR(50) NULL,
  `file_size` INT UNSIGNED NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pp_docs_purchase` (`purchase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `production_purchases` 
  ADD COLUMN IF NOT EXISTS `document_urls` TEXT NULL AFTER `document_url`;
