<?php

namespace Mg\Rh;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioService;

class RhReprocessarPeriodoCommand extends Command
{
    protected $signature = 'rh:reprocessar-periodo {codperiodo} {--limpar : Limpa indicadores e lançamentos antes de reprocessar}';

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

        // Limpar indicadores se solicitado
        if ($limpar) {
            $this->warn('Limpando lançamentos e resetando indicadores...');

            $lancamentos = DB::table('tblindicadorlancamento')
                ->whereIn('codindicador', function ($q) use ($codperiodo) {
                    $q->select('codindicador')->from('tblindicador')->where('codperiodo', $codperiodo);
                })->delete();

            $this->info("  {$lancamentos} lançamentos removidos");

            $indicadores = Indicador::where('codperiodo', $codperiodo)->update(['valoracumulado' => 0]);

            $this->info("  {$indicadores} indicadores resetados");
        }

        // Buscar vendas do período (fechadas e canceladas)
        $negocios = Negocio::whereIn('codnegociostatus', [NegocioService::STATUS_FECHADO, NegocioService::STATUS_CANCELADO])
            ->where('lancamento', '>=', $periodo->periodoinicial)
            ->where('lancamento', '<=', $periodo->periodofinal)
            ->pluck('codnegocio');

        $total = $negocios->count();
        $this->info("Processando {$total} negócios...");

        $ok = 0;
        $erros = 0;
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($negocios as $codnegocio) {
            try {
                ProcessarVendaService::processar($codnegocio);
                $ok++;
            } catch (\Exception $e) {
                $erros++;
                if ($erros <= 10) {
                    $bar->clear();
                    $this->error("  Erro negócio {$codnegocio}: {$e->getMessage()}");
                    $bar->display();
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Resumo indicadores
        $this->info("Concluído! OK: {$ok} | Erros: {$erros}");
        $this->newLine();

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
