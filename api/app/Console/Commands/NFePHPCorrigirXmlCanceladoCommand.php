<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use NFePHP\NFe\Complements;

/**
 * Reconstroi os {chave}-cancelado.xml que foram gravados no formato errado.
 *
 * O QUE ESTAVA ERRADO
 *
 * O vincularProtocoloCancelamento tinha dois caminhos. Quando o cancelamento vinha do botao
 * Cancelar, ele gravava o nfeProc autorizado com o <retEvento> anexado — correto. Quando
 * vinha de uma consulta de chave (nota cancelada por fora, ou simplesmente um Consultar
 * rodado depois), ele gravava a resposta CRUA do webservice: o envelope SOAP inteiro do
 * retConsSitNFe, que nao tem <infNFe> dentro. E como a consulta roda depois do cancelamento,
 * ela sobrescrevia o arquivo bom.
 *
 * O estrago e duplo: a DANFE nao consegue renderizar esse arquivo (por isso nota cancelada
 * saia sem a tarja CANCELADA), e o endpoint /xml entrega o envelope de consulta ao contador
 * no lugar do documento fiscal.
 *
 * COMO A RECONSTRUCAO FUNCIONA
 *
 * Nada e inventado: o envelope SOAP JA CONTEM o <retEvento> verdadeiro assinado pela SEFAZ.
 * O Complements::cancelRegister so procura um <retEvento> com chNFe casando e o anexa ao
 * nfeProc — e ele digere tanto o retEnvEvento quanto o retConsSitNFe. Entao basta reprocessar
 * o proprio arquivo ruim junto com o -proc.xml irmao.
 *
 * SEGURANCA
 *
 * Sao arquivos fiscais. O comando so escreve depois de conferir que o resultado ficou melhor
 * que a origem (tem <infNFe> e <retEvento>), sempre guarda um .bak, e recusa qualquer caminho
 * fora das arvores nfe/ e nfce/.
 */
class NFePHPCorrigirXmlCanceladoCommand extends Command
{
    protected $signature = 'nfe-php:corrigir-xml-cancelado
        {--dry-run : so mostra o que faria, sem escrever}
        {--limit=0 : para depois de N arquivos corrigidos (0 = sem limite)}
        {--sem-backup : nao gera o .bak (use so em reprocessamento)}';

    protected $description = 'Reconstroi os XMLs de cancelamento gravados como envelope SOAP da consulta';

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $backup = !$this->option('sem-backup');
        $raiz = rtrim(config('mg.paths.nfe_php'), '/');

        if (!is_dir($raiz)) {
            $this->error("Raiz inexistente: {$raiz}");
            return 1;
        }

        $stats = [
            'varridos' => 0,
            'ja_completos' => 0,
            'corrigidos' => 0,
            'sem_proc' => 0,
            'sem_evento' => 0,
            'erro' => 0,
        ];

        // Em producao sao dezenas de milhares de arquivos: listar um por um afoga o terminal.
        // O detalhe fica atras de -v; sem ele, so o progresso e as anomalias (que sao poucas).
        $verbose = $this->output->isVerbose();

        foreach ($this->arquivosCancelados($raiz) as $path) {
            $stats['varridos']++;

            if ($stats['varridos'] % 500 === 0) {
                $this->line(sprintf(
                    '  ... %d varridos, %d corrigidos',
                    $stats['varridos'],
                    $stats['corrigidos']
                ));
            }

            $atual = @file_get_contents($path);
            if ($atual === false) {
                $stats['erro']++;
                Log::warning("corrigir-xml-cancelado: falha ao ler {$path}");
                continue;
            }

            // Ja tem a nota dentro: nada a fazer.
            if (str_contains($atual, '<infNFe')) {
                $stats['ja_completos']++;
                continue;
            }

            $pathProc = preg_replace('/-cancelado\.xml$/', '-proc.xml', $path);
            if (!is_file($pathProc)) {
                $stats['sem_proc']++;
                $this->line("  <comment>sem -proc.xml</comment> {$path}");
                continue;
            }

            try {
                $montado = Complements::cancelRegister(file_get_contents($pathProc), $atual);
            } catch (\Throwable $e) {
                $stats['erro']++;
                Log::warning("corrigir-xml-cancelado: {$path}: " . $e->getMessage());
                continue;
            }

            // O cancelRegister devolve o nfeProc INALTERADO quando nao acha um retEvento
            // casando (ex: cStat 101, que ele nao aceita). Gravar isso trocaria um arquivo
            // ruim por outro sem o evento — pior, porque parece bom.
            if (!str_contains($montado, '<infNFe') || !str_contains($montado, '<retEvento')) {
                $stats['sem_evento']++;
                $this->line("  <comment>sem retEvento aproveitavel</comment> {$path}");
                continue;
            }

            if ($dryRun) {
                $this->line("  <info>corrigiria</info> {$path}");
            } else {
                if ($backup && !@copy($path, $path . '.bak')) {
                    $stats['erro']++;
                    Log::warning("corrigir-xml-cancelado: falha ao gerar .bak de {$path}");
                    continue;
                }
                if (@file_put_contents($path, $montado) === false) {
                    $stats['erro']++;
                    Log::warning("corrigir-xml-cancelado: falha ao gravar {$path}");
                    continue;
                }
            }

            $stats['corrigidos']++;
            if ($limit > 0 && $stats['corrigidos'] >= $limit) {
                $this->warn("Limite de {$limit} atingido, parando.");
                break;
            }
        }

        $this->newLine();
        $this->info($dryRun ? '[dry-run] nada foi escrito' : 'Concluido');
        foreach ($stats as $chave => $valor) {
            $this->line(sprintf('  %-14s %d', str_replace('_', ' ', $chave), $valor));
        }

        return 0;
    }

    /**
     * Varre so as arvores de NFe/NFC-e. O guard de caminho e estrutural: mesmo que a raiz
     * venha errada da config, nada fora de nfe/ e nfce/ e tocado.
     */
    private function arquivosCancelados(string $raiz): \Generator
    {
        foreach (['nfe', 'nfce'] as $tipo) {
            $base = "{$raiz}/{$tipo}";
            if (!is_dir($base)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $arquivo) {
                if (!$arquivo->isFile() || !str_ends_with($arquivo->getFilename(), '-cancelado.xml')) {
                    continue;
                }

                $path = $arquivo->getPathname();
                if (!str_contains($path, "/{$tipo}/")) {
                    Log::error("corrigir-xml-cancelado: RECUSADO caminho fora de {$tipo}/: {$path}");
                    continue;
                }

                yield $path;
            }
        }
    }
}
