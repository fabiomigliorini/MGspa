<?php

namespace Mg\Cultura;

use Mg\MgService;

class VariedadeService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = Variedade::query()->with('Cultura');

        if (!empty($filter['codvariedade'])) {
            $qry->where('codvariedade', $filter['codvariedade']);
        }

        if (!empty($filter['codcultura'])) {
            $qry->where('codcultura', $filter['codcultura']);
        }

        if (!empty($filter['variedade'])) {
            $qry->palavras('variedade', $filter['variedade']);
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        $qry = self::qryOrdem($qry, $sort ?: ['variedade']);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }
}
