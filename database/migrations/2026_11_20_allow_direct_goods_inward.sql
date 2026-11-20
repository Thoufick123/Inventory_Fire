-- Allow direct Goods Inward receipts that are not tied to a purchase order.
ALTER TABLE `purchase_inwards`
  MODIFY COLUMN `purchase_order_id` int(10) unsigned DEFAULT NULL;
