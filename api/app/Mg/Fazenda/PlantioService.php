<?php

namespace Mg\Fazenda;

use Mg\MgService;

class PlantioService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = Plantio::query()->with(['Safra.Cultura', 'Fazenda', 'Variedade']);

        if (!empty($filter['codplantio'])) {
            $qry->where('codplantio', $filter['codplantio']);
        }

        if (!empty($filter['codsafra'])) {
            $qry->where('codsafra', $filter['codsafra']);
        }

        if (!empty($filter['codfazenda'])) {
            $qry->where('codfazenda', $filter['codfazenda']);
        }

        if (!empty($filter['codvariedade'])) {
            $qry->where('codvariedade', $filter['codvariedade']);
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        $qry = self::qryOrdem($qry, $sort ?: ['codfazenda', 'talhao']);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }
}
