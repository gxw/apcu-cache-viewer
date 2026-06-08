<?php
// test_router.php
require_once __DIR__ . '/vendor/autoload.php';

// Test data
$testPath = '/get-cache/MzQ5MTpoclBvc3Rpb25JZHNMaXN0Q3VycmVudDM0OTE=';
$pattern = '#^/get-cache/(?P<key>[^/]+)$#i';

// Test 1: Basic pattern matching
echo "Testing pattern matching:\n";
echo "Pattern: " . $pattern . "\n";
echo "Test path: " . $testPath . "\n\n";

if (preg_match($pattern, $testPath, $matches)) {
    echo "✓ Pattern match SUCCESS\n";
    echo "Matches:\n";
    print_r($matches);

    // Extract the key
    if (isset($matches['key'])) {
        echo "\nExtracted key: " . $matches['key'] . "\n";
        echo "Base64 decoded: " . base64_decode($matches['key']) . "\n";
    }
} else {
    echo "✗ Pattern match FAILED\n";
}

// Test 2: Router class test
echo "\nTesting Router class:\n";

// Create a mock request
$request = new class {
    public function getMethod() { return 'GET'; }
    public function server($key) {
        return '/get-cache/MzQ5MTpoclBvc3Rpb25JZHNMaXN0Q3VycmVudDM0OTE=';
    }
};

// Test the router
$router = new \App\Routing\Router();
$router->add('GET', '/get-cache/{key}', function() { return 'Success'; });

// Get routes using reflection to test the pattern
$reflection = new ReflectionClass($router);
$routesProperty = $reflection->getProperty('routes');
$routesProperty->setAccessible(true);
$routes = $routesProperty->getValue($router);

echo "\nRouter patterns:\n";
foreach ($routes as $route) {
    $method = new ReflectionMethod($router, 'convertToRegex');
    $method->setAccessible(true);
    $pattern = $method->invoke($router, $route['path']);

    echo "- Method: " . $route['method'] . "\n";
    echo "  Path: " . $route['path'] . "\n";
    echo "  Pattern: " . $pattern . "\n";

    // Test the pattern
    if (preg_match($pattern, $testPath, $matches)) {
        echo "  ✓ Pattern matches test path\n";
    } else {
        echo "  ✗ Pattern does NOT match test path\n";
    }
}

// Test matching
echo "\nTesting route matching:\n";
$result = $router->match($request);
if ($result) {
    echo "✓ Route matched successfully\n";
    print_r($result);
} else {
    echo "✗ Route match failed\n";
    // Dump the request path for debugging
    $requestPath = parse_url($request->server('REQUEST_URI'), PHP_URL_PATH);
    echo "Request path: " . $requestPath . "\n";

    // Dump all routes for debugging
    echo "\nAll registered routes:\n";
    foreach ($routes as $route) {
        echo "- " . $route['method'] . " " . $route['path'] . "\n";
    }
}