<?php

namespace Mg\Produto;

use Mg\MgService;

class TipoProdutoService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = TipoProduto::query();

        if (!empty($filter['codtipoproduto'])) {
            $qry->where('codtipoproduto', $filter['codtipoproduto']);
        }

        if (!empty($filter['tipoproduto'])) {
            $qry->palavras('tipoproduto', $filter['tipoproduto']);
        }

        if (empty($sort)) {
            $qry->orderBy('tipoproduto');
        }

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    public static function autocompletar(array $params): array
    {
        $qry = static::pesquisar($params)
            ->select('codtipoproduto', 'tipoproduto')
            ->take(20);
        $ret = [];
        foreach ($qry->get() as $item) {
            $ret[] = [
                'label' => $item->tipoproduto,
                'value' => $item->codtipoproduto,
                'id' => $item->codtipoproduto,
            ];
        }
        return $ret;
    }
}
