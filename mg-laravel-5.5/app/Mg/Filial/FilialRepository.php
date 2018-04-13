<?php
namespace Filial;
use App\Mg\MgRepository;

class FilialRepository extends MgRepository
{

    public static function search(array $filter = null, array $sort = null, array $fields = null)
    {
        $qry = Filial::query();

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        if (!empty($filter['filial'])) {
            $qry->palavras('filial', $filter['filial']);
        }


        $qry = self::querySort($qry, $sort);
        $qry = self::queryFields($qry, $fields);
        return $qry;
    }
}
