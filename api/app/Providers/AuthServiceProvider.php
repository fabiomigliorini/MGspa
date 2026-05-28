<?php

namespace App\Providers;

use App\Passport\PlainOrHashedClientRepository;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Bridge\ClientRepository as BridgeClientRepository;
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

        // Substitui o ClientRepository do Passport pra aceitar client_secret
        // em plain text (compat com mgsis.oauth_clients legado, que ainda
        // tem secrets não-hashados). Ver app/Passport/PlainOrHashedClientRepository.
        $this->app->bind(BridgeClientRepository::class, PlainOrHashedClientRepository::class);
    }

    public function boot(): void
    {
        // Obrigatório no Passport 11+: password grant vem desligado por padrão.
        Passport::enablePasswordGrant();

        // Tokens expiram à meia-noite (comportamento herdado do MGAuth).
        Passport::tokensExpireIn(now()->endOfDay());

        // Scope `view-user` herdado do MGAuth (registrava em AppServiceProvider).
        // MGLara/MGsis pedem esse scope via SSO_SCOPES no fluxo authorization_code
        // — sem registrar, Passport rejeita o scope solicitado.
        Passport::tokensCan([
            'view-user' => 'View e Usuários',
        ]);
    }
}
