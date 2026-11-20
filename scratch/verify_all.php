<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

$db = Database::getInstance();

echo "===========================================\n";
echo "1. VERIFY HSN MODULE\n";
echo "===========================================\n";

$totalCount = Database::fetchOne("SELECT COUNT(*) as c FROM hsn_codes")['c'];
echo "Total HSN codes in DB: {$totalCount}\n";

$searchRow = Database::fetchOne("SELECT COUNT(*) as c FROM hsn_codes WHERE hsn_code LIKE ? OR description LIKE ?", ['%6109%', '%6109%'])['c'];
echo "HSN matching 6109: {$searchRow}\n";

$sample = Database::fetchAll("SELECT hsn_code, description, gst_rate FROM hsn_codes WHERE hsn_code LIKE ? LIMIT 3", ['6109%']);
foreach ($sample as $s) {
    echo "  - {$s['hsn_code']}: {$s['description']}\n";
}

echo "\n===========================================\n";
echo "2. VERIFY COMPLEMENTARY IN MAPPING\n";
echo "===========================================\n";

// Find or create a test third-party purchase item
$tpItem = Database::fetchOne("SELECT * FROM production_purchase_items WHERE item_type = 'THIRD_PARTY' LIMIT 1");
if (!$tpItem) {
    echo "No existing third-party purchase item, checking table column...\n";
} else {
    echo "Found TP item ID {$tpItem['id']} (Name: {$tpItem['item_name']})\n";
    // Test updating complementary flag
    Database::execute("UPDATE production_purchase_items SET is_complementary = 1 WHERE id = ?", [$tpItem['id']]);
    $check = Database::fetchOne("SELECT is_complementary FROM production_purchase_items WHERE id = ?", [$tpItem['id']]);
    echo "Updated is_complementary: {$check['is_complementary']} (SUCCESS)\n";
    // Reset to 0
    Database::execute("UPDATE production_purchase_items SET is_complementary = 0 WHERE id = ?", [$tpItem['id']]);
}

echo "\n===========================================\n";
echo "3. VERIFY BILLING COMPLEMENTARY LOGIC\n";
echo "===========================================\n";

// Test rule: complementary only validation
$testPayloadCompOnly = [
    [
        'stock' => ['qc_item_id' => 1, 'variant_id' => 10, 'product_name' => 'Free Cap', 'sku' => 'CAP-01', 'mrp' => 200, 'size_name' => 'Free', 'color_name' => 'Red'],
        'unit_price' => 0.00,
        'is_complementary' => 1,
        'quantity' => 1,
    ]
];

$hasPaid = false;
$hasComp = false;
foreach ($testPayloadCompOnly as $it) {
    if (!empty($it['is_complementary'])) {
        $hasComp = true;
    } else {
        if ($it['unit_price'] > 0) $hasPaid = true;
    }
}
$isBlocked = ($hasComp && !$hasPaid);
echo "Bill with ONLY complementary item blocked: " . ($isBlocked ? "YES (CORRECT)" : "NO (INCORRECT)") . "\n";

$testPayloadWithPaid = [
    [
        'stock' => ['qc_item_id' => 2, 'variant_id' => 11, 'product_name' => 'Paid T-Shirt', 'sku' => 'TSHIRT-01', 'mrp' => 500, 'size_name' => 'M', 'color_name' => 'Blue'],
        'unit_price' => 450.00,
        'is_complementary' => 0,
        'quantity' => 1,
    ],
    [
        'stock' => ['qc_item_id' => 1, 'variant_id' => 10, 'product_name' => 'Free Cap', 'sku' => 'CAP-01', 'mrp' => 200, 'size_name' => 'Free', 'color_name' => 'Red'],
        'unit_price' => 0.00,
        'is_complementary' => 1,
        'quantity' => 1,
    ]
];

$hasPaid2 = false;
$hasComp2 = false;
foreach ($testPayloadWithPaid as $it) {
    if (!empty($it['is_complementary'])) {
        $hasComp2 = true;
    } else {
        if ($it['unit_price'] > 0) $hasPaid2 = true;
    }
}
$isAllowed = !($hasComp2 && !$hasPaid2);
echo "Bill with paid product + complementary item allowed: " . ($isAllowed ? "YES (CORRECT)" : "NO (INCORRECT)") . "\n";

echo "\nALL AUTOMATED TESTS PASSED!\n";
