<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use Mg\Filial\Filial;
use Mg\NFePHP\NFePHPDistDfeService;

class NFePHPCommandDistDfe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nfe-php:dist-dfe {--codfilial=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executa consulta de Distribuicao de DFe (Documento Fiscal Eletronico) na Sefaz';

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
        $qry = Filial::ativo()->where('dfe', true)->orderBy('codempresa')->orderBy('codfilial');
        if ($codfilial = $this->option('codfilial')) {
            $qry->where('codfilial', $codfilial);
        }
        $filiais = $qry->get();
        foreach ($filiais as $filial) {
            $nsu = null;
            do {
                Log::info("NFePHPCommandDistDfe - Filial {$filial->codfilial} - NSU {$nsu}");
                $resp = NFePHPDistDfeService::consultar($filial, $nsu);
                $nsu = $resp['ultNSU'];
            } while ($resp['ultNSU'] < $resp['maxNSU']);
        }
    }
}
