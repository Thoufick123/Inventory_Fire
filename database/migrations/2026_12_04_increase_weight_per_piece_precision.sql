-- Increase precision of weight_per_piece to 4 decimal places to prevent rounding accumulation errors
ALTER TABLE `stitching_items` MODIFY COLUMN `weight_per_piece` decimal(10,4) DEFAULT 0.0000;
ALTER TABLE `cutting_items` MODIFY COLUMN `weight_per_piece` decimal(10,4) DEFAULT 0.0000;
