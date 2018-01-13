<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Repositories\EstoqueEstatisticaRepository;

class EstoqueEstatisticaController extends ControllerCrud
{
    protected $repositoryName = 'App\\Repositories\\EstoqueEstatisticaRepository';

        public function show(Request $request, $id)
        {
            $this->authorize();

            $res = EstoqueEstatisticaRepository::buscaEstatisticaProduto($id);

            return response()->json($res, 206);
        }
}
