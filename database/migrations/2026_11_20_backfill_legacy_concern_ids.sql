-- =========================================================================
-- Backfill Legacy Data to Default Concern (Concern ID = 1)
-- Ensures complete concern data isolation across all modules.
-- =========================================================================

UPDATE `production_purchases` SET `concern_id` = 1 WHERE `concern_id` IS NULL;
UPDATE `cutting_batches` SET `concern_id` = 1 WHERE `concern_id` IS NULL;
UPDATE `production_bills` SET `concern_id` = 1 WHERE `concern_id` IS NULL;
UPDATE `production_programs` SET `concern_id` = 1 WHERE `concern_id` IS NULL;
UPDATE `production_units` SET `concern_id` = 1 WHERE `concern_id` IS NULL;
UPDATE `qc_batches` SET `concern_id` = 1 WHERE `concern_id` IS NULL;
UPDATE `roll_damages` SET `concern_id` = 1 WHERE `concern_id` IS NULL;
UPDATE `stitching_batches` SET `concern_id` = 1 WHERE `concern_id` IS NULL;
UPDATE `stock_balances` SET `concern_id` = 1 WHERE `concern_id` IS NULL;
UPDATE `stock_transactions` SET `concern_id` = 1 WHERE `concern_id` IS NULL;
UPDATE `warehouses` SET `concern_id` = 1 WHERE `concern_id` IS NULL;
UPDATE `qr_generations` SET `concern_id` = 1 WHERE `concern_id` IS NULL;
