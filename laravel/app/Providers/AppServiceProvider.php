<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Mg\NotaFiscal\NotaFiscal;
use Mg\NotaFiscal\Observers\NotaFiscalObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Registra o Observer para NotaFiscal
        NotaFiscal::observe(NotaFiscalObserver::class);
    }
}
