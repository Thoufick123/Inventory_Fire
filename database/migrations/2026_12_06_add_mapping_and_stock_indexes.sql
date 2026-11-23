-- High-performance database indexing for purchases, mapping drafts, QR generations and stock balances
-- Safe and additive migration

CREATE INDEX IF NOT EXISTS idx_pp_seller_inv ON production_purchases (seller_id, invoice_number);
CREATE INDEX IF NOT EXISTS idx_pp_concern_type ON production_purchases (concern_id, purchase_type);

CREATE INDEX IF NOT EXISTS idx_ppi_purchase_type ON production_purchase_items (purchase_id, item_type);
CREATE INDEX IF NOT EXISTS idx_ppi_variant ON production_purchase_items (variant_id);
CREATE INDEX IF NOT EXISTS idx_ppi_qr ON production_purchase_items (qr_generation_id);

CREATE INDEX IF NOT EXISTS idx_tpmb_match_inv ON third_party_mapping_bills (concern_id, seller_id, invoice_number);
CREATE INDEX IF NOT EXISTS idx_tpmb_purchase_link ON third_party_mapping_bills (purchase_id);

CREATE INDEX IF NOT EXISTS idx_tpmi_purchase_item ON third_party_mapping_items (purchase_item_id);
CREATE INDEX IF NOT EXISTS idx_tpmi_variant_link ON third_party_mapping_items (variant_id);

CREATE INDEX IF NOT EXISTS idx_st_variant_loc_state ON stock_transactions (variant_id, location_type, location_id);
CREATE INDEX IF NOT EXISTS idx_sb_variant_loc_state ON stock_balances (variant_id, location_type, location_id, stock_state);

CREATE INDEX IF NOT EXISTS idx_qrg_source_mapping ON qr_generations (source_mapping_item_id);
CREATE INDEX IF NOT EXISTS idx_qrg_source_purchase ON qr_generations (source_purchase_item_id);
CREATE INDEX IF NOT EXISTS idx_qrg_stock_status ON qr_generations (stock_added);

CREATE INDEX IF NOT EXISTS idx_pv_product_active ON product_variants (product_id, is_active);
