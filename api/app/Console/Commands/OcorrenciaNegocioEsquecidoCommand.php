<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Mg\Ocorrencia\OcorrenciaService;

// Negocio aberto com item, em PDV monitorado, parado alem do tempo do PDV
// vira ocorrencia "esquecido" (TASK-205)
class OcorrenciaNegocioEsquecidoCommand extends Command
{
    protected $signature = 'ocorrencia:negocio-esquecido';

    protected $description = 'Registra no livro de ocorrências os negócios esquecidos abertos nos PDVs monitorados';

    public function handle()
    {
        DB::beginTransaction();
        $qtd = OcorrenciaService::esquecidos();
        DB::commit();
        $this->info("{$qtd} negócio(s) esquecido(s) registrado(s).");
        return Command::SUCCESS;
    }
}
