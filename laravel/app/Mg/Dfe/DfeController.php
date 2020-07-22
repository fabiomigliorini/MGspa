<?php

namespace Mg\Dfe;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class DfeController extends MgController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function distribuicao(Request $request)
    {
        $codfilial = $request->codfilial;
        $datade = (empty($request->datade))?null:Carbon::parse($request->datade);
        $dataate = (empty($request->dataate))?null:Carbon::parse($request->dataate);
        $nfechave = trim(numeroLimpo($request->nfechave));
        $nsude = $request->nsude;
        $nsuate = $request->nsuate;
        // dd($request->all());
        $qry = DfeService::pesquisarDistribuicao(
            $codfilial,
            $datade,
            $dataate,
            $nfechave,
            $nsude,
            $nsuate
        );
        return DistribuicaoDfeResource::collection($qry->paginate());
    }

}
