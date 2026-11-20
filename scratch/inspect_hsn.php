<?php
require __DIR__ . '/../config/app.php';
require __DIR__ . '/../app/Helpers/Database.php';

$purchasesWithHsn = Database::fetchAll("SELECT DISTINCT hsn_code FROM production_purchase_items WHERE hsn_code IS NOT NULL AND hsn_code != ''");
echo "HSN codes used in production_purchase_items: " . count($purchasesWithHsn) . "\n";
foreach ($purchasesWithHsn as $p) echo " - " . $p['hsn_code'] . "\n";

$prodHsn = Database::fetchAll("SELECT DISTINCT hsn_code FROM products WHERE hsn_code IS NOT NULL AND hsn_code != ''");
echo "HSN codes used in products: " . count($prodHsn) . "\n";
foreach ($prodHsn as $p) echo " - " . $p['hsn_code'] . "\n";

$varHsn = Database::fetchAll("SELECT DISTINCT hsn_code FROM product_variants WHERE hsn_code IS NOT NULL AND hsn_code != ''");
echo "HSN codes used in product_variants: " . count($varHsn) . "\n";
foreach ($varHsn as $p) echo " - " . $p['hsn_code'] . "\n";
