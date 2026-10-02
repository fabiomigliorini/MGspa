<?php

namespace Mg\Titulo;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use RuntimeException;

class TipoTituloService
{
    const TIPO_PIX_RECEBER = 101;
    const TIPO_PIX_PAGAR = 201;
    const TIPO_ENTREGA_RECEBER = 102;
    const TIPO_ENTREGA_PAGAR = 202;

    public static function listar(array $filtros)
    {
        $q = TipoTitulo::query();

        if (!empty($filtros['codtipotitulo'])) {
            $q->where('codtipotitulo', $filtros['codtipotitulo']);
        }
        if (!empty($filtros['tipotitulo'])) {
            $q->palavras('tipotitulo', $filtros['tipotitulo']);
        }
        if (!empty($filtros['natureza'])) {
            $q->where('natureza', $filtros['natureza']);
        }

        foreach (['pagar', 'receber', 'movimentaportador'] as $flag) {
            if (array_key_exists($flag, $filtros) && $filtros[$flag] !== null && $filtros[$flag] !== '') {
                $q->where($flag, filter_var($filtros[$flag], FILTER_VALIDATE_BOOLEAN));
            }
        }

        if (array_key_exists('inativo', $filtros) && $filtros['inativo'] !== null && $filtros['inativo'] !== '') {
            if (in_array($filtros['inativo'], [true, 'true', 1, '1'], true)) {
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
        return TipoTitulo::create($dados);
    }

    public static function atualizar(TipoTitulo $tipo, array $dados): TipoTitulo
    {
        $tipo->fill($dados);
        $tipo->save();
        $tipo->refresh();
        return $tipo;
    }

    public static function inativar(TipoTitulo $tipo): TipoTitulo
    {
        $tipo->inativo = Carbon::now();
        $tipo->save();
        $tipo->refresh();
        return $tipo;
    }

    public static function ativar(TipoTitulo $tipo): TipoTitulo
    {
        $tipo->inativo = null;
        $tipo->save();
        $tipo->refresh();
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
