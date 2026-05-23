<?php

namespace Mg\Rh;

use Illuminate\Console\Command;

class RhReprocessarPeriodoCommand extends Command
{
    protected $signature = 'rh:reprocessar-periodo {codperiodo} {--limpar : Limpa lançamentos automáticos antes de reprocessar}';

    protected $description = 'Reprocessa todas as vendas de um período nos indicadores RH';

    public function handle()
    {
        $codperiodo = $this->argument('codperiodo');
        $limpar = $this->option('limpar');

        $periodo = Periodo::findOrFail($codperiodo);

        if ($periodo->status !== PeriodoService::STATUS_ABERTO) {
            $this->error("Período {$codperiodo} está fechado. Reabra antes de reprocessar.");
            $this->info("Use: php artisan tinker --execute=\"\\Mg\\Rh\\PeriodoService::reabrir({$codperiodo});\"");
            return 1;
        }

        $this->info("Período {$codperiodo}: {$periodo->periodoinicial} a {$periodo->periodofinal}");

        $bar = null;

        $resultado = ReprocessarPeriodoService::reprocessar(
            $codperiodo,
            $limpar,
            function (array $dados) use (&$bar) {
                if ($dados['etapa'] === 'limpando') {
                    $this->warn($dados['mensagem']);
                } elseif ($dados['etapa'] === 'processando') {
                    if (!$bar && $dados['total'] > 0) {
                        $this->info("Processando {$dados['total']} negócios...");
                        $bar = $this->output->createProgressBar($dados['total']);
                        $bar->start();
                    }
                    if ($bar) {
                        $bar->setProgress($dados['atual']);
                    }
                } elseif ($dados['etapa'] === 'rubricas') {
                    if ($bar) {
                        $bar->finish();
                        $this->newLine(2);
                    }
                    $this->info($dados['mensagem']);
                }
                return true;
            }
        );

        if ($bar && !$resultado['cancelado']) {
            // Caso rubricas não tenha sido chamado (cenário sem vendas)
        }

        $this->newLine();
        $this->info("Concluído! OK: {$resultado['ok']} | Erros: {$resultado['erros']}");
        $this->newLine();

        // Resumo indicadores por unidade
        $indicadores = Indicador::where('codperiodo', $codperiodo)
            ->where('tipo', 'U')
            ->get();

        $this->table(
            ['Unidade', 'Vendas'],
            $indicadores->map(function ($i) {
                $un = \Mg\Filial\UnidadeNegocio::find($i->codunidadenegocio);
                return [
                    $un->descricao ?? $i->codunidadenegocio,
                    'R$ ' . number_format($i->valoracumulado, 2, ',', '.'),
                ];
            })
        );

        $totais = Indicador::where('codperiodo', $codperiodo)
            ->selectRaw('tipo, COUNT(*) as qtd')
            ->groupBy('tipo')
            ->pluck('qtd', 'tipo');

        $this->info("Indicadores: U={$totais->get('U', 0)} S={$totais->get('S', 0)} V={$totais->get('V', 0)} C={$totais->get('C', 0)}");

        return 0;
    }
}
