<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        'boleto' => [
            'driver' => 'local',
            'root' => env('BOLETO_PATH'),
        ],

        'dominio' => [
            'driver' => 'local',
            'root' => env('DOMINIO_PATH'),
        ],

        'pagar-me' => [
            'driver' => 'local',
            'root' => env('PAGAR_ME_PATH'),
        ],

        'pix' => [
            'driver' => 'local',
            'root' => env('PIX_PATH'),
        ],

        'negocio-anexo' => [
            'driver' => 'local',
            'root' => env('NEGOCIO_ANEXO_PATH'),
        ],

        'pessoa-anexo' => [
            'driver' => 'local',
            'root' => env('PESSOA_ANEXO_PATH'),
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
