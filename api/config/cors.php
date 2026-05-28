<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    | Frontends Quasar (pessoas/contas/negocios/notas), MGLara e MGsis
    | precisam acessar com `credentials: include` por causa dos cookies
    | de SSO no domínio .mgpapelaria.com.br.
    */

    'paths' => ['api/*', 'oauth/*', 'userinfo', 'login'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [],

    'allowed_origins_patterns' => [
        '#^https?://([a-z0-9-]+\.)*mgpapelaria\.com\.br(:\d+)?$#',
        '#^http://localhost(:\d+)?$#',
        '#^http://127\.0\.0\.1(:\d+)?$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
