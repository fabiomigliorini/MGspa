<?php

namespace Mg\Classificacao;

use Mg\MgService;

class ParametroClassificacaoService extends MgService
{
    const WITH = ['Cultura'];

    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = ParametroClassificacao::query()->with(static::WITH);

        if (!empty($filter['codparametroclassificacao'])) {
            $qry->where('codparametroclassificacao', $filter['codparametroclassificacao']);
        }

        if (!empty($filter['codcultura'])) {
            $qry->where('codcultura', $filter['codcultura']);
        }

        if (!empty($filter['metodo'])) {
            $qry->where('metodo', $filter['metodo']);
        }

        if (!empty($filter['parametroclassificacao'])) {
            $qry->palavras('parametroclassificacao', $filter['parametroclassificacao']);
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        // Ordem da CASCATA é a ordenação natural: o desconto de um parâmetro com
        // reduzbase muda a base do seguinte, então listar fora de ordem esconde o
        // efeito. `codcultura` na frente mantém as culturas agrupadas na listagem.
        $qry = self::qryOrdem($qry, $sort ?: ['codcultura', 'ordem', 'parametroclassificacao']);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    /** Parâmetros ATIVOS de uma cultura, na ordem da cascata — o que o cálculo consome. */
    public static function daCultura(?int $codcultura)
    {
        if (empty($codcultura)) {
            return collect();
        }
        return ParametroClassificacao::where('codcultura', $codcultura)
            ->whereNull('inativo')
            ->orderBy('ordem')
            ->get();
    }
}
