<?php

namespace Mg\Gerador;

use Illuminate\Console\Command;

class GeradorModelCommand extends Command
{
    protected $signature = 'gerador:model
        {tabela? : Nome da tabela. Opcional quando --all é usado}
        {--all : Itera por todas as tabelas do IndiceModels.json (bulk regenerate)}
        {--no-test : Pula os testes de count/instanciar/relacionamentos}';

    protected $description = 'Gera o codigo fonte de um model. Use --no-interaction (-n) pra rodar sem prompts, --no-test pra pular validações e --all pra regenerar tudo.';

    /**
     * Tabelas que NÃO podem ser regeneradas porque o model tem refatoração
     * manual incompatível com o template do gerador (ex.: auth model L13).
     */
    protected const SKIP_TABELAS = [
        'tblusuario' => 'auth model L13 (extends Model + AuthenticatableContract) — gerador transformaria em MgModel e quebraria o login',
    ];

    public function handle()
    {
        if ($this->option('all')) {
            return $this->handleAll();
        }

        $tabela = $this->argument('tabela');
        if (!$tabela) {
            $this->error('Informe a tabela ou use --all.');
            return 1;
        }

        if (isset(self::SKIP_TABELAS[$tabela])) {
            $this->warn("⊘ pulando {$tabela}: " . self::SKIP_TABELAS[$tabela]);
            return 0;
        }

        (new GeradorModelService($this))->gerar($tabela);
        return 0;
    }

    protected function handleAll(): int
    {
        $indice = base_path('app/Mg/Gerador/IndiceModels.json');
        if (!file_exists($indice)) {
            $this->error("IndiceModels.json não encontrado em {$indice}");
            return 1;
        }

        $tabelas = array_keys(json_decode(file_get_contents($indice), true) ?? []);
        $total = count($tabelas);
        $this->info("Regenerando {$total} models a partir do IndiceModels.json...");

        $erros = [];
        $pulados = [];
        $i = 0;
        $inicio = microtime(true);
        foreach ($tabelas as $tabela) {
            $i++;
            if (isset(self::SKIP_TABELAS[$tabela])) {
                $this->warn("[{$i}/{$total}] ⊘ {$tabela} pulado: " . self::SKIP_TABELAS[$tabela]);
                $pulados[] = $tabela;
                continue;
            }
            $this->line("[{$i}/{$total}] {$tabela}");
            try {
                (new GeradorModelService($this))->gerar($tabela);
            } catch (\Throwable $e) {
                $erros[$tabela] = $e->getMessage();
                $this->error("  ✗ {$e->getMessage()}");
            }
        }

        $tempo = round(microtime(true) - $inicio, 1);
        $processados = $total - count($erros) - count($pulados);
        $this->line('');
        $this->info("Concluído em {$tempo}s. {$processados} regenerados, " . count($pulados) . " pulados, " . count($erros) . " erros.");
        if ($pulados) {
            $this->warn(count($pulados) . ' pulados: ' . implode(', ', $pulados));
        }
        if ($erros) {
            $this->error(count($erros) . ' falhas:');
            foreach ($erros as $tabela => $msg) {
                $this->line("  - {$tabela}: {$msg}");
            }
            return 1;
        }
        return 0;
    }
}
