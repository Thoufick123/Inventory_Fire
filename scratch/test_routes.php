<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';
require_once BASE_PATH . '/app/Helpers/Session.php';
require_once BASE_PATH . '/app/Helpers/CSRF.php';
require_once BASE_PATH . '/app/Helpers/Response.php';
require_once BASE_PATH . '/routes/web.php';

$router = new Router();
$reflection = new ReflectionClass($router);
$routesProperty = $reflection->getProperty('routes');
$routesProperty->setAccessible(true);
$routes = $routesProperty->getValue($router);

echo "Registered HSN routes:\n";
foreach ($routes as $r) {
    if (str_starts_with($r['path'], 'hsn') || str_starts_with($r['path'], 'api/hsn')) {
        echo "  [{$r['method']}] /{$r['path']} => {$r['controller']}@{$r['action']}\n";
    }
}
