-- Additive migration: separate QR Generations into Fabric, Raw Material,
-- Third-Party and Return areas. Safe to run on an existing database.

ALTER TABLE qr_generations
  ADD COLUMN qr_source VARCHAR(30) NOT NULL DEFAULT 'THIRD_PARTY' AFTER generation_type,
  ADD COLUMN source_purchase_id INT UNSIGNED DEFAULT NULL AFTER qr_source,
  ADD COLUMN source_purchase_item_id INT UNSIGNED DEFAULT NULL AFTER source_purchase_id,
  ADD COLUMN stock_added TINYINT(1) NOT NULL DEFAULT 0 AFTER source_purchase_item_id,
  ADD COLUMN stock_added_at DATETIME DEFAULT NULL AFTER stock_added,
  ADD COLUMN stock_added_by INT UNSIGNED DEFAULT NULL AFTER stock_added_at,
  ADD KEY idx_qrg_source (qr_source),
  ADD KEY idx_qrg_source_purchase (source_purchase_id, qr_source);

-- Existing manual/catalog batches remain visible in the Third-Party area.
UPDATE qr_generations SET qr_source = 'THIRD_PARTY' WHERE qr_source IS NULL OR qr_source = '';
