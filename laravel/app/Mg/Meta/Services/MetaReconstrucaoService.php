<?php

namespace Mg\Meta\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mg\Meta\BonificacaoEvento;
use Mg\Meta\Meta;
use Mg\Meta\MetaService;
use Mg\Negocio\Negocio;
use RuntimeException;

class MetaReconstrucaoService
{
    public static function reconciliarNegocio(int $codnegocio): void
    {
        $negocio = Negocio::findOrFail($codnegocio);

        $meta = static::resolverMeta($negocio);

        Log::info('MetaReconstrucaoService - reconciliarNegocio', [
            'codnegocio' => $codnegocio,
            'codmeta' => $meta->codmeta,
        ]);

        BonificacaoService::processarNegocio($codnegocio, $meta);
    }

    public static function reconciliarMeta(Meta $meta): void
    {
        static::validarStatus($meta);

        Log::info('MetaReconstrucaoService - reconciliarMeta iniciado', [
            'codmeta' => $meta->codmeta,
            'status' => $meta->status,
            'periodoinicial' => $meta->periodoinicial,
            'periodofinal' => $meta->periodofinal,
        ]);

        // Pluck somente IDs (inteiros) em vez de hidratar modelos Eloquent
        $codnegociosValidos = Negocio::query()
            ->join('tblnaturezaoperacao as nop', 'nop.codnaturezaoperacao', '=', 'tblnegocio.codnaturezaoperacao')
            ->where(function ($q) {
                $q->where('nop.venda', true)
                  ->orWhere('nop.vendadevolucao', true);
            })
            ->whereBetween('tblnegocio.lancamento', [
                $meta->periodoinicial->copy()->startOfDay(),
                $meta->periodofinal->copy()->endOfDay(),
            ])
            ->whereIn('tblnegocio.codnegociostatus', [
                BonificacaoService::STATUS_NEGOCIO_FECHADO,
                BonificacaoService::STATUS_NEGOCIO_CANCELADO,
            ])
            ->orderBy('tblnegocio.lancamento')
            ->orderBy('tblnegocio.codnegocio')
            ->pluck('tblnegocio.codnegocio')
            ->map(fn ($v) => intval($v))
            ->all();

        $totalNegocios = count($codnegociosValidos);

        // Processar em chunks — cada chunk em transação independente
        foreach (array_chunk($codnegociosValidos, 500) as $chunk) {
            DB::beginTransaction();
            foreach ($chunk as $codnegocio) {
                BonificacaoService::processarNegocio($codnegocio, $meta);
            }
            DB::commit();
            gc_collect_cycles();
        }

        $orfaosRemovidos = static::limparEventosOrfaos($meta, $codnegociosValidos);

        Log::info('MetaReconstrucaoService - reconciliarMeta concluido', [
            'codmeta' => $meta->codmeta,
            'negociosprocessados' => $totalNegocios,
            'orfaosremovidos' => $orfaosRemovidos,
        ]);
    }

    private static function resolverMeta(Negocio $negocio): Meta
    {
        if (empty($negocio->lancamento)) {
            throw new RuntimeException("Negocio {$negocio->codnegocio} sem data de lancamento.");
        }

        $meta = Meta::query()
            ->where('periodoinicial', '<=', $negocio->lancamento)
            ->where('periodofinal', '>=', $negocio->lancamento)
            ->where('status', '!=', MetaService::META_STATUS_FECHADA)
            ->first();

        if (empty($meta)) {
            throw new RuntimeException(
                "Nenhuma meta aberta/bloqueada encontrada para negocio {$negocio->codnegocio} "
                . "(lancamento: {$negocio->lancamento})."
            );
        }

        return $meta;
    }

    private static function limparEventosOrfaos(Meta $meta, array $codnegociosValidos): int
    {
        $query = BonificacaoEvento::query()
            ->where('codmeta', $meta->codmeta)
            ->where('manual', false)
            ->whereNotNull('codnegocio');

        if (!empty($codnegociosValidos)) {
            $query->whereNotIn('codnegocio', $codnegociosValidos);
        }

        $total = 0;

        $query->chunkById(500, function ($orfaos) use ($meta, &$total) {
            DB::beginTransaction();
            foreach ($orfaos as $orfao) {
                Log::info('MetaReconstrucaoService - Evento orfao removido', [
                    'codbonificacaoevento' => $orfao->codbonificacaoevento,
                    'codmeta' => $meta->codmeta,
                    'codnegocio' => $orfao->codnegocio,
                    'tipo' => $orfao->tipo,
                    'codpessoa' => $orfao->codpessoa,
                    'valor' => $orfao->valor,
                ]);

                $orfao->delete();
                $total++;
            }
            DB::commit();
        }, 'codbonificacaoevento');

        return $total;
    }

    private static function validarStatus(Meta $meta): void
    {
        if ($meta->status === MetaService::META_STATUS_FECHADA) {
            throw new RuntimeException("Meta {$meta->codmeta} com status Fechada (F) nao permite reprocessamento.");
        }
    }
}
