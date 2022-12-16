<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

use Mg\Portador\Portador;
use Mg\Pix\PixService;

class PixConsultar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pix:consultar {--codportador=} {--inicio=} {--fim=} {--pagina=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consulta via API pix recebidos';

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
        Log::info("Consultando PIX Recebidos");
        $inicio = $this->option('inicio')??null;
        if (!empty($inicio)) {
            $inicio = Carbon::parse($inicio);
        }
        $fim = $this->option('fim')??null;
        if (!empty($fim)) {
            $fim = Carbon::parse($fim);
        }
        $pagina = $this->option('pagina')??0;

        $qry = Portador::ativo()->whereNotNull('pixdict')->orderBy('codportador');
        if ($codportador = $this->option('codportador')) {
            $qry->where('codportador', $codportador);
        }
        $portadores = $qry->get();
        foreach ($portadores as $portador) {
            Log::info("Consultando PIX Recebidos Portador {$portador->codportador} ({$portador->portador})");
            $processados = 0;
            $paginaAtual = $pagina;
            do {
                $ret = PixService::consultarPix(
                    $portador,
                    $inicio,
                    $fim,
                    $paginaAtual
                );
                if ($pagina != null) {
                    $continuar = false;
                } else {
                    $pag = $ret['parametros']['paginacao'];
                    $continuar = ($pag['paginaAtual'] < ($pag['quantidadeDePaginas'] - 1));
                }
                $paginaAtual = $pag['paginaAtual'] + 1;
                $processados += count($ret['processados']);
            } while ($continuar);
            Log::info("Processados {$processados} PIX Recebidos do Portador {$portador->codportador} ({$portador->portador})");
        }
    }
}
