-- Store an independent taxonomy and HSN classification for each mapped variant.
ALTER TABLE third_party_product_variants
  ADD COLUMN third_party_category_id INT UNSIGNED DEFAULT NULL AFTER barcode,
  ADD COLUMN third_party_subcategory_id INT UNSIGNED DEFAULT NULL AFTER third_party_category_id,
  ADD COLUMN third_party_sub_subcategory_id INT UNSIGNED DEFAULT NULL AFTER third_party_subcategory_id,
  ADD COLUMN hsn_code VARCHAR(20) DEFAULT NULL AFTER third_party_sub_subcategory_id;
