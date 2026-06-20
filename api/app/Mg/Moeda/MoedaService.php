<?php

namespace Mg\Moeda;

use Mg\MgService;

class MoedaService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = Moeda::query();

        if (!empty($filter['moeda'])) {
            $qry->palavras('moeda', $filter['moeda']);
        }
        if (!empty($filter['descricao'])) {
            $qry->palavras('descricao', $filter['descricao']);
        }
        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        $qry = self::qryOrdem($qry, $sort ?: ['moeda']);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }
}
