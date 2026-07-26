<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your settings for cross-origin requests (CORS).
    | These determine what operations may be executed in browsers.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'], // Allow all HTTP methods (GET, POST, etc.)

    'allowed_origins' => [
        'http://localhost:3000',
        'http://localhost:3001',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
        'http://192.168.2.1:3000',
        'http://172.22.80.1:3000',
        'http://172.25.20.53:3000',
        'http://172.25.20.53:3001',
        'http://10.68.87.45:3000',
        'http://172.25.20.84:3000',
        'http://172.25.20.120:3000',
        'http://172.25.20.120:3000'
        
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'], // Allow all headers (e.g., Content-Type, X-XSRF-TOKEN)

    'exposed_headers' => [
        'Authorization',
        'X-CSRF-TOKEN',
        'X-Requested-With',
    ],

    'max_age' => 3600, // Cache preflight responses for 1 hour

    'supports_credentials' => true, // Required for cookie-based auth like Laravel Sanctum

];
