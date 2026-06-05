<?php

namespace Mg\Fazenda;

use Mg\MgService;

class FazendaService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = Fazenda::query()->with('Pessoa');

        if (!empty($filter['codfazenda'])) {
            $qry->where('codfazenda', $filter['codfazenda']);
        }

        if (!empty($filter['fazenda'])) {
            $qry->palavras('fazenda', $filter['fazenda']);
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        $qry = self::qryOrdem($qry, $sort ?: ['fazenda']);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }
}
