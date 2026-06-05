<?php

namespace Mg\Safra;

use Mg\MgService;

class SafraService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = Safra::query()->with('Cultura');

        if (!empty($filter['codsafra'])) {
            $qry->where('codsafra', $filter['codsafra']);
        }

        if (!empty($filter['codcultura'])) {
            $qry->where('codcultura', $filter['codcultura']);
        }

        if (!empty($filter['safra'])) {
            $qry->palavras('safra', $filter['safra']);
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        $qry = self::qryOrdem($qry, $sort ?: ['-datainicio', 'safra']);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }
}
