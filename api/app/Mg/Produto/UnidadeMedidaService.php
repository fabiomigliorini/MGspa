<?php

namespace Mg\Produto;

use Mg\MgService;

class UnidadeMedidaService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = UnidadeMedida::query();

        if (!empty($filter['codunidademedida'])) {
            $qry->where('codunidademedida', $filter['codunidademedida']);
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        if (!empty($filter['unidademedida'])) {
            $qry->palavras('unidademedida', $filter['unidademedida']);
        }

        if (!empty($filter['sigla'])) {
            $qry->where('sigla', 'ilike', "%{$filter['sigla']}%");
        }

        if (empty($sort)) {
            $qry->orderBy('unidademedida');
        }

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    public static function autocompletar(array $params): array
    {
        $qry = static::pesquisar($params)
            ->select('codunidademedida', 'unidademedida', 'sigla')
            ->take(20);
        $ret = [];
        foreach ($qry->get() as $item) {
            $ret[] = [
                'label' => "{$item->unidademedida} ({$item->sigla})",
                'value' => $item->codunidademedida,
                'id' => $item->codunidademedida,
                'sigla' => $item->sigla,
            ];
        }
        return $ret;
    }
}
