<?php

namespace Mg\Classificacao;

use Mg\MgService;

class ParametroClassificacaoService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = ParametroClassificacao::query();

        if (!empty($filter['codparametroclassificacao'])) {
            $qry->where('codparametroclassificacao', $filter['codparametroclassificacao']);
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

        $qry = self::qryOrdem($qry, $sort ?: ['parametroclassificacao']);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }
}
