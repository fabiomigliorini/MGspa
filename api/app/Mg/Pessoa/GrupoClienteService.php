<?php

namespace Mg\Pessoa;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use RuntimeException;

class GrupoClienteService
{
    public static function listar(array $filtros)
    {
        $q = GrupoCliente::query();

        if (!empty($filtros['codgrupocliente'])) {
            $q->where('codgrupocliente', $filtros['codgrupocliente']);
        }

        if (!empty($filtros['grupocliente'])) {
            $q->palavras('grupocliente', $filtros['grupocliente']);
        }

        switch ($filtros['status'] ?? 'ativos') {
            case 'inativos':
                $q->whereNotNull('inativo');
                break;
            case 'todos':
                break;
            case 'ativos':
            default:
                $q->whereNull('inativo');
                break;
        }

        $q->orderBy('grupocliente');

        if (!empty($filtros['todos_sem_paginacao'])) {
            return $q->get();
        }

        return $q->paginate(25);
    }

    public static function criar(array $dados): GrupoCliente
    {
        $grupo = GrupoCliente::create($dados);
        return $grupo->refresh();
    }

    public static function atualizar(GrupoCliente $grupo, array $dados): GrupoCliente
    {
        $grupo->fill($dados);
        $grupo->save();
        $grupo->refresh();
        return $grupo;
    }

    public static function inativar(GrupoCliente $grupo): GrupoCliente
    {
        $grupo->inativo = Carbon::now();
        $grupo->save();
        $grupo->refresh();
        return $grupo;
    }

    public static function ativar(GrupoCliente $grupo): GrupoCliente
    {
        $grupo->inativo = null;
        $grupo->save();
        $grupo->refresh();
        return $grupo;
    }

    public static function excluir(GrupoCliente $grupo): void
    {
        try {
            $grupo->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === '23503') {
                throw new RuntimeException('Grupo de Cliente em uso, não pode ser excluído. Inative ao invés de excluir.');
            }
            throw $e;
        }
    }
}
