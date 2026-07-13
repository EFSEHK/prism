<?php

$fromEnv = array_filter(array_map('trim', explode(',', (string) env(
    'CORS_ALLOWED_ORIGINS',
    env('LARAVEL_CORS_ALLOWED_ORIGINS', '')
))));

$defaultOrigins = array_filter([
    'http://localhost:5173',
    'http://localhost:8081',
    'http://localhost:8082',
    env('PRODUCTION_WEB_URL', 'https://sap.innovisiq.com'),
]);

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_unique(array_merge($defaultOrigins, $fromEnv))),

    // Expo web often moves to 8082+ when 8081 is busy; browsers treat missing CORS as Network Error.
    'allowed_origins_patterns' => [
        '#^http://localhost:\d+$#',
        '#^http://127\.0\.0\.1:\d+$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
