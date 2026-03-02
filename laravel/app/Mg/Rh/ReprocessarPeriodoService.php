<?php

namespace Mg\Rh;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioService;

class ReprocessarPeriodoService
{
    /**
     * Reprocessa todas as vendas de um período nos indicadores.
     * Atualiza o cache de progresso automaticamente (funciona via Job, Command ou HTTP).
     *
     * @param int $codperiodo
     * @param bool $limpar Limpar lançamentos automáticos antes de reprocessar
     * @param callable|null $onProgresso fn(array $dados): bool — retorna false para cancelar
     * @return array ['ok' => int, 'erros' => int, 'total' => int, 'cancelado' => bool]
     */
    public static function reprocessar(int $codperiodo, bool $limpar = false, ?callable $onProgresso = null): array
    {
        $cacheKey = "rh:reprocessar:{$codperiodo}";

        // Atualiza cache E chama callback externo (CLI progress bar, etc.)
        $progresso = function (array $dados) use ($cacheKey, $onProgresso): bool {
            static::atualizarCacheProgresso($cacheKey, $dados);

            if ($onProgresso) {
                return $onProgresso($dados) !== false;
            }
            return true;
        };

        // Iniciar
        static::atualizarCache($cacheKey, [
            'status' => 'processando',
            'progresso' => 0,
            'total' => 0,
            'atual' => 0,
            'ok' => 0,
            'erros' => 0,
            'mensagem' => 'Iniciando...',
            'inicio' => now()->toIso8601String(),
        ]);

        try {
            $periodo = Periodo::findOrFail($codperiodo);

            // Limpar lançamentos automáticos se solicitado (preserva manuais)
            if ($limpar) {
                if (!$progresso(['etapa' => 'limpando', 'mensagem' => 'Limpando lançamentos automáticos...'])) {
                    return static::finalizar($cacheKey, 0, 0, 0, true);
                }

                DB::table('tblindicadorlancamento')
                    ->whereIn('codindicador', function ($q) use ($codperiodo) {
                        $q->select('codindicador')->from('tblindicador')->where('codperiodo', $codperiodo);
                    })
                    ->where('manual', false)
                    ->delete();

                // Recalcular valoracumulado apenas com manuais restantes
                $indicadores = Indicador::where('codperiodo', $codperiodo)->get();
                foreach ($indicadores as $indicador) {
                    $somaManual = $indicador->IndicadorLancamentoS()->where('manual', true)->sum('valor');
                    $indicador->valoracumulado = $somaManual;
                    $indicador->save();
                }
            }

            // Buscar vendas do período (fechadas e canceladas)
            $negocios = Negocio::whereIn('codnegociostatus', [NegocioService::STATUS_FECHADO, NegocioService::STATUS_CANCELADO])
                ->where('lancamento', '>=', $periodo->periodoinicial)
                ->where('lancamento', '<=', $periodo->periodofinal)
                ->pluck('codnegocio');

            $total = $negocios->count();
            $ok = 0;
            $erros = 0;

            $progresso([
                'etapa' => 'processando',
                'atual' => 0,
                'total' => $total,
                'ok' => 0,
                'erros' => 0,
            ]);

            foreach ($negocios as $i => $codnegocio) {
                try {
                    ProcessarVendaService::processar($codnegocio);
                    $ok++;
                } catch (\Exception $e) {
                    $erros++;
                }

                if (($i + 1) % 50 === 0 || $i === $total - 1) {
                    // Verificar cancelamento
                    if (static::foiCancelado($cacheKey)) {
                        return static::finalizar($cacheKey, $ok, $erros, $total, true);
                    }

                    $continuar = $progresso([
                        'etapa' => 'processando',
                        'atual' => $i + 1,
                        'total' => $total,
                        'ok' => $ok,
                        'erros' => $erros,
                    ]);
                    if (!$continuar) {
                        return static::finalizar($cacheKey, $ok, $erros, $total, true);
                    }
                }
            }

            // Recalcular rubricas
            $progresso(['etapa' => 'rubricas', 'mensagem' => 'Recalculando rubricas...']);
            CalculoRubricaService::calcular($codperiodo);

            return static::finalizar($cacheKey, $ok, $erros, $total, false);
        } catch (\Throwable $e) {
            static::atualizarCache($cacheKey, [
                'status' => 'erro',
                'progresso' => 0,
                'mensagem' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private static function atualizarCacheProgresso(string $cacheKey, array $dados): void
    {
        if ($dados['etapa'] === 'limpando') {
            static::atualizarCache($cacheKey, ['mensagem' => $dados['mensagem']]);
        } elseif ($dados['etapa'] === 'processando') {
            $total = $dados['total'];
            $atual = $dados['atual'];
            static::atualizarCache($cacheKey, [
                'progresso' => $total > 0 ? (int) round($atual / $total * 95) : 0,
                'total' => $total,
                'atual' => $atual,
                'ok' => $dados['ok'],
                'erros' => $dados['erros'],
                'mensagem' => "Processando vendas... {$atual}/{$total} ({$dados['ok']} ok, {$dados['erros']} erros)",
            ]);
        } elseif ($dados['etapa'] === 'rubricas') {
            static::atualizarCache($cacheKey, [
                'progresso' => 98,
                'mensagem' => $dados['mensagem'],
            ]);
        }
    }

    private static function finalizar(string $cacheKey, int $ok, int $erros, int $total, bool $cancelado): array
    {
        if ($cancelado) {
            static::atualizarCache($cacheKey, [
                'status' => 'cancelado',
                'mensagem' => 'Cancelado pelo usuário',
            ]);
        } else {
            static::atualizarCache($cacheKey, [
                'status' => 'concluido',
                'progresso' => 100,
                'total' => $total,
                'atual' => $total,
                'ok' => $ok,
                'erros' => $erros,
                'mensagem' => "Concluído! {$ok} processados, {$erros} erros",
            ]);
        }

        return ['ok' => $ok, 'erros' => $erros, 'total' => $total, 'cancelado' => $cancelado];
    }

    private static function atualizarCache(string $cacheKey, array $dados): void
    {
        $atual = Cache::get($cacheKey, []);
        Cache::put($cacheKey, array_merge($atual, $dados), 3600);
    }

    private static function foiCancelado(string $cacheKey): bool
    {
        $cache = Cache::get($cacheKey);
        return $cache && ($cache['status'] ?? null) === 'cancelado';
    }
}
