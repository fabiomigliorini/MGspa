<?php

namespace Mg\Fazenda;

use Mg\MgService;

class TalhaoService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = Talhao::query()->with('Fazenda');

        if (!empty($filter['codtalhao'])) {
            $qry->where('codtalhao', $filter['codtalhao']);
        }

        if (!empty($filter['codfazenda'])) {
            $qry->where('codfazenda', $filter['codfazenda']);
        }

        if (!empty($filter['talhao'])) {
            $qry->palavras('talhao', $filter['talhao']);
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        $qry = self::qryOrdem($qry, $sort ?: ['talhao']);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }
}
