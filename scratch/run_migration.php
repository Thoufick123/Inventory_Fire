<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

$db = Database::getInstance();

// 1. Check if is_complementary already exists in production_purchase_items
$colCheck = $db->query("SHOW COLUMNS FROM production_purchase_items LIKE 'is_complementary'")->fetch();
if (!$colCheck) {
    $db->exec("ALTER TABLE production_purchase_items ADD COLUMN is_complementary TINYINT(1) NOT NULL DEFAULT 0 AFTER is_mapped");
    echo "Added is_complementary to production_purchase_items.\n";
} else {
    echo "is_complementary already exists in production_purchase_items.\n";
}

// 2. Check if is_complementary already exists in production_bill_items
$colCheck2 = $db->query("SHOW COLUMNS FROM production_bill_items LIKE 'is_complementary'")->fetch();
if (!$colCheck2) {
    $db->exec("ALTER TABLE production_bill_items ADD COLUMN is_complementary TINYINT(1) NOT NULL DEFAULT 0 AFTER unit_price");
    echo "Added is_complementary to production_bill_items.\n";
} else {
    echo "is_complementary already exists in production_bill_items.\n";
}

echo "Database migration completed successfully.\n";
