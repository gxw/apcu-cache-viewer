<?php

declare(strict_types=1);

use App\Http\Request;
use App\Http\Response;
use App\Security\CsrfProtection;
use App\Security\RateLimiter;

// Route definitions
// Variables $router, $dashboardController, $opcacheController are available
// from the caller's scope (public/app.php)

$requireCsrf = function (Request $request): ?Response {
    if (CsrfProtection::isReadMethod($request->getMethod())) {
        return null;
    }

    $token = $request->post('csrf_token') ?: $request->server('HTTP_X_CSRF_TOKEN');

    if (!CsrfProtection::validateToken($token)) {
        if ($request->isAjax()) {
            return new Response(
                json_encode(['success' => false, 'message' => 'Invalid CSRF token']),
                403,
                ['Content-Type' => 'application/json']
            );
        }

        $_SESSION['flash_errors'] = ['Invalid CSRF token'];
        return new Response('', 302, ['Location' => $_SERVER['HTTP_REFERER'] ?? '/']);
    }

    return null;
};

// Rate limiter factory — returns a middleware-style closure for the given limits
$requireRateLimit = function (int $maxRequests, int $windowSeconds): \Closure {
    $limiter = new RateLimiter($maxRequests, $windowSeconds);
    return function (Request $request) use ($limiter): ?Response {
        $identifier = $request->server('REMOTE_ADDR') ?: 'unknown';
        if (!$limiter->isAllowed($identifier)) {
            if ($request->isAjax()) {
                return new Response(json_encode([
                    'success' => false,
                    'message' => 'Rate limit exceeded. Try again later.'
                ]), 429, ['Content-Type' => 'application/json']);
            }
            $_SESSION['flash_errors'] = ['Rate limit exceeded. Try again later.'];
            return new Response('', 429, ['Location' => $_SERVER['HTTP_REFERER'] ?? '/']);
        }
        return null;
    };
};

$router->get('/', function (Request $request) use ($dashboardController) {
    return $dashboardController->index($request);
});

$router->get('/opcache', function (Request $request) use ($opcacheController) {
    return $opcacheController->index($request);
});

$router->get('/opcache/scripts', function (Request $request) use ($opcacheController) {
    return $opcacheController->scripts($request);
});

$router->post('/opcache/reset', function (Request $request) use ($opcacheController, $requireCsrf, $requireRateLimit) {
    $rateLimitError = $requireRateLimit(6, 3600)($request);
    if ($rateLimitError !== null) {
        return $rateLimitError;
    }
    $csrfError = $requireCsrf($request);
    if ($csrfError !== null) {
        return $csrfError;
    }
    return $opcacheController->reset($request);
});

$router->get('/entries', function (Request $request) use ($dashboardController) {
    return $dashboardController->entries($request);
});

$router->post('/clear-cache', function (Request $request) use ($dashboardController, $requireCsrf, $requireRateLimit) {
    $rateLimitError = $requireRateLimit(6, 3600)($request);
    if ($rateLimitError !== null) {
        return $rateLimitError;
    }
    $csrfError = $requireCsrf($request);
    if ($csrfError !== null) {
        return $csrfError;
    }
    return $dashboardController->clearCache($request);
});

$router->delete('/delete-key/{key}', function (Request $request, string $key) use ($dashboardController, $requireCsrf, $requireRateLimit) {
    $rateLimitError = $requireRateLimit(30, 60)($request);
    if ($rateLimitError !== null) {
        return $rateLimitError;
    }
    $csrfError = $requireCsrf($request);
    if ($csrfError !== null) {
        return $csrfError;
    }
    return $dashboardController->deleteKey($request);
});

$router->get('/get-cache/{key}', function (Request $request, string $key) use ($dashboardController) {
    return $dashboardController->getKeyValue($key);
});

$router->get('/warmup', function (Request $request) use ($dashboardController) {
    return $dashboardController->warmupPage($request);
});

$router->post('/warmup', function (Request $request) use ($dashboardController, $requireCsrf, $requireRateLimit) {
    $rateLimitError = $requireRateLimit(10, 60)($request);
    if ($rateLimitError !== null) {
        return $rateLimitError;
    }
    $csrfError = $requireCsrf($request);
    if ($csrfError !== null) {
        return $csrfError;
    }
    return $dashboardController->warmup($request);
});

$router->get('/audit', function (Request $request) use ($dashboardController) {
    return $dashboardController->audit($request);
});

$router->get('/info', function (Request $request) use ($view) {
    $info = [
        'php_version' => phpversion(),
        'php_sapi' => php_sapi_name(),
        'os' => PHP_OS . ' ' . php_uname('r'),
        'host' => php_uname('n'),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'date_timezone' => date_default_timezone_get(),
        'display_errors' => ini_get('display_errors'),
        'error_reporting' => error_reporting(),
    ];

    $extensions = [];
    foreach (['apcu', 'Zend OPcache', 'mbstring', 'json', 'session', 'pcre', 'filter', 'hash', 'openssl'] as $ext) {
        $extensions[$ext] = phpversion($ext) ?: 'Not loaded';
    }

    $apcuIni = [];
    foreach (['apc.enabled', 'apc.shm_size', 'apc.shm_segments', 'apc.ttl', 'apc.gc_ttl', 'apc.enable_cli'] as $key) {
        $apcuIni[$key] = ini_get($key);
    }

    $opcacheIni = [];
    foreach (['opcache.enable', 'opcache.memory_consumption', 'opcache.interned_strings_buffer', 'opcache.max_accelerated_files', 'opcache.revalidate_freq', 'opcache.validate_timestamps', 'opcache.jit', 'opcache.jit_buffer_size'] as $key) {
        $val = ini_get($key);
        if ($val !== false) {
            $opcacheIni[$key] = $val;
        }
    }

    $view->setData(['info' => $info, 'extensions' => $extensions, 'apcu_ini' => $apcuIni, 'opcache_ini' => $opcacheIni]);
    $content = $view->render('info', ['title' => 'Server Information']);
    return new Response($content);
});

$router->get('/export', function (Request $request) use ($dashboardController) {
    return $dashboardController->export($request);
});

$router->post('/pin-key', function (Request $request) use ($dashboardController, $requireCsrf) {
    $csrfError = $requireCsrf($request);
    if ($csrfError !== null) {
        return $csrfError;
    }
    return $dashboardController->togglePin($request);
});

$router->put('/key/{key}', function (Request $request, string $key) use ($dashboardController, $requireCsrf, $requireRateLimit) {
    $rateLimitError = $requireRateLimit(30, 60)($request);
    if ($rateLimitError !== null) {
        return $rateLimitError;
    }
    $csrfError = $requireCsrf($request);
    if ($csrfError !== null) {
        return $csrfError;
    }
    return $dashboardController->updateKey($request);
});

$router->post('/delete-multiple', function (Request $request) use ($dashboardController, $requireCsrf, $requireRateLimit) {
    $rateLimitError = $requireRateLimit(10, 60)($request);
    if ($rateLimitError !== null) {
        return $rateLimitError;
    }
    $csrfError = $requireCsrf($request);
    if ($csrfError !== null) {
        return $csrfError;
    }
    return $dashboardController->deleteMultiple($request);
});
