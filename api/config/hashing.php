<?php

return [

    'driver' => 'bcrypt',

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        // Importante: Laravel 13 trouxe a flag `verify` (verifyAlgorithm)
        // que rejeita hashes não-bcrypt com RuntimeException. As senhas em
        // mgsis.tblusuario.senha NÃO são bcrypt (vêm do MGsis Yii legacy)
        // — `password_verify` consegue validar pelo prefixo do hash, mas a
        // verificação de algoritmo bloqueia antes. Desligamos pra preservar
        // o comportamento anterior do MGAuth (Laravel 10).
        'verify' => false,
        'limit' => null,
    ],

    'argon' => [
        'memory' => 65536,
        'threads' => 1,
        'time' => 4,
        'verify' => true,
    ],

    'rehash_on_login' => true,

];
