-- -------------------------------------------------------------
-- Migration: Allow NULL variant_id in third_party_mapping_items
-- (Variant IDs are generated only at QR Generation time, not during Purchases)
-- -------------------------------------------------------------

ALTER TABLE `third_party_mapping_items`
  MODIFY COLUMN `variant_id` int(10) unsigned DEFAULT NULL;
