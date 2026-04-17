<?php

namespace Mg\Banco;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use RuntimeException;

class BancoService
{
    public static function listar(array $filtros)
    {
        $q = Banco::query();

        if (!empty($filtros['codbanco'])) {
            $q->where('codbanco', $filtros['codbanco']);
        }

        if (!empty($filtros['banco'])) {
            $q->palavras('banco', $filtros['banco']);
        }

        if (!empty($filtros['sigla'])) {
            $q->where('sigla', 'ilike', "%{$filtros['sigla']}%");
        }

        if (!empty($filtros['numerobanco'])) {
            $q->where('numerobanco', $filtros['numerobanco']);
        }

        if (array_key_exists('inativo', $filtros) && $filtros['inativo'] !== null && $filtros['inativo'] !== '') {
            if ($filtros['inativo'] === true || $filtros['inativo'] === 'true' || $filtros['inativo'] === 1 || $filtros['inativo'] === '1') {
                $q->whereNotNull('inativo');
            } else {
                $q->whereNull('inativo');
            }
        }

        $q->orderBy('banco');

        if (!empty($filtros['todos'])) {
            return $q->get();
        }

        return $q->paginate(25);
    }

    public static function criar(array $dados): Banco
    {
        return Banco::create($dados);
    }

    public static function atualizar(Banco $banco, array $dados): Banco
    {
        $banco->fill($dados);
        $banco->save();
        return $banco->refresh();
    }

    public static function inativar(Banco $banco): Banco
    {
        $banco->inativo = Carbon::now();
        $banco->save();
        return $banco->refresh();
    }

    public static function ativar(Banco $banco): Banco
    {
        $banco->inativo = null;
        $banco->save();
        return $banco->refresh();
    }

    public static function excluir(Banco $banco): void
    {
        try {
            $banco->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === '23503') {
                throw new RuntimeException('Banco em uso, não pode ser excluído. Inative ao invés de excluir.');
            }
            throw $e;
        }
    }
}
