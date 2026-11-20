<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

$db = Database::getInstance();
$db->exec("ALTER TABLE production_purchases MODIFY COLUMN purchase_type ENUM('FABRIC_ROLL','RAW_MATERIAL','THIRD_PARTY','OWN_PRODUCT') NOT NULL DEFAULT 'FABRIC_ROLL'");
$db->exec("ALTER TABLE production_purchase_items MODIFY COLUMN item_type ENUM('FABRIC_ROLL','RAW_MATERIAL','THIRD_PARTY','OWN_PRODUCT') NOT NULL DEFAULT 'FABRIC_ROLL'");
echo "Enums updated successfully.\n";
