<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
    }
}
