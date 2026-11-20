-- Additive migration: Purchase item mapping -> QR generation -> GRN fulfillment.
-- Safe to run on an existing database; no table is dropped.

ALTER TABLE production_purchase_items
  ADD COLUMN qr_generation_id INT UNSIGNED DEFAULT NULL AFTER mapped_by,
  ADD COLUMN qr_batch_code VARCHAR(50) DEFAULT NULL AFTER qr_generation_id,
  ADD COLUMN grn_id INT UNSIGNED DEFAULT NULL AFTER qr_batch_code,
  ADD COLUMN grn_number VARCHAR(50) DEFAULT NULL AFTER grn_id,
  ADD KEY idx_ppi_qr_generation (qr_generation_id),
  ADD KEY idx_ppi_grn (grn_id);

ALTER TABLE production_purchases
  ADD COLUMN grn_id INT UNSIGNED DEFAULT NULL AFTER total_amount,
  ADD COLUMN grn_number VARCHAR(50) DEFAULT NULL AFTER grn_id,
  ADD KEY idx_pp_grn (grn_id);

ALTER TABLE purchase_orders
  ADD COLUMN production_purchase_id INT UNSIGNED DEFAULT NULL AFTER id,
  ADD KEY idx_po_production_purchase (production_purchase_id);

ALTER TABLE purchase_inward_items
  ADD COLUMN production_purchase_item_id INT UNSIGNED DEFAULT NULL AFTER purchase_inward_id,
  ADD KEY idx_pii_production_purchase_item (production_purchase_item_id);

ALTER TABLE products
  ADD COLUMN attributes TEXT DEFAULT NULL AFTER description;

ALTER TABLE product_variants
  ADD COLUMN attributes TEXT DEFAULT NULL AFTER image;

-- Legacy rows were flagged as mapped with a hard-coded category but without a
-- real mapping. Reset them so they appear for mapping and get QR + GRN.
UPDATE production_purchase_items
SET is_mapped = 0
WHERE is_mapped = 1 AND mapped_category_id IS NULL;
