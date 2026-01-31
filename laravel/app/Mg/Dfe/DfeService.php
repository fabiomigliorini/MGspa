<?php

namespace Mg\Dfe;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use Mg\NFePHP\NFePHPDistDfeService;

class DfeService
{
    public static function pesquisarDistribuicao(
        int $codfilial = null,
        Carbon $datade = null,
        Carbon $dataate = null,
        $nfechave = null,
        int $nsude = null,
        int $nsuate = null
    ) {
        $qry = DistribuicaoDfe::query();

        if (!empty($codfilial)) {
            $qry->where('codfilial', $codfilial);
        }

        if (!empty($datade)) {
            $qry->where('data', '>=', $datade->startOfDay());
        }
        if (!empty($dataate)) {
            $qry->where('data', '<=', $dataate->endOfDay());
        }

        if (!empty($nfechave)) {
            $qry->where('nfechave', 'ilike', "%$nfechave%");
        }

        if (!empty($nsude)) {
            $qry->where('nsu', '>=', $nsude);
        }
        if (!empty($nsuate)) {
            $qry->where('nsu', '<=', $nsuate);
        }

        $qry->orderBy('data', 'desc')->orderBy('nsu', 'desc');

        return $qry;
    }

    public static function xml($coddistribuicaodfe)
    {
        $dd = DistribuicaoDfe::findOrFail($coddistribuicaodfe);
        $xml = NFePHPDistDfeService::carregarXml($dd);
        return $xml;
    }

    public static function processar($coddistribuicaodfe)
    {
        $dd = DistribuicaoDfe::findOrFail($coddistribuicaodfe);
        return NFePHPDistDfeService::processar($dd);
    }
}
