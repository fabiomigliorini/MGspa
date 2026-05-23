<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        //
    ];

    public function register(): void
    {
        // Precisa rodar ANTES do PassportServiceProvider boot — em register()
        // o flag estático é setado a tempo de pular o registro automático
        // das rotas /oauth/*. Usamos wrappers próprios em routes/api.php
        // (paths /api/oauth/token, etc.) espelhando o contrato do MGAuth.
        Passport::ignoreRoutes();
    }

    public function boot(): void
    {
        // Obrigatório no Passport 11+: password grant vem desligado por padrão.
        Passport::enablePasswordGrant();

        // Tokens expiram à meia-noite (comportamento herdado do MGAuth).
        Passport::tokensExpireIn(now()->endOfDay());
    }
}
