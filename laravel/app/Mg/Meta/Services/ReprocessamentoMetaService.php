<?php

namespace Mg\Meta\Services;

use Illuminate\Support\Facades\Log;
use Mg\Meta\BonificacaoEvento;
use Mg\Meta\Meta;
use Mg\Negocio\Negocio;
use RuntimeException;

class ReprocessamentoMetaService
{
    public const STATUS_META_ABERTA = 'A';
    public const STATUS_META_BLOQUEADA = 'B';

    public static function reprocessar(Meta $meta): void
    {
        static::validarStatusReprocessamento($meta);

        Log::info('ReprocessamentoMetaService - Reprocessamento iniciado', [
            'codmeta' => $meta->codmeta,
            'status' => $meta->status,
            'periodoinicial' => $meta->periodoinicial,
            'periodofinal' => $meta->periodofinal,
        ]);

        $totalEventosApagados = BonificacaoEvento::query()
            ->where('codmeta', $meta->codmeta)
            ->where('manual', false)
            ->delete();

        $negocios = Negocio::query()
            ->whereBetween('lancamento', [
                $meta->periodoinicial->copy()->startOfDay(),
                $meta->periodofinal->copy()->endOfDay(),
            ])
            ->orderBy('lancamento')
            ->orderBy('codnegocio')
            ->get(['codnegocio']);

        foreach ($negocios as $negocio) {
            BonificacaoService::processarNegocio(intval($negocio->codnegocio), $meta);
        }

        Log::info('ReprocessamentoMetaService - Reprocessamento concluido', [
            'codmeta' => $meta->codmeta,
            'eventosapagados' => $totalEventosApagados,
            'negociosprocessados' => $negocios->count(),
        ]);
    }

    private static function validarStatusReprocessamento(Meta $meta): void
    {
        if (in_array($meta->status, [static::STATUS_META_ABERTA, static::STATUS_META_BLOQUEADA])) {
            return;
        }

        throw new RuntimeException("Meta {$meta->codmeta} com status {$meta->status} nao permite reprocessamento.");
    }
}
