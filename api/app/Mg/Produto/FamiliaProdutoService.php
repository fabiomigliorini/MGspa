<?php

namespace Mg\Produto;

use Mg\MgService;

class FamiliaProdutoService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = FamiliaProduto::query();

        if (!empty($filter['codfamiliaproduto'])) {
            $qry->where('codfamiliaproduto', $filter['codfamiliaproduto']);
        }

        if (!empty($filter['codsecaoproduto'])) {
            $qry->where('codsecaoproduto', $filter['codsecaoproduto']);
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        if (!empty($filter['familiaproduto'])) {
            $qry->palavras('familiaproduto', $filter['familiaproduto']);
        }

        if (empty($sort)) {
            $qry->orderBy('familiaproduto');
        }

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    public static function autocompletar(array $params): array
    {
        $qry = static::pesquisar($params)
            ->select('codfamiliaproduto', 'familiaproduto')
            ->take(30);
        $ret = [];
        foreach ($qry->get() as $item) {
            $ret[] = [
                'label' => $item->familiaproduto,
                'value' => $item->codfamiliaproduto,
                'id' => $item->codfamiliaproduto,
            ];
        }
        return $ret;
    }
}
