<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mg\UnidadeReferencia\UnidadeReferenciaService;

class UpfMtImportar extends Command
{
    protected $signature = 'upf-mt:importar';

    protected $description = 'Importa os valores da UPF-MT do site da SEFAZ-MT (best-effort)';

    public function handle()
    {
        $importados = UnidadeReferenciaService::importarUpfMt();
        $this->info('UPF-MT: ' . count($importados) . ' competências importadas/atualizadas.');
        foreach ($importados as $comp) {
            $this->line("  - {$comp}");
        }
        return Command::SUCCESS;
    }
}
