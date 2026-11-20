<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';
require_once BASE_PATH . '/app/Helpers/Session.php';
require_once BASE_PATH . '/app/Helpers/CSRF.php';
require_once BASE_PATH . '/app/Controllers/HsnController.php';

$hsn = new HsnController();

// 1. Test query execution for index
$_GET['search'] = 'cotton';
$_GET['per_page'] = 25;
$_GET['page'] = 1;

$db = Database::getInstance();
$totalRow = Database::fetchOne("SELECT COUNT(*) as total FROM hsn_codes WHERE hsn_code LIKE ? OR description LIKE ?", ['%cotton%', '%cotton%']);
echo "Total matching 'cotton': " . $totalRow['total'] . "\n";

$items = Database::fetchAll("SELECT id, hsn_code, description, gst_rate FROM hsn_codes WHERE hsn_code LIKE ? OR description LIKE ? ORDER BY hsn_code ASC LIMIT 5", ['%cotton%', '%cotton%']);
echo "Sample items:\n";
foreach ($items as $it) {
    echo "  - HSN: {$it['hsn_code']} | {$it['description']} | GST: " . ($it['gst_rate'] ?? 'NULL') . "\n";
}

// 2. Test inserting a test HSN code
$testCode = 'TEST9999';
Database::execute("DELETE FROM hsn_codes WHERE hsn_code = ?", [$testCode]);
Database::execute("INSERT INTO hsn_codes (hsn_code, description, gst_rate, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())", [$testCode, 'Test commodity description for automated unit test', null]);
$inserted = Database::fetchOne("SELECT * FROM hsn_codes WHERE hsn_code = ?", [$testCode]);
echo "Inserted test code: " . $inserted['hsn_code'] . " with ID " . $inserted['id'] . "\n";

// 3. Test updating it
Database::execute("UPDATE hsn_codes SET description = ?, gst_rate = 5.00 WHERE id = ?", ['Updated test commodity description', $inserted['id']]);
$updated = Database::fetchOne("SELECT * FROM hsn_codes WHERE id = ?", [$inserted['id']]);
echo "Updated test code description: " . $updated['description'] . " | GST: " . $updated['gst_rate'] . "\n";

// 4. Test deleting it
Database::execute("DELETE FROM hsn_codes WHERE id = ?", [$inserted['id']]);
$deleted = Database::fetchOne("SELECT * FROM hsn_codes WHERE id = ?", [$inserted['id']]);
echo "Deleted test code verified: " . ($deleted ? 'FAILED' : 'SUCCESS') . "\n";
