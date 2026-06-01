<?php

namespace Mg\Produto;

use Mg\MgService;

class SecaoProdutoService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = SecaoProduto::query();

        if (!empty($filter['codsecaoproduto'])) {
            $qry->where('codsecaoproduto', $filter['codsecaoproduto']);
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        if (!empty($filter['secaoproduto'])) {
            $qry->palavras('secaoproduto', $filter['secaoproduto']);
        }

        if (empty($sort)) {
            $qry->orderBy('secaoproduto');
        }

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    public static function autocompletar(array $params): array
    {
        $qry = static::pesquisar($params)
            ->select('codsecaoproduto', 'secaoproduto')
            ->take(30);
        $ret = [];
        foreach ($qry->get() as $item) {
            $ret[] = [
                'label' => $item->secaoproduto,
                'value' => $item->codsecaoproduto,
                'id' => $item->codsecaoproduto,
            ];
        }
        return $ret;
    }
}
