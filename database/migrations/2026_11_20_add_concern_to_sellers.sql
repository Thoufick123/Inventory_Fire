-- Add concern_id to sellers for multi-concern isolation
ALTER TABLE `sellers` ADD COLUMN IF NOT EXISTS `concern_id` INT NULL DEFAULT 1 AFTER `id`, ADD INDEX IF NOT EXISTS `idx_sellers_concern_id` (`concern_id`);
UPDATE `sellers` SET `concern_id` = 1 WHERE `concern_id` IS NULL;
