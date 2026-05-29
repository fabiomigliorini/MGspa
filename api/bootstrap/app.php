<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        // Auto-discovery do L13 vê só app/Console/Commands/. Commands em
        // outros namespaces (Mg\Rh\*, etc.) precisam ser listados aqui.
        \Mg\Gerador\GeradorModelCommand::class,
        \Mg\Rh\RhCargaInicialCommand::class,
        \Mg\Rh\RhReprocessarPeriodoCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        // Confia em todos os proxies (MGweb/nginx faz a borda TLS na frente)
        $middleware->trustProxies(at: '*');

        // CORS é nativo no L13 — controlado por config/cors.php

        // OAuth2/OIDC endpoints (RFC 6749/7009/7662 + OIDC Core) NÃO devem exigir
        // CSRF — são autenticados via client_credentials no body/Basic Auth ou
        // Bearer token. CSRF é proteção pra sessões web, irrelevante aqui.
        $middleware->preventRequestForgery(except: [
            'oauth/token',
            'oauth/revoke',
            'oauth/introspect',
        ]);

        // Cookies access_token e user_id são lidos como string raw pelos
        // consumers (JWT no Bearer, int em document.cookie). NÃO devem ser
        // encriptados pelo EncryptCookies middleware.
        $middleware->encryptCookies(except: [
            'access_token',
            'user_id',
        ]);

        // Rotas baixadas pelo MGprint (signed URL anônima) e também pelo
        // frontend (Bearer). Cai no primeiro ramo que validar.
        $middleware->alias([
            'auth_or_signed' => \App\Http\Middleware\AuthOrSigned::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
