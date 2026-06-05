<?php

namespace Mg\Cultura;

use Mg\MgService;

class TabelaDescontoService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = TabelaDesconto::query()->with('Cultura');

        if (!empty($filter['codtabeladesconto'])) {
            $qry->where('codtabeladesconto', $filter['codtabeladesconto']);
        }

        if (!empty($filter['codcultura'])) {
            $qry->where('codcultura', $filter['codcultura']);
        }

        if (!empty($filter['tipo'])) {
            $qry->where('tipo', $filter['tipo']);
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        $qry = self::qryOrdem($qry, $sort ?: ['codcultura', 'tipo', 'faixainicio']);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }
}
