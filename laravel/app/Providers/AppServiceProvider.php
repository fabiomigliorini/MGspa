<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Mg\NotaFiscal\NotaFiscal;
use Mg\NotaFiscal\Observers\NotaFiscalObserver;

use Mg\Pessoa\Pessoa;
use Mg\Pessoa\PessoaObserver;

use Mg\Pessoa\Dependente;
use Mg\Pessoa\DependenteObserver;

use Mg\Colaborador\Colaborador;
use Mg\Colaborador\ColaboradorObserver;

use Mg\Colaborador\Ferias;
use Mg\Colaborador\FeriasObserver;

use Mg\Pessoa\Calendario\EventoCalendario;
use Mg\Pessoa\Calendario\EventoCalendarioObserver;

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

        // Registra os Observers do RH
        Pessoa::observe(PessoaObserver::class);
        Dependente::observe(DependenteObserver::class);
        Colaborador::observe(ColaboradorObserver::class);
        Ferias::observe(FeriasObserver::class);
        EventoCalendario::observe(EventoCalendarioObserver::class);
    }
}
