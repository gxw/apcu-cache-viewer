<?php

return [
    'app' => [
        'name' => 'APCu Cache Viewer',
        'version' => '1.0.0',
        'environment' => getenv('APP_ENV') ?: 'production',
        'debug' => (bool)(getenv('APP_DEBUG') ?: false),
    ],
    'view' => [
        'path' => __DIR__ . '/../templates',
        'cache' => false, // Set to a directory path to enable view caching
    ],
    'cache' => [
        'prefix' => 'apcu_viewer_',
        'ttl' => 3600, // 1 hour default TTL
    ],
];
