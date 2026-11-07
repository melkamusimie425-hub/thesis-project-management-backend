<?php

return [

    // The stateful domains that may receive Sanctum cookies.
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:5173,127.0.0.1,127.0.0.1:5173,::1,otpms.test',
        env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
    ))),

    // The Sanctum token expiration (in minutes). null = never expire.
    'expiration' => null,

    // Guard used to authenticate Sanctum token requests.
    'guard' => ['web'],

];
