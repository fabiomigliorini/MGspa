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

    /*
     * Sem isto o navegador refaz o preflight a CADA requisição: o polling do envio de NFe
     * pagava um OPTIONS por consulta, dobrando o número de requisições. 10 min cobrem o
     * polling inteiro (que dura segundos) e mantêm curta a janela de propagação caso as
     * regras acima mudem — navegador com cache quente só enxerga a mudança ao expirar.
     */
    'max_age' => 600,

    'supports_credentials' => true,

];
