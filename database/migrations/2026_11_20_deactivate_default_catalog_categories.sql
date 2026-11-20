-- Deactivate only the five built-in starter categories.
-- Rows are retained so existing products and historical records keep their references.
UPDATE `categories`
SET `is_active` = 0
WHERE `is_active` = 1
  AND (
    (`code` = 'KW' AND `slug` = 'kids-wear')
    OR (`code` = 'MW' AND `slug` = 'mens-wear')
    OR (`code` = 'WW' AND `slug` = 'womens-wear')
    OR (`code` = 'BW' AND `slug` = 'baby-wear')
    OR (`code` = 'ACC' AND `slug` = 'accessories-other')
  );
