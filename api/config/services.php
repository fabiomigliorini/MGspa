<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auth (paridade com o antigo MGAuth)
    |--------------------------------------------------------------------------
    | Centralizamos aqui o que ficava em env() dentro do AuthController.
    | client_id/secret são os mesmos do MGAuth (banco mgsis.oauth_clients).
    */
    'auth' => [
        'client_id' => env('AUTH_CLIENT_ID'),
        'client_secret' => env('AUTH_CLIENT_SECRET'),
        'cookie_domain' => env('AUTH_COOKIE_DOMAIN', '.mgpapelaria.com.br'),
        'cookie_secure' => (bool) env('AUTH_COOKIE_SECURE', true),
        'cookie_same_site' => env('AUTH_COOKIE_SAME_SITE', 'none'),
        'default_redirect' => env('AUTH_DEFAULT_REDIRECT', 'https://sistema-dev.mgpapelaria.com.br'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Backend legado (MGspa/laravel) — para proxy de rotas ainda não migradas
    |--------------------------------------------------------------------------
    | Configurado mas não usado no Marco 1 (só entra em ação nos Marcos 3+).
    */
    'legacy' => [
        'url' => env('LEGACY_API_URL', 'https://api-mgspa-dev.mgpapelaria.com.br/api'),
    ],

];
