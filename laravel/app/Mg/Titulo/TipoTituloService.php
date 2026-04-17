<?php

namespace Mg\Titulo;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use RuntimeException;

class TipoTituloService
{

    const TIPO_PIX_RECEBER = 201;
    const TIPO_PIX_PAGAR = 930;

    const TIPO_ENTREGA_RECEBER = 310;
    const TIPO_ENTREGA_PAGAR = 320;

    public static function listar(array $filtros)
    {
        $q = TipoTitulo::with('TipoMovimentoTitulo');

        if (!empty($filtros['codtipotitulo'])) {
            $q->where('codtipotitulo', $filtros['codtipotitulo']);
        }

        if (!empty($filtros['tipotitulo'])) {
            $q->palavras('tipotitulo', $filtros['tipotitulo']);
        }

        if (!empty($filtros['codtipomovimentotitulo'])) {
            $q->where('codtipomovimentotitulo', $filtros['codtipomovimentotitulo']);
        }

        foreach (['pagar', 'receber', 'debito', 'credito'] as $flag) {
            if (array_key_exists($flag, $filtros) && $filtros[$flag] !== null && $filtros[$flag] !== '') {
                $q->where($flag, filter_var($filtros[$flag], FILTER_VALIDATE_BOOLEAN));
            }
        }

        if (array_key_exists('inativo', $filtros) && $filtros['inativo'] !== null && $filtros['inativo'] !== '') {
            if ($filtros['inativo'] === true || $filtros['inativo'] === 'true' || $filtros['inativo'] === 1 || $filtros['inativo'] === '1') {
                $q->whereNotNull('inativo');
            } else {
                $q->whereNull('inativo');
            }
        }

        $q->orderBy('tipotitulo');

        if (!empty($filtros['todos'])) {
            return $q->get();
        }

        return $q->paginate(25);
    }

    public static function criar(array $dados): TipoTitulo
    {
        $tipo = TipoTitulo::create($dados);
        $tipo->load('TipoMovimentoTitulo');
        return $tipo;
    }

    public static function atualizar(TipoTitulo $tipo, array $dados): TipoTitulo
    {
        $tipo->fill($dados);
        $tipo->save();
        $tipo->refresh();
        $tipo->load('TipoMovimentoTitulo');
        return $tipo;
    }

    public static function inativar(TipoTitulo $tipo): TipoTitulo
    {
        $tipo->inativo = Carbon::now();
        $tipo->save();
        $tipo->refresh();
        $tipo->load('TipoMovimentoTitulo');
        return $tipo;
    }

    public static function ativar(TipoTitulo $tipo): TipoTitulo
    {
        $tipo->inativo = null;
        $tipo->save();
        $tipo->refresh();
        $tipo->load('TipoMovimentoTitulo');
        return $tipo;
    }

    public static function excluir(TipoTitulo $tipo): void
    {
        try {
            $tipo->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === '23503') {
                throw new RuntimeException('Tipo de Título em uso, não pode ser excluído. Inative ao invés de excluir.');
            }
            throw $e;
        }
    }
}
