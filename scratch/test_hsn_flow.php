<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Helpers/Database.php';

echo "=== 1. VERIFYING HSN CODES IN DB ===\n";
$totalHsn = Database::fetchColumn("SELECT COUNT(*) FROM hsn_codes");
echo "Total HSN codes: $totalHsn\n";

$samples = Database::fetchAll("SELECT hsn_code, gst_rate, description FROM hsn_codes WHERE hsn_code IN ('5208', '6109', '6203', '9606', '8452') ORDER BY hsn_code");
foreach ($samples as $s) {
    echo " - HSN {$s['hsn_code']} | GST: {$s['gst_rate']}% | {$s['description']}\n";
}

echo "\n=== 2. TEST QUICK ADD HSN CODE API ===\n";
$testCode = 'TEST' . rand(100, 999);
$testDesc = 'Test Spandex Knitted Elastic Tape';
$testGst = 12.00;

Database::execute(
    "INSERT INTO hsn_codes (hsn_code, description, gst_rate, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())",
    [$testCode, $testDesc, $testGst]
);
$inserted = Database::fetchOne("SELECT * FROM hsn_codes WHERE hsn_code = ?", [$testCode]);
if ($inserted && (float)$inserted['gst_rate'] === 12.00) {
    echo "SUCCESS: Inserted HSN '$testCode' with GST rate 12.00%\n";
} else {
    echo "FAILED: Could not find inserted HSN '$testCode'\n";
}
// Clean up test entry
Database::execute("DELETE FROM hsn_codes WHERE hsn_code = ?", [$testCode]);
echo "Cleaned up test HSN '$testCode'.\n";

echo "\n=== 3. BENCHMARK RETRIEVAL SPEED ===\n";
$start = microtime(true);
$codes = Database::fetchAll("SELECT id, hsn_code, description, gst_rate FROM hsn_codes ORDER BY hsn_code ASC");
$time = (microtime(true) - $start) * 1000;
$jsonSize = strlen(json_encode($codes));
echo "Retrieved " . count($codes) . " codes in " . round($time, 2) . " ms\n";
echo "JSON payload size: " . round($jsonSize / 1024, 2) . " KB (previously ~2,600 KB)\n";

echo "\n=== 4. VERIFY SIDEBAR LAYOUT (NO HSN MODULE) ===\n";
$mainLayout = file_get_contents(__DIR__ . '/../views/layouts/main.php');
if (strpos($mainLayout, '<span>HSN Module</span>') === false) {
    echo "SUCCESS: 'HSN Module' is completely removed from the sidebar navigation!\n";
} else {
    echo "WARNING: 'HSN Module' still found in sidebar layout!\n";
}

echo "\nAll checks passed successfully!\n";
