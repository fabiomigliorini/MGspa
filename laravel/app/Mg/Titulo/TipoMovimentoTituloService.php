<?php

namespace Mg\Titulo;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use RuntimeException;

class TipoMovimentoTituloService
{
    public static function listar(array $filtros)
    {
        $q = TipoMovimentoTitulo::query();

        if (!empty($filtros['codtipomovimentotitulo'])) {
            $q->where('codtipomovimentotitulo', $filtros['codtipomovimentotitulo']);
        }

        if (!empty($filtros['tipomovimentotitulo'])) {
            $q->palavras('tipomovimentotitulo', $filtros['tipomovimentotitulo']);
        }

        foreach (['implantacao', 'ajuste', 'armotizacao', 'juros', 'desconto', 'pagamento', 'estorno'] as $flag) {
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

        $q->orderBy('tipomovimentotitulo');

        if (!empty($filtros['todos'])) {
            return $q->get();
        }

        return $q->paginate(25);
    }

    public static function criar(array $dados): TipoMovimentoTitulo
    {
        return TipoMovimentoTitulo::create($dados);
    }

    public static function atualizar(TipoMovimentoTitulo $tipo, array $dados): TipoMovimentoTitulo
    {
        $tipo->fill($dados);
        $tipo->save();
        return $tipo->refresh();
    }

    public static function inativar(TipoMovimentoTitulo $tipo): TipoMovimentoTitulo
    {
        $tipo->inativo = Carbon::now();
        $tipo->save();
        return $tipo->refresh();
    }

    public static function ativar(TipoMovimentoTitulo $tipo): TipoMovimentoTitulo
    {
        $tipo->inativo = null;
        $tipo->save();
        return $tipo->refresh();
    }

    public static function excluir(TipoMovimentoTitulo $tipo): void
    {
        try {
            $tipo->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === '23503') {
                throw new RuntimeException('Tipo em uso, não pode ser excluído. Inative ao invés de excluir.');
            }
            throw $e;
        }
    }
}
