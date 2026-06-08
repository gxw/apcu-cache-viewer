<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
if (isset($_SERVER['HTTP_CLIENT_IP'])
    || isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    || !in_array(@$_SERVER['REMOTE_ADDR'], array('10.48.50.121', '85.115.213.245', '85.115.213.244', '85.115.213.243', '93.177.90.9'))
) {
    header('HTTP/1.0 403 Not Found');
    exit('');
}
*/

// Set development environment for PHP built-in server
if (php_sapi_name() === 'cli-server') {
    $_SERVER['APP_ENV'] = 'development';
    $_SERVER['APP_DEBUG'] = 'true';
}

// Set default timezone
date_default_timezone_set('Europe/Warsaw');

use App\Controller\DashboardController;
use App\Controller\OpcacheController;
use App\Http\Request;
use App\Http\Response;
use App\Routing\Router;
use App\Services\AuditService;
use App\Services\CacheService;
use App\Services\OpcacheService;
use App\View\View;
use App\Cache\CacheManager;

// Get the request URI and method (defined early to be available globally)
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Define base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
// PHP built-in server (php -S) → no prefix; Apache behind /apcu → /apcu
$basePath = php_sapi_name() === 'cli-server' ? '' : '/apcu';

define('BASE_URL', "{$protocol}://{$host}{$basePath}");

// Create a new Router instance
$router = new Router();

// Create a new View instance
$view = new View(__DIR__ . '/../templates');

// Create a new CacheManager instance
$cacheManager = new CacheManager();

// Create a new CacheService instance
$cacheService = new CacheService($cacheManager);

// Create a new OpcacheService instance
$opcacheService = new OpcacheService();

// Create a new AuditService instance
$auditService = new AuditService();

// Create a new DashboardController instance
$dashboardController = new DashboardController($view, $cacheService, $auditService);

// Create a new OpcacheController instance
$opcacheController = new OpcacheController($view, $opcacheService);


try {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

    // Create a Request object with the processed URI
    $request = new Request(
        $_GET,
        $_POST,
        $_COOKIE,
        $_FILES,
        $_SERVER,
        $requestUri,
        $requestMethod
    );

    // Load routes
    require __DIR__ . '/../routes/web.php';

    // Handle the request with the correct path
    $response = $router->dispatch($request);

    if ($response->getStatusCode() === 404 && $response->getHeader('Content-Type') === null) {
        $response = new Response($view->render('errors/404'), 404, ['Content-Type' => 'text/html']);
    }
    $response->send();

} catch (\Throwable $e) {
    // Log the error
    error_log('Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());

    // Show error page
    if (isset($view)) {
        echo $view->render('errors/500', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    } else {
        echo 'An error occurred: ' . htmlspecialchars($e->getMessage());
    }
}
