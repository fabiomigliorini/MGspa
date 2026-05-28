<?php
// Gera um Personal Access Token Passport p/ smoke test
// Uso: docker exec mgspa-api php /opt/www/MGspa/api/scripts/smoke-token.php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Acha um usuário ativo qualquer (não importa quem — só queremos token válido)
$u = Mg\Usuario\Usuario::whereNull('inativo')->first()
    ?? Mg\Usuario\Usuario::first();

if (!$u) {
    fwrite(STDERR, "Nenhum usuário no banco\n");
    exit(1);
}

$token = $u->createToken('smoke-test')->accessToken;
echo $token . PHP_EOL;
