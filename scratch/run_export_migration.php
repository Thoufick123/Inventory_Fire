<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

Database::execute("ALTER TABLE `seller_rolls` ADD COLUMN IF NOT EXISTS `concern_id` INT NULL DEFAULT 1 AFTER `id`, ADD INDEX IF NOT EXISTS `idx_seller_rolls_concern` (`concern_id`)");
echo "seller_rolls concern_id ensured." . PHP_EOL;
