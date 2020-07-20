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
    protected $signature = 'nfe-php:resolver-pendentes {--quantidade=}';

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
        $pendentes = NFePHPRoboService::pendentes($quantidade);
        foreach ($pendentes as $pendente) {
            $this->info("Agendando Job para #$pendente->codnotafiscal");
            NFePHPResolverJob::dispatch($pendente->codnotafiscal);
        }
    }
}
