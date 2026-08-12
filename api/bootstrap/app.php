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
            'auth_or_cookie' => \App\Http\Middleware\AuthOrCookie::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Nota sem manifestacao do destinatario (cStat 632) e condicao de negocio
        // rotineira, nao falha de sistema. Sem isto cada clique no DANFE de uma nota
        // nao manifestada viraria ERROR com stack trace no laravel.log. O download()
        // ja emite um Log::warning com cStat/xMotivo, que e o registro util.
        $exceptions->dontReport(\Mg\NfeTerceiro\NfeTerceiroXmlIndisponivelException::class);

        // /nfe-terceiro/{id}/xml e /danfe sao abertas em <a target="_blank"> pelo MGsis:
        // navegacao top-level, Accept: text/html. Um abort(404) ali vira a pagina generica
        // do framework — errors::404 tem @section('message', __('Not Found')) HARDCODED,
        // entao a mensagem real ("632 - sem manifestacao do destinatario") se perde com
        // APP_DEBUG=false, e o usuario ve so "Not Found".
        //
        // Hook TIPADO em vez de publicar errors/404.blade.php: publicar a view mudaria
        // TODOS os 404 da API e passaria a ecoar texto nao escrito para usuario — o
        // ModelNotFoundException do route-model-binding preserva a mensagem, e o usuario
        // leria "No query results for model [Mg\NfeTerceiro\NfeTerceiro] 82466".
        //
        // 409 e nao 404: a NfeTerceiro EXISTE; o que falta e uma pre-condicao.
        $exceptions->render(function (
            \Mg\NfeTerceiro\NfeTerceiroXmlIndisponivelException $e,
            \Illuminate\Http\Request $request
        ) {
            $dados = [
                'message' => $e->getMessage(),
                'sugestao' => $e->sugestao,
            ];

            // HTML e so para navegacao top-level de verdade. Qualquer XHR leva JSON —
            // ajax() (X-Requested-With, que o MGsis manda explicitamente em downloadNfe
            // porque cross-origin o jQuery nao poe sozinho) alem do expectsJson() normal,
            // senao um XHR que mande Accept: text/html receberia uma pagina inteira.
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json($dados, 409);
            }

            return response()->view(
                'errors.mg-mensagem',
                $dados + ['titulo' => 'XML da NFe indisponível'],
                409
            );
        });
    })->create();
