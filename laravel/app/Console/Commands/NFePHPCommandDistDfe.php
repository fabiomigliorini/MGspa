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
    protected $signature = 'nfe-php:dist-dfe {--codfilial=} {--ultNSU=0} {--numNSU=0} {--chave=}';

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
            $ultNSU = $this->option('ultNSU')??0;
            $numNSU = $this->option('numNSU')??0;
            $chave = $this->option('chave')??null;
            $loops = 0;
            do {
                $loops++;
                $continuar = false;
                try {
                    $resp = NFePHPDistDfeService::consultar($filial, $ultNSU, $numNSU, $chave);
                    if ($resp === null) {
                        break;
                    }
                    $ultNSU = $resp['ultNSU']+1;
                    $continuar = ($resp['ultNSU'] < $resp['maxNSU']);
                    if ($continuar) {
                        $continuar = ($loops < 20);
                    }
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                }
            } while ($continuar);
        }
    }
}
