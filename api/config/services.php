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

    'mglara' => [
        'url' => env('MGLARA_URL'),
        'imagens_url' => env('MGLARA_IMAGENS_URL', 'https://sistema.mgpapelaria.com.br/MGLara/public/imagens'),
        'produto_url' => env('MGLARA_PRODUTO_URL', 'https://sistema.mgpapelaria.com.br/MGLara/produto'),
    ],

    'bb' => [
        'url_oauth' => env('BB_URL_OAUTH'),
        'url_cobranca' => env('BB_URL_COBRANCA'),
        'url_extrato' => env('BB_URL_EXTRATO'),
        'url_pix' => env('BB_URL_PIX'),
    ],

    'sicredi' => [
        'url_oauth' => env('SICREDI_URL_OAUTH'),
        'url_pix' => env('SICREDI_URL_PIX'),
        'certificado' => env('SICREDI_CERTIFICADO'),
        'certificado_key' => env('SICREDI_CERTIFICADO_KEY'),
        'certificado_senha' => env('SICREDI_CERTIFICADO_SENHA'),
    ],

    'pagarme' => [
        'url' => env('PAGAR_ME_API_BASE_URL'),
        'codpessoa' => env('PAGAR_ME_CODPESSOA'),
    ],

    'saurus' => [
        'url' => env('SAURUS_S2PAY_URL'),
        'credential' => env('SAURUS_S2PAY_CREDENTIAL'),
        'dominio' => env('SAURUS_S2PAY_DOMINIO'),
    ],

    'woo' => [
        'url' => env('WOO_API_URL'),
        'key' => env('WOO_API_KEY'),
        'secret' => env('WOO_API_SECRET'),
        'attribute_id' => env('WOO_ATTRIBUTE_ID'),
        'fator_preco' => env('WOO_FATOR_PRECO', 1),
        'codestoquelocal' => env('WOO_CODESTOQUELOCAL'),
        'codestoquelocal_disponivel' => env('WOO_API_CODESTOQUELOCAL_DISPONIVEL'),
        'codnaturezaoperacao' => env('WOO_CODNATUREZAOPERACAO'),
        'codpdv' => env('WOO_CODPDV'),
        'codpessoavendedor' => env('WOO_CODPESSOAVENDEDOR'),
        'codusuario' => env('WOO_CODUSUARIO'),
        'codprodutobarra_nao_cadastrado' => env('WOO_CODPRODUTOBARRA_NAO_CADASTRADO'),
    ],

    'mercos' => [
        'url' => env('MERCOS_BASEURL'),
        'application_token' => env('MERCOS_APPLICATION_TOKEN'),
        'company_token' => env('MERCOS_COMPANY_TOKEN'),
        'codestoquelocal' => env('MERCOS_CODESTOQUELOCAL'),
        'codestoquelocal_disponivel' => env('MERCOS_CODESTOQUELOCAL_DISPONIVEL'),
        'codnaturezaoperacao' => env('MERCOS_CODNATUREZAOPERACAO'),
        'codpessoavendedor' => env('MERCOS_CODPESSOAVENDEDOR'),
        'codformapagamento_mercospay' => env('MERCOS_CODFORMAPAGAMENTO_MERCOSPAY'),
        'codprodutobarra_nao_cadastrado' => env('MERCOS_CODPRODUTOBARRA_NAO_CADASTRADO'),
    ],

    'google' => [
        'drive_credentials_path' => env('GOOGLE_DRIVE_CREDENTIALS_PATH'),
        'drive_folder_colaboradores_id' => env('GOOGLE_DRIVE_FOLDER_COLABORADORES_ID'),
        'calendar_aniversarios' => env('GOOGLE_CALENDAR_ID_ANIVERSARIOS'),
        'calendar_eventos_rh' => env('GOOGLE_CALENDAR_ID_EVENTOS_RH'),
    ],

    'ably' => [
        'key' => env('ABLY_APP_KEY'),
    ],

    'receitaws' => [
        'token' => env('RECEITA_WS_TOKEN'),
    ],

    'sms' => [
        'chave' => env('CHAVE_API_SMS'),
    ],

];
