-- Add concern_id to remaining export tables for direct concern partitioning
ALTER TABLE `seller_rolls` ADD COLUMN IF NOT EXISTS `concern_id` INT NULL DEFAULT 1 AFTER `id`, ADD INDEX IF NOT EXISTS `idx_seller_rolls_concern` (`concern_id`);
ALTER TABLE `assets` ADD COLUMN IF NOT EXISTS `concern_id` INT NULL DEFAULT 1 AFTER `id`, ADD INDEX IF NOT EXISTS `idx_assets_concern` (`concern_id`);
ALTER TABLE `asset_maintenance_logs` ADD COLUMN IF NOT EXISTS `concern_id` INT NULL DEFAULT 1 AFTER `id`, ADD INDEX IF NOT EXISTS `idx_asset_maintenance_concern` (`concern_id`);
ALTER TABLE `production_warehouse_reworks` ADD COLUMN IF NOT EXISTS `concern_id` INT NULL DEFAULT 1 AFTER `id`, ADD INDEX IF NOT EXISTS `idx_pwr_concern` (`concern_id`);
