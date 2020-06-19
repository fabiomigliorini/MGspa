<?php
namespace Mg\Filial;
use Mg\MgService;

class FilialService extends MgService
{

    public static function pesquisar(array $filter = null, array $sort = null, array $fields = null)
    {
        $qry = Filial::query();

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        if (!empty($filter['filial'])) {
            $qry->palavras('filial', $filter['filial']);
        }


        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }
}
