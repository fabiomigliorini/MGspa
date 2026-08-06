<?php

// Os discos locais precisam declarar 'visibility' e 'permissions'. Sem isso o
// Laravel assume Visibility::PRIVATE e cria os diretorios com 0700, quebrando o
// rsync do backup e o acesso do grupo www-data-alpine.

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
            'visibility' => 'public',
            'permissions' => [
                'file' => ['public' => 0664, 'private' => 0664],
                'dir' => ['public' => 0775, 'private' => 0775],
            ],
        ],

        'dominio' => [
            'driver' => 'local',
            'root' => env('DOMINIO_PATH'),
            'visibility' => 'public',
            'permissions' => [
                'file' => ['public' => 0664, 'private' => 0664],
                'dir' => ['public' => 0775, 'private' => 0775],
            ],
        ],

        'pagar-me' => [
            'driver' => 'local',
            'root' => env('PAGAR_ME_PATH'),
            'visibility' => 'public',
            'permissions' => [
                'file' => ['public' => 0664, 'private' => 0664],
                'dir' => ['public' => 0775, 'private' => 0775],
            ],
        ],

        'pix' => [
            'driver' => 'local',
            'root' => env('PIX_PATH'),
            'visibility' => 'public',
            'permissions' => [
                'file' => ['public' => 0664, 'private' => 0664],
                'dir' => ['public' => 0775, 'private' => 0775],
            ],
        ],

        'negocio-anexo' => [
            'driver' => 'local',
            'root' => env('NEGOCIO_ANEXO_PATH'),
            'visibility' => 'public',
            'permissions' => [
                'file' => ['public' => 0664, 'private' => 0664],
                'dir' => ['public' => 0775, 'private' => 0775],
            ],
        ],

        'pessoa-anexo' => [
            'driver' => 'local',
            'root' => env('PESSOA_ANEXO_PATH'),
            'visibility' => 'public',
            'permissions' => [
                'file' => ['public' => 0664, 'private' => 0664],
                'dir' => ['public' => 0775, 'private' => 0775],
            ],
        ],

        'contrato-anexo' => [
            'driver' => 'local',
            'root' => env('CONTRATO_ANEXO_PATH', storage_path('app/contrato-anexo')),
            'visibility' => 'public',
            'permissions' => [
                'file' => ['public' => 0664, 'private' => 0664],
                'dir' => ['public' => 0775, 'private' => 0775],
            ],
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];

