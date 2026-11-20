-- Migration: Add POS Billing enhancements
-- Staff attribution, cash given by customer, change returned

ALTER TABLE `production_bills` 
    ADD COLUMN IF NOT EXISTS `staff_id` INT(10) UNSIGNED NULL AFTER `created_by`,
    ADD COLUMN IF NOT EXISTS `staff_name` VARCHAR(150) NULL AFTER `staff_id`,
    ADD COLUMN IF NOT EXISTS `cash_given` DECIMAL(12,2) DEFAULT 0.00 AFTER `notes`,
    ADD COLUMN IF NOT EXISTS `change_returned` DECIMAL(12,2) DEFAULT 0.00 AFTER `cash_given`;
