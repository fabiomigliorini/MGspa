<?php

namespace Mg\Dfe;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

use Mg\Filial\Filial;
use Mg\Dfe\DistribuicaoDfe;

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

    public function xml(Request $request, int $coddistribuicaodfe)
    {
        $xml = DfeService::xml($coddistribuicaodfe);
        return response($xml, 200, [
            'Content-Type' => 'application/xml'
        ]);
    }

    public function processar(Request $request, int $coddistribuicaodfe)
    {
        return DfeService::processar($coddistribuicaodfe);
    }

    public function filiaisHabilitadas (Request $request)
    {
        $filiais = Filial::select([
            'codfilial',
            'filial'
        ])->ativo()
        ->where('dfe', true)
        ->orderBy('codempresa')
        ->orderBy('codfilial')
        ->get();
        $arr = [];
        foreach ($filiais as $filial) {
            $ret = [
                'codfilial' => $filial->codfilial,
                'filial' => $filial->filial,
            ];
            $ret['nsu'] = DistribuicaoDfe::where('codfilial', $filial->codfilial)->max('nsu');
            $arr[] = $ret;
        }
        return $arr;
    }

}
