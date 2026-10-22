<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mg\Impressora\ImpressoraService;

// Carga inicial da tblimpressora a partir do printers.json do servidor (TASK-253).
// Cria so' as que faltam; rodar antes de apagar o arquivo.
class ImpressoraImportarJson extends Command
{
    protected $signature = 'impressora:importar-json {arquivo? : caminho do printers.json (padrao: api/printers.json)}';
    protected $description = 'Importa as impressoras do printers.json para o cadastro (tblimpressora)';

    public function handle()
    {
        $arquivo = $this->argument('arquivo') ?: base_path('printers.json');
        if (!file_exists($arquivo)) {
            $this->error("Arquivo {$arquivo} nao encontrado.");
            return 1;
        }
        $criadas = ImpressoraService::importarJson($arquivo);
        foreach ($criadas as $nome) {
            $this->line("criada: {$nome}");
        }
        $this->info(count($criadas) . ' impressora(s) criada(s).');
        return 0;
    }
}
