<?php

namespace Mg\NaturezaOperacao;

use Illuminate\Http\Request;

use Mg\MgController;
use Mg\Filial\Filial;



class NaturezaOperacaoController extends MgController
{

    public function index(Request $request)
    {
        list($filter, $sort, $fields) = $this->filtros($request);
        $qry = NaturezaOperacaoRepository::pesquisar($filter, $sort, $fields);
        $res = $qry->paginate()->appends($request->all());

        return response()->json($res, 206);

    }

    // public function autocompletar(Request $request)
    // {
    //     $res = NaturezaOperacaoRepository::autocompletar($request->all());
    //     return response()->json($res, 200);
    // }

    // public function armazenaDadosDFe(Request $request)
    // {
    //     $filial = Filial::findOrFail($request->filial);
    //     $res = NFeTerceiroRepository::armazenaDadosDFe($filial);
    //     return response()->json($res, 200);
    // }

}
