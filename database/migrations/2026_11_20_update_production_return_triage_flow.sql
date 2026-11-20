-- Migration: Update Production Returns schema for Triage Workflow
-- Supports scanning any piece/product tag, staging returns, and deciding Add to Stock vs Damage Module

ALTER TABLE `production_returns` 
    MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'PENDING_INSPECTION',
    MODIFY COLUMN `return_type` VARCHAR(50) NOT NULL DEFAULT 'REFUND',
    MODIFY COLUMN `disposition` VARCHAR(50) NOT NULL DEFAULT 'PENDING_INSPECTION';

ALTER TABLE `production_return_items` 
    MODIFY COLUMN `action_taken` VARCHAR(50) NOT NULL DEFAULT 'PENDING_INSPECTION',
    ADD COLUMN IF NOT EXISTS `damage_id` INT(10) UNSIGNED NULL DEFAULT NULL AFTER `action_taken`,
    ADD COLUMN IF NOT EXISTS `triaged_at` DATETIME NULL DEFAULT NULL AFTER `damage_id`,
    ADD COLUMN IF NOT EXISTS `triaged_by` INT(10) UNSIGNED NULL DEFAULT NULL AFTER `triaged_at`;
