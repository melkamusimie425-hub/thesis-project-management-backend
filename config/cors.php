<?php

return [

    // Paths matching these patterns will have CORS headers applied.
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],

    // Allowed HTTP methods for the matched paths.
    'allowed_methods' => ['*'],

    // The React SPA runs on Vite's dev server during development.
    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://otpms.test',
    ],

    'allowed_origins_patterns' => [],

    // Headers the browser is allowed to send with the request.
    'allowed_headers' => ['*'],

    // Headers the browser is allowed to read from the response.
    'exposed_headers' => [],

    'max_age' => 0,

    // Allow the SPA to send cookies so Sanctum stateful auth works.
    'supports_credentials' => true,

];
