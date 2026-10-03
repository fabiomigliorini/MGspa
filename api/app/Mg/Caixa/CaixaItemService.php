<?php

namespace Mg\Caixa;

use Carbon\Carbon;
use Illuminate\Database\QueryException;

/**
 * Cadastro dos itens do caixa (M13 doc-3): contas -> Cadastros -> Itens do
 * Caixa.
 */
class CaixaItemService
{
    public static function listar(array $filtros)
    {
        $q = CaixaItem::with([
            'Filial:codfilial,filial',
            'Pessoa:codpessoa,fantasia',
            'ContaContabil:codcontacontabil,contacontabil',
        ]);
        if (!empty($filtros['item'])) {
            $q->where('item', 'ilike', '%' . $filtros['item'] . '%');
        }
        if (!empty($filtros['codfilial'])) {
            $q->where(fn ($w) => $w->whereNull('codfilial')->orWhere('codfilial', $filtros['codfilial']));
        }
        $inativo = $filtros['inativo'] ?? null;
        if (in_array($inativo, [true, 'true', 1, '1'], true)) {
            $q->whereNotNull('inativo');
        } elseif (in_array($inativo, [false, 'false', 0, '0'], true)) {
            $q->whereNull('inativo');
        }
        return $q->orderBy('ordem')->orderBy('item')->get();
    }

    // itens ativos que aparecem na gaveta da filial (os de todas as
    // filiais e os dela)
    public static function ativosDaFilial(?int $codfilial)
    {
        return CaixaItem::whereNull('inativo')
            ->where(fn ($w) => $w->whereNull('codfilial')->orWhere('codfilial', $codfilial))
            ->orderBy('ordem')
            ->orderBy('item')
            ->get();
    }

    public static function salvar(CaixaItem $item, array $dados): CaixaItem
    {
        $item->fill($dados);
        $item->ordem = (int) ($dados['ordem'] ?? $item->ordem ?? 0);
        $item->save();
        return $item->fresh(['Filial', 'Pessoa', 'ContaContabil']);
    }

    public static function inativar(CaixaItem $item): CaixaItem
    {
        $item->inativo = Carbon::now();
        $item->save();
        return $item->fresh(['Filial', 'Pessoa', 'ContaContabil']);
    }

    public static function ativar(CaixaItem $item): CaixaItem
    {
        $item->inativo = null;
        $item->save();
        return $item->fresh(['Filial', 'Pessoa', 'ContaContabil']);
    }

    public static function excluir(CaixaItem $item): void
    {
        try {
            $item->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === '23503') {
                abort(409, 'Item já usado em caixa, não pode ser excluído. Inative ao invés de excluir.');
            }
            throw $e;
        }
    }
}
