<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Apaga os .gz de conversa com a SEFAZ mais antigos que N dias.
 *
 * ATENCAO: este comando APAGA arquivo perto de registro fiscal. Por isso ele so opera
 * dentro de conversas/, e ha um guard explicito que RECUSA qualquer caminho que nao
 * contenha '/conversas/'. As arvores nfe/, nfce/, mdfe/, inutilizacao/ e dfe/ sao
 * obrigacao de guarda e NUNCA sao tocadas.
 *
 * E justamente por causa dessa diferenca de retencao (2 anos contra "para sempre") que as
 * conversas ficam em arvore separada, mesmo carregando a chave no nome do arquivo: assim
 * a protecao e estrutural, e nao depende de um padrao de nome estar certo.
 *
 * O metadado na tblsefazcomunicacao fica para sempre; so o XML expira. O endpoint de
 * download devolve 404 quando o arquivo ja saiu.
 */
class NFePHPLimparConversasCommand extends Command
{
    protected $signature = 'nfe-php:limpar-conversas
        {--dias=730 : idade minima para apagar}
        {--dry-run : so mostra o que faria}';

    protected $description = 'Apaga XMLs de conversa com a SEFAZ com mais de N dias (retencao padrao 2 anos)';

    public function handle()
    {
        $dias = (int) $this->option('dias');
        $dryRun = (bool) $this->option('dry-run');
        $raiz = rtrim(config('mg.paths.nfe_php'), '/') . '/conversas';

        if (!is_dir($raiz)) {
            $this->info('Nada a fazer: a pasta de conversas ainda não existe.');
            return 0;
        }

        $corte = now()->subDays($dias)->getTimestamp();
        $apagados = 0;
        $bytes = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($raiz, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $arquivo) {
            if (!$arquivo->isFile()) {
                continue;
            }

            $path = $arquivo->getPathname();

            // GUARD: nunca apagar fora de conversas/. Defesa contra symlink, bug de
            // montagem de caminho ou alguem mudar a raiz sem pensar.
            if (!str_contains($path, '/conversas/')) {
                Log::error("limpar-conversas: RECUSADO caminho fora de conversas/: {$path}");
                continue;
            }

            if ($arquivo->getMTime() >= $corte) {
                continue;
            }

            $tamanho = $arquivo->getSize();
            if ($dryRun) {
                $this->line("  apagaria {$path}");
            } elseif (!@unlink($path)) {
                Log::warning("limpar-conversas: falha ao apagar {$path}");
                continue;
            }

            $apagados++;
            $bytes += $tamanho;
        }

        $this->info(sprintf(
            '%s%d arquivos (%.1f MB) com mais de %d dias.',
            $dryRun ? '[dry-run] ' : 'Apagados ',
            $apagados,
            $bytes / 1048576,
            $dias
        ));

        return 0;
    }
}
