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
    protected $signature = 'nfe-php:dist-dfe {--codfilial=} {--nsu-inicial=} {--nsu-final=}';

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
        $loops = 0;
        $qry = Filial::ativo()->where('dfe', true)->orderBy('codempresa')->orderBy('codfilial');
        if ($codfilial = $this->option('codfilial')) {
            $qry->where('codfilial', $codfilial);
        }
        $filiais = $qry->get();
        $nsuInicial = $this->option('nsu-inicial')??0;
        $nsuFinal = $this->option('nsu-final')??0;
        foreach ($filiais as $filial) {
            do {
                $loops++;
                $continuar = false;
                try {
                    $resp = NFePHPDistDfeService::consultar($filial, $nsuInicial, $nsuFinal);
                    $nsuInicial = $resp['ultNSU']+1;
                    $continuar = ($resp['ultNSU'] < $resp['maxNSU']);
                    if ($continuar) {
                        $continuar = ($loops < 20);
                    }
                    if ($continuar && ($nsuFinal != 0)) {
                        $continuar = ($nsuInicial <= $nsuFinal);
                    }
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                }
            } while ($continuar);
        }
    }
}
