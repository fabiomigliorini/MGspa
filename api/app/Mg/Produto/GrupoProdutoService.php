<?php

namespace Mg\Produto;

use Mg\MgService;

class GrupoProdutoService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = GrupoProduto::query();

        if (!empty($filter['codgrupoproduto'])) {
            $qry->where('codgrupoproduto', $filter['codgrupoproduto']);
        }

        if (!empty($filter['codfamiliaproduto'])) {
            $qry->where('codfamiliaproduto', $filter['codfamiliaproduto']);
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        if (!empty($filter['grupoproduto'])) {
            $qry->palavras('grupoproduto', $filter['grupoproduto']);
        }

        if (empty($sort)) {
            $qry->orderBy('grupoproduto');
        }

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    public static function autocompletar(array $params): array
    {
        $qry = static::pesquisar($params)
            ->select('codgrupoproduto', 'grupoproduto')
            ->take(30);
        $ret = [];
        foreach ($qry->get() as $item) {
            $ret[] = [
                'label' => $item->grupoproduto,
                'value' => $item->codgrupoproduto,
                'id' => $item->codgrupoproduto,
            ];
        }
        return $ret;
    }
}
