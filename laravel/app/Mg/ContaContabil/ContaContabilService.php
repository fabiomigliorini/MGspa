<?php

namespace Mg\ContaContabil;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use RuntimeException;

class ContaContabilService
{
    public static function listar(array $filtros)
    {
        $q = ContaContabil::query();

        if (!empty($filtros['codcontacontabil'])) {
            $q->where('codcontacontabil', $filtros['codcontacontabil']);
        }

        if (!empty($filtros['contacontabil'])) {
            $q->palavras('contacontabil', $filtros['contacontabil']);
        }

        if (!empty($filtros['numero'])) {
            $q->where('numero', 'ilike', "%{$filtros['numero']}%");
        }

        if (array_key_exists('inativo', $filtros) && $filtros['inativo'] !== null && $filtros['inativo'] !== '') {
            if ($filtros['inativo'] === true || $filtros['inativo'] === 'true' || $filtros['inativo'] === 1 || $filtros['inativo'] === '1') {
                $q->whereNotNull('inativo');
            } else {
                $q->whereNull('inativo');
            }
        }

        $q->orderBy('contacontabil');

        return $q->paginate(25);
    }

    public static function criar(array $dados): ContaContabil
    {
        return ContaContabil::create($dados);
    }

    public static function atualizar(ContaContabil $conta, array $dados): ContaContabil
    {
        $conta->fill($dados);
        $conta->save();
        return $conta->refresh();
    }

    public static function inativar(ContaContabil $conta): ContaContabil
    {
        $conta->inativo = Carbon::now();
        $conta->save();
        return $conta->refresh();
    }

    public static function ativar(ContaContabil $conta): ContaContabil
    {
        $conta->inativo = null;
        $conta->save();
        return $conta->refresh();
    }

    public static function excluir(ContaContabil $conta): void
    {
        try {
            $conta->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === '23503') {
                throw new RuntimeException('Conta Contábil em uso, não pode ser excluída. Inative ao invés de excluir.');
            }
            throw $e;
        }
    }
}
