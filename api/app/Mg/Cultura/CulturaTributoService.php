<?php

namespace Mg\Cultura;

use Mg\MgService;

class CulturaTributoService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = CulturaTributo::query()->with(['Tributo', 'UnidadeReferencia']);

        if (!empty($filter['codculturatributo'])) {
            $qry->where('codculturatributo', $filter['codculturatributo']);
        }

        if (!empty($filter['codcultura'])) {
            $qry->where('codcultura', $filter['codcultura']);
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        $qry = self::qryOrdem($qry, $sort ?: ['codcultura', 'ordem']);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }
}
