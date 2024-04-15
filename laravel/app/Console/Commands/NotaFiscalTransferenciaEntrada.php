<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Illuminate\Support\Facades\Log;
use Mg\NotaFiscal\NotaFiscalTransferenciaService;

class NotaFiscalTransferenciaEntrada extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nota-fiscal:transferencia-entrada';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gera o registro de Transferencia de Entrada para as notas de Transferencia de Saida Emitidas!';

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
        $nfs = NotaFiscalTransferenciaService::buscarNotasTransferenciaSaidaSemEntrada();
        foreach ($nfs as $nf) {
            try {
                DB::beginTransaction();
                $nfEnt = NotaFiscalTransferenciaService::gerarTransferenciaEntrada($nf);
                DB::commit();
                Log::info("Importada Transferencia Entrada {$nf->numero} Chave {$nf->nfechave} ({$nf->codnotafiscal})!");
            } catch (\Exception $e) {
                Log::error("Falha ao gerar Transferencia Entrada {$nf->numero} Chave {$nf->nfechave} ({$nf->codnotafiscal})!");
                Log::error($e->getMessage());
            }
        }
    }
}
