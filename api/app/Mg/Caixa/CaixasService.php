<?php

namespace Mg\Caixa;

use Carbon\Carbon;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoListaService;
use Mg\Pagamento\PagamentoService;

/**
 * Listagem das transferencias entre portadores (M11 doc-3). Os portadores
 * com saldo, sessao e pendencias foram para o painel /portador (doc-4).
 */
class CaixasService
{
    // transferencias de/para os portadores da filial: as a confirmar
    // sempre; as demais no periodo
    public static function transferencias(array $filtros)
    {
        $q = Pagamento::query()
            ->with(array_merge(PagamentoListaService::RELACOES, [
                'PortadorOrigem.Filial:codfilial,filial',
                'PortadorDestino.Filial:codfilial,filial',
            ]))
            ->whereNull('codnegocio')
            ->whereNotNull('codportadororigem')
            ->whereNotNull('codportadordestino')
            ->whereNotExists(fn ($e) => $e->selectRaw('1')->from('tblmovimentotitulo as mt')
                ->whereColumn('mt.codpagamento', 'tblpagamento.codpagamento'));
        if (!empty($filtros['codfilial'])) {
            $q->where(fn ($w) => $w
                ->whereIn('codportadororigem', fn ($s) => $s->select('codportador')->from('tblportador')->where('codfilial', $filtros['codfilial']))
                ->orWhereIn('codportadordestino', fn ($s) => $s->select('codportador')->from('tblportador')->where('codfilial', $filtros['codfilial'])));
        }
        if (!empty($filtros['codportador'])) {
            $q->where(fn ($w) => $w->where('codportadororigem', $filtros['codportador'])
                ->orWhere('codportadordestino', $filtros['codportador']));
        }
        if (($filtros['estado'] ?? null) == PagamentoService::ESTADO_PENDENTE) {
            $q->where('estado', PagamentoService::ESTADO_PENDENTE);
        } else {
            if (!empty($filtros['estado'])) {
                $q->where('estado', $filtros['estado']);
            }
            if (!empty($filtros['transacao_de'])) {
                $q->where('transacao', '>=', Carbon::parse($filtros['transacao_de'])->startOfDay());
            }
            if (!empty($filtros['transacao_ate'])) {
                $q->where('transacao', '<=', Carbon::parse($filtros['transacao_ate'])->endOfDay());
            }
        }
        return $q->orderBy('transacao', 'desc')->orderBy('codpagamento', 'desc')->paginate(50);
    }
}
