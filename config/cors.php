<?php

return [

    // Paths matching these patterns will have CORS headers applied.
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],

    // Allowed HTTP methods for the matched paths.
    'allowed_methods' => ['*'],

    // Allowed origins — local dev + both Vercel URL formats
    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://otpms.test',
        'https://thesis-project-management-frontend-gw8z4uli6-melkia.vercel.app',
        'https://thesis-project-management-frontend.vercel.app',
    ],

    'allowed_origins_patterns' => [
        // Allow any Vercel preview deployment URL for this project
        '#^https://thesis-project-management-frontend.*\.vercel\.app$#',
    ],

    // Headers the browser is allowed to send with the request.
    'allowed_headers' => ['*'],

    // Headers the browser is allowed to read from the response.
    'exposed_headers' => [],

    'max_age' => 0,

    // Allow the SPA to send cookies so Sanctum stateful auth works.
    'supports_credentials' => true,

];
