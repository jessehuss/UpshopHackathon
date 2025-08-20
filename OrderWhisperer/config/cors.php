<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => (static function () {
        $env = env('CORS_ALLOWED_ORIGINS', '*');
        if ($env === '*') {
            return ['*'];
        }
        return array_values(array_filter(array_map('trim', explode(',', (string) $env))));
    })(),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Cache-Control', 'Content-Type'],

    'max_age' => 3600,

    'supports_credentials' => false,
];


