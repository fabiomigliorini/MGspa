<?php

namespace Mg\Dfe;

use DB;
use Carbon\Carbon;

class DfeService
{
    public static function pesquisarDistribuicao(
        int $codfilial = null,
        Carbon $datade = null,
        Carbon $dataate = null,
        $nfechave = null,
        int $nsude = null,
        int $nsuate = null
        )
    {
        $qry = DistribuicaoDfe::query();

        if (!empty($codfilial)) {
            $qry->where('codfilial', $codfilial);
        }

        if (!empty($datade)) {
            $qry->where('criacao', '>=', $datade->startOfDay());
        }
        if (!empty($dataate)) {
            $qry->where('criacao', '<=', $dataate->endOfDay());
        }

        // if (!empty($nfechave)) {
        //     $qry->where('codfilial', $codfilial);
        // }

        if (!empty($nsude)) {
            $qry->where('nsu', '>=', $nsude);
        }
        if (!empty($nsuate)) {
            $qry->where('nsu', '<=', $nsuate);
        }

        $qry->orderBy('criacao', 'desc')->orderBy('nsu', 'desc');

        return $qry;
    }

}
