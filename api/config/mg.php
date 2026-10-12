<?php

return [

    /*
    |--------------------------------------------------------------------------
    | URLs dos apps MGspa
    |--------------------------------------------------------------------------
    | Mesmo conjunto de variáveis que os frontends já usam nos seus .env, para
    | o backend montar links que apontam de volta pras telas (ex.: link da
    | pessoa no convite do Google Calendar).
    */
    'apps' => [
        'sistema' => env('SISTEMA_URL', 'https://sistema.mgpapelaria.com.br'),
        'negocios' => env('NEGOCIOS_URL', 'https://negocios.mgpapelaria.com.br'),
        'notas' => env('NOTAS_URL', 'https://notas.mgpapelaria.com.br'),
        'contas' => env('CONTAS_URL', 'https://contas.mgpapelaria.com.br'),
        'pessoas' => env('PESSOAS_URL', 'https://pessoas.mgpapelaria.com.br'),
        'estoque' => env('ESTOQUE_URL', 'https://estoque.mgpapelaria.com.br'),
        'agro' => env('AGRO_URL', 'https://agro.mgpapelaria.com.br'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Caminhos em disco
    |--------------------------------------------------------------------------
    */
    'paths' => [
        'nfe_php' => env('NFE_PHP_PATH'),
        'pedido' => env('PEDIDO_PATH', '/tmp/'),
        'transferencia' => env('TRANSFERENCIA_PATH', '/tmp/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Códigos de registros específicos
    |--------------------------------------------------------------------------
    | Chaves de linhas do banco que a regra de negócio referencia direto.
    */
    'codcidade_sinop' => env('CODCIDADE_SINOP'),

    /*
    |--------------------------------------------------------------------------
    | Conferências do caixa (M9 doc-3)
    |--------------------------------------------------------------------------
    | Dia do go-live: pagamentos e vendas de antes não entram em lote de
    | maquineta, sessão de gaveta nem viram pendência.
    */
    'conferencia_inicio' => env('CONFERENCIA_INICIO', '2026-10-02'),
    'codpessoa_safra' => env('SAFRA_CODPESSOA'),
    'codnaturezaoperacao_transferencia_saida' => env('CODNATUREZAOPERACAO_TRANSFERENCIA_SAIDA'),

];
