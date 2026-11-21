<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // O MGweb (nginx) faz a borda TLS. Force HTTPS para todas as URLs
        // geradas pela aplicação quando APP_URL for https.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Rate limiter "api" — desde Laravel 11 não vem mais auto-definido
        // pelo skeleton (era no antigo RouteServiceProvider). Sem isso, o
        // middleware `throttle:api` quebra com MissingRateLimiterException.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->user()?->getAuthIdentifier() ?: $request->ip()
            );
        });

        // Validator `cpf_cnpj` — porta da lib geekcom/validator-docs (não
        // compatível com L13). Verifica CPF (11 dígitos) ou CNPJ (14 caracteres,
        // que podem ter letras desde jul/2026 — TASK-283). As contas ficam em
        // cpfValido()/cnpjValido() do MgHelpers.
        Validator::extend('cpf_cnpj', function ($attribute, $value, $parameters, $validator) {
            $value = documentoLimpo($value);
            $len = strlen($value);
            if ($len === 11 && ctype_digit($value)) {
                return cpfValido($value);
            }
            if ($len === 14) {
                return cnpjValido($value);
            }
            return false;
        }, 'O campo :attribute deve ser um CPF ou CNPJ válido.');

        // Observers ATIVOS:
        // - NotaFiscalObserver: recalcula status interno e tributação dos itens
        //   quando campos críticos mudam (emitida, numero, nfeautorizacao, etc.).
        //   Não depende de integrações externas.
        \Mg\NotaFiscal\NotaFiscal::observe(\Mg\NotaFiscal\Observers\NotaFiscalObserver::class);

        // Observers de RH que alimentam o Google Calendar. A cadeia é:
        //   Ferias/Colaborador/Dependente -> EventoCalendario -> Google Calendar
        // Ferias/Colaborador/Dependente gravam as linhas em tbleventocalendario;
        // EventoCalendario é quem envia ao Google; Pessoa propaga alteração de
        // `nascimento` para os eventos de aniversário.
        //
        // Ficaram comentados no import do Laravel 8 (a8901f4b) e a sincronização
        // ficou parada de mar/2026 a ago/2026 sem sintoma nenhum — não comentar
        // de novo sem antes checar com o RH.
        //
        // O envio depende de storage/app/google/credentials.json e roda na fila:
        // com QUEUE_CONNECTION=redis (produção) sai do request; com `sync` (dev)
        // roda inline, e aí uma falha do Google derruba o cadastro de férias.
        \Mg\Pessoa\Pessoa::observe(\Mg\Pessoa\PessoaObserver::class);
        \Mg\Pessoa\Dependente::observe(\Mg\Pessoa\DependenteObserver::class);
        \Mg\Colaborador\Colaborador::observe(\Mg\Colaborador\ColaboradorObserver::class);
        \Mg\Colaborador\Ferias::observe(\Mg\Colaborador\FeriasObserver::class);
        \Mg\Pessoa\Calendario\EventoCalendario::observe(\Mg\Pessoa\Calendario\EventoCalendarioObserver::class);
    }
}
