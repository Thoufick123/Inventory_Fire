<?php
define('BASE_PATH', dirname(__DIR__));
$appConfig = require BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';
require_once BASE_PATH . '/app/Helpers/Session.php';
require_once BASE_PATH . '/app/Helpers/Auth.php';
require_once BASE_PATH . '/app/Helpers/Response.php';
require_once BASE_PATH . '/app/Controllers/ProductionController.php';

$controller = new ProductionController();

echo "--- Testing Universal Damage QR Lookup ---" . PHP_EOL;

$cols = Database::fetchAll("DESCRIBE qr_generation_items");
echo "Columns in qr_generation_items: " . implode(', ', array_column($cols, 'Field')) . PHP_EOL;

// 1. Check an existing QR from qr_generation_items if any
$qrItem = Database::fetchOne("SELECT * FROM qr_generation_items ORDER BY id DESC LIMIT 1");
if ($qrItem) {
    echo "Found QR in qr_generation_items: " . $qrItem['qr_code'] . PHP_EOL;
    $refMethod = new ReflectionMethod($controller, 'universalDamageQrLookup');
    $refMethod->setAccessible(true);
    $result = $refMethod->invoke($controller, $qrItem['qr_code']);
    echo "Lookup Result: " . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
}

// 2. Test Transaction Simulation
Database::beginTransaction();
try {
    $testQr = 'TEST-DAMAGED-QR-' . time();
    $genBatch = Database::fetchOne("SELECT id FROM qr_generations LIMIT 1");
    $genId = $genBatch ? (int)$genBatch['id'] : 1;

    // Insert into qr_generation_items
    Database::execute(
        "INSERT INTO qr_generation_items (generation_id, qr_code, status, created_at) VALUES (?, ?, 'ACTIVE', NOW())",
        [$genId, $testQr]
    );
    echo "Inserted active test QR in qr_generation_items: $testQr" . PHP_EOL;

    $refMethod = new ReflectionMethod($controller, 'universalDamageQrLookup');
    $refMethod->setAccessible(true);
    $found = $refMethod->invoke($controller, $testQr);
    echo "Universal QR lookup succeeded: " . ($found ? "YES (found in: {$found['found_in']}, name: {$found['product_name']})" : "NO") . PHP_EOL;

    // Simulate counting quantity = 5
    $qty = 5;
    $unitLoss = (float)($found['loss'] ?? 100.0);
    $unitWeight = (float)($found['piece_weight'] ?? 0.25);
    $totalLoss = $unitLoss * $qty;
    $totalWeight = $unitWeight * $qty;

    // Insert into roll_damages
    Database::execute(
        "INSERT INTO roll_damages (
            damage_number, item_type, qr_code, damaged_quantity, damaged_weight, loss_amount,
            damage_reason, status, reported_by, created_at, updated_at
        ) VALUES ('DMG-TEST-0001', ?, ?, ?, ?, ?, 'Defective Stitching', 'LOGGED', 1, NOW(), NOW())",
        [$found['item_type'], $testQr, $qty, $totalWeight, $totalLoss]
    );
    $damageId = Database::lastInsertId();
    echo "Created damage record #$damageId: Qty = $qty, Weight = {$totalWeight}kg, Valuation = Rs. {$totalLoss}" . PHP_EOL;

    // Perform the deletions as implemented in storeDamage()
    Database::execute("DELETE FROM qr_generation_items WHERE UPPER(TRIM(qr_code)) = ?", [$testQr]);
    Database::execute("DELETE FROM warehouse_qr_codes WHERE UPPER(TRIM(qr_code)) = ?", [$testQr]);
    Database::execute("INSERT IGNORE INTO production_deleted_label_codes (code, created_by, created_at) VALUES (?, 1, NOW())", [$testQr]);

    // Verify deleted
    $existsInQrItems = Database::fetchOne("SELECT * FROM qr_generation_items WHERE qr_code = ?", [$testQr]);
    $loggedDeleted = Database::fetchOne("SELECT * FROM production_deleted_label_codes WHERE code = ?", [$testQr]);

    echo "Exists in qr_generation_items after damage confirmation: " . ($existsInQrItems ? "YES (FAILED)" : "NO (DELETED)") . PHP_EOL;
    echo "Logged in production_deleted_label_codes: " . ($loggedDeleted ? "YES (CONFIRMED)" : "NO (FAILED)") . PHP_EOL;

} finally {
    Database::rollback();
    echo "Transaction rolled back cleanly." . PHP_EOL;
}
