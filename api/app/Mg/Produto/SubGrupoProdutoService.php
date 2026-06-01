<?php

namespace Mg\Produto;

use Mg\MgService;

class SubGrupoProdutoService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = SubGrupoProduto::query();

        if (!empty($filter['codsubgrupoproduto'])) {
            $qry->where('codsubgrupoproduto', $filter['codsubgrupoproduto']);
        }

        if (!empty($filter['codgrupoproduto'])) {
            $qry->where('codgrupoproduto', $filter['codgrupoproduto']);
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        if (!empty($filter['subgrupoproduto'])) {
            $qry->palavras('subgrupoproduto', $filter['subgrupoproduto']);
        }

        if (empty($sort)) {
            $qry->orderBy('subgrupoproduto');
        }

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    public static function autocompletar(array $params): array
    {
        $qry = static::pesquisar($params)
            ->select('codsubgrupoproduto', 'subgrupoproduto')
            ->take(30);
        $ret = [];
        foreach ($qry->get() as $item) {
            $ret[] = [
                'label' => $item->subgrupoproduto,
                'value' => $item->codsubgrupoproduto,
                'id' => $item->codsubgrupoproduto,
            ];
        }
        return $ret;
    }
}
