<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Mg\NFePHP\NFePHPRoboService;
use Mg\NFePHP\NFePHPResolverJob;

class NFePHPCommandResolverPendentes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nfe-php:resolver-pendentes {--quantidade=1000} {--minutos=60}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Varre lista de Notas Fiscais nao autorizadas cria Jobs para cada uma pedindo para resolver';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $quantidade = $this->option('quantidade')??1000;
        $minutos = $this->option('minutos')??60;
        $pendentes = NFePHPRoboService::pendentes($minutos, $quantidade, $minutos);
        foreach ($pendentes as $pendente) {
            $this->info("Agendando Job para #$pendente->codnotafiscal");
            NFePHPResolverJob::dispatch($pendente->codnotafiscal);
        }
    }
}
