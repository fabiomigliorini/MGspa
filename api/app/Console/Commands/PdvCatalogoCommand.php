<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mg\Pdv\PdvCatalogoService;

/**
 * Catálogo do PDV (TASK-197): a cada rodada gera o json de produtos e pessoas em
 * storage/app/pdv-catalogo, sempre, e o diff só do que mudou. É o que a sincronização
 * incremental do negocios consome. Agendado no routes/console.php.
 */
class PdvCatalogoCommand extends Command
{
    protected $signature = 'pdv:catalogo';

    protected $description = 'Gera a versão do catálogo do PDV (json e diff de produtos e pessoas) para a sincronização incremental';

    public function handle()
    {
        $manifesto = PdvCatalogoService::gerar();
        foreach ($manifesto['tabelas'] as $tabela => $t) {
            $this->info(sprintf(
                '%s: %d registros, %d alterados, %d excluídos',
                $tabela,
                $t['registros'],
                $t['alterados'],
                $t['excluidos']
            ));
        }
        $this->info("Versão {$manifesto['versao']} gerada em {$manifesto['segundos']} s.");
        return 0;
    }
}
