<?php

namespace Mg\FormaPagamento;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use RuntimeException;

class FormaPagamentoService
{
    public static function listar(array $filtros)
    {
        $q = FormaPagamento::query();

        if (!empty($filtros['codformapagamento'])) {
            $q->where('codformapagamento', $filtros['codformapagamento']);
        }

        if (!empty($filtros['formapagamento'])) {
            $q->palavras('formapagamento', $filtros['formapagamento']);
        }

        foreach (['avista', 'boleto', 'fechamento', 'notafiscal', 'entrega', 'valecompra', 'lio', 'pix', 'stone', 'integracao', 'safrapay'] as $flag) {
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

        $q->orderBy('formapagamento');

        if (!empty($filtros['todos'])) {
            return $q->get();
        }

        return $q->paginate(25);
    }

    public static function criar(array $dados): FormaPagamento
    {
        return FormaPagamento::create($dados);
    }

    public static function atualizar(FormaPagamento $forma, array $dados): FormaPagamento
    {
        $forma->fill($dados);
        $forma->save();
        return $forma->refresh();
    }

    public static function inativar(FormaPagamento $forma): FormaPagamento
    {
        $forma->inativo = Carbon::now();
        $forma->save();
        return $forma->refresh();
    }

    public static function ativar(FormaPagamento $forma): FormaPagamento
    {
        $forma->inativo = null;
        $forma->save();
        return $forma->refresh();
    }

    public static function excluir(FormaPagamento $forma): void
    {
        try {
            $forma->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === '23503') {
                throw new RuntimeException('Forma de Pagamento em uso, não pode ser excluída. Inative ao invés de excluir.');
            }
            throw $e;
        }
    }
}
