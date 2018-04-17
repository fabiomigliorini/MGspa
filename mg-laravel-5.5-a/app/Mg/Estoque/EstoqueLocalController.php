<?php

namespace Mg\Estoque;

use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Estoque\EstoqueLocalRepository;

class EstoqueLocalController extends MgController
{
    public function autocompletar (Request $request) {
        $res = EstoqueLocalRepository::autocompletar($request->all());

        return response()->json($res, 206);
    }


}
