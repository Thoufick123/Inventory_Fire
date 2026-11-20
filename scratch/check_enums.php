<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

$db = Database::getInstance();
$stmt1 = $db->query("SHOW COLUMNS FROM production_purchases LIKE 'purchase_type'");
print_r($stmt1->fetch(PDO::FETCH_ASSOC));

$stmt2 = $db->query("SHOW COLUMNS FROM production_purchase_items LIKE 'item_type'");
print_r($stmt2->fetch(PDO::FETCH_ASSOC));
