<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

$db = Database::getInstance();
$r1 = Database::fetchAll("SELECT purchase_type, COUNT(*) as c FROM production_purchases GROUP BY purchase_type");
print_r($r1);

$r2 = Database::fetchAll("SELECT item_type, COUNT(*) as c FROM production_purchase_items GROUP BY item_type");
print_r($r2);
