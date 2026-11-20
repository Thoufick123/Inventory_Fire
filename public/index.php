<?php
/**
 * Front Controller — Application Entry Point
 * All requests are routed through this file.
 */

// Error reporting based on environment
error_reporting(E_ALL);

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Load configuration (this also loads .env)
$appConfig = require BASE_PATH . '/config/app.php';

// Set timezone
date_default_timezone_set($appConfig['timezone'] ?? 'Asia/Kolkata');

// Set error display based on environment
ini_set('display_errors', $appConfig['debug'] ? '1' : '0');
ini_set('log_errors', '1');
if (!is_dir(BASE_PATH . '/storage/logs')) {
    @mkdir(BASE_PATH . '/storage/logs', 0777, true);
}
ini_set('error_log', BASE_PATH . '/storage/logs/error.log');

// Autoload helpers
require_once BASE_PATH . '/app/Helpers/Database.php';
require_once BASE_PATH . '/app/Helpers/Session.php';
require_once BASE_PATH . '/app/Helpers/Auth.php';
require_once BASE_PATH . '/app/Helpers/CSRF.php';
require_once BASE_PATH . '/app/Helpers/Validator.php';
require_once BASE_PATH . '/app/Helpers/Response.php';
require_once BASE_PATH . '/app/Helpers/Request.php';

// Autoload core models
require_once BASE_PATH . '/app/Models/User.php';
require_once BASE_PATH . '/app/Models/Role.php';

// Autoload middleware
require_once BASE_PATH . '/app/Middleware/AuthMiddleware.php';
require_once BASE_PATH . '/app/Middleware/PermissionMiddleware.php';

// Global high-performance class autoloader with in-memory caching and suffix heuristics
spl_autoload_register(function (string $className) {
    static $resolved = [];
    $cleanClass = basename(str_replace('\\', '/', $className));
    if (isset($resolved[$cleanClass])) {
        return;
    }

    // Direct suffix routing avoids scanning all 5 directories on disk
    $preferred = null;
    if (str_ends_with($cleanClass, 'Controller')) {
        $preferred = BASE_PATH . '/app/Controllers/' . $cleanClass . '.php';
    } elseif (str_ends_with($cleanClass, 'Service')) {
        $preferred = BASE_PATH . '/app/Services/' . $cleanClass . '.php';
    } elseif (str_ends_with($cleanClass, 'Middleware')) {
        $preferred = BASE_PATH . '/app/Middleware/' . $cleanClass . '.php';
    }

    if ($preferred !== null && file_exists($preferred)) {
        require_once $preferred;
        $resolved[$cleanClass] = true;
        return;
    }

    $directories = [
        BASE_PATH . '/app/Models/',
        BASE_PATH . '/app/Services/',
        BASE_PATH . '/app/Helpers/',
        BASE_PATH . '/app/Controllers/',
        BASE_PATH . '/app/Middleware/',
    ];

    foreach ($directories as $dir) {
        $file = $dir . $cleanClass . '.php';
        if (file_exists($file)) {
            require_once $file;
            $resolved[$cleanClass] = true;
            return;
        }
    }
});

// Start session early so session-based schema caching is available
Session::start();

// Ensure concern master tables exist on every request (cached in session after initial check)
if (class_exists('Concern')) {
    Concern::ensureSchema();
}

// Get the requested URL
$requestUrl = isset($_GET['url']) ? trim($_GET['url'], '/') : '';

// Normalize if base folder name is duplicated in the URL path
while (str_starts_with($requestUrl, 'grm-inventory/')) {
    $requestUrl = substr($requestUrl, strlen('grm-inventory/'));
}
while (str_starts_with($requestUrl, 'grm_inventory/')) {
    $requestUrl = substr($requestUrl, strlen('grm_inventory/'));
}
if ($requestUrl === 'grm-inventory' || $requestUrl === 'grm_inventory') {
    $requestUrl = '';
}

$requestMethod = $_SERVER['REQUEST_METHOD'];

// Load and execute routes
require_once BASE_PATH . '/routes/web.php';

// Route dispatcher with graceful exception handling
try {
    $router = new Router();
    $router->dispatch($requestUrl, $requestMethod);
} catch (\Throwable $e) {
    error_log("Unhandled Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());

    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
        || str_starts_with($requestUrl, 'api/');

    if ($isAjax) {
        Response::json([
            'success' => false,
            'message' => $appConfig['debug'] ? $e->getMessage() : 'A server error occurred. Please try again.',
            'error'   => $appConfig['debug'] ? $e->getMessage() : null,
        ], 500);
    } else {
        http_response_code(500);
        $errorMessage = $appConfig['debug'] 
            ? $e->getMessage() 
            : 'A database or system error occurred. Please ensure the database server is running and try again.';
        $trace = $appConfig['debug'] ? ($e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString()) : '';
        
        $errorView = BASE_PATH . '/views/errors/500.php';
        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo "<h1>Server Error</h1><p>" . htmlspecialchars($errorMessage) . "</p>";
        }
        exit;
    }
}
