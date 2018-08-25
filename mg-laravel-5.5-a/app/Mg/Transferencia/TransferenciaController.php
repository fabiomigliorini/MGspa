<?php

namespace Mg\Transferencia;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class TransferenciaController extends MgController
{

    public function produtosFaltandoSemRequisicao(Request $request) {
        $res = TransferenciaRepository::produtosFaltandoSemRequisicao($request->codestoquelocalorigem, $request->codestoquelocaldestino);
        return response()->json($res, 200);
    }

    public function criarRequisicoes(Request $request) {
        $res = TransferenciaRepository::criarRequisicoes($request->requisicoes);
        return response()->json($res, 200);
    }

}
