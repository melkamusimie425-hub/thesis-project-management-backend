<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://otpms.test',
        'https://thesis-project-management-frontend.vercel.app',
        'https://thesis-project-management-frontend-gw8z4uli6-melkia.vercel.app',
        'https://thesis-project-management-frontend-melkia.vercel.app',
    ],

    'allowed_origins_patterns' => [
        '#^https://thesis-project-management-frontend.*\.vercel\.app$#',
        '#^https://.*\.vercel\.app$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
