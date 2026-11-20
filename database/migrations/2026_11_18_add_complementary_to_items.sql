-- Add is_complementary to production_purchase_items and production_bill_items
ALTER TABLE production_purchase_items 
ADD COLUMN is_complementary TINYINT(1) NOT NULL DEFAULT 0 AFTER is_mapped;

ALTER TABLE production_bill_items 
ADD COLUMN is_complementary TINYINT(1) NOT NULL DEFAULT 0 AFTER unit_price;
