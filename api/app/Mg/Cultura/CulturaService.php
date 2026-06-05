<?php

namespace Mg\Cultura;

use Mg\MgService;

class CulturaService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = Cultura::query();

        if (!empty($filter['codcultura'])) {
            $qry->where('codcultura', $filter['codcultura']);
        }

        if (!empty($filter['cultura'])) {
            $qry->palavras('cultura', $filter['cultura']);
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        $qry = self::qryOrdem($qry, $sort ?: ['cultura']);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }
}
