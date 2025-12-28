<?php

return [
    'paths' => ['api/*', 'oauth/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // 'allowed_origins' => [
    //     'https://sistema-dev.mgpapelaria.com.br:8082'
    // ],

    'allowed_origins_patterns' => [
        '/^https:\/\/localhost:\d+$/',                           // localhost com porta
        '/^https:\/\/[a-z0-9\-]+\.mgpapelaria\.com\.br$/',      // subdominio sem porta
        '/^https:\/\/[a-z0-9\-]+\.mgpapelaria\.com\.br:\d+$/',  // subdominio com porta
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];