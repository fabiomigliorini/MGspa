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
            $dados = [
              'id' => 'asdadasds',
              'codprodutovariacao' => $request->codprodutovariacao
            ];

            $res = $request->all();

            //dd($request->get('codprodutovariacao'));

            $res = EstoqueEstatisticaRepository::buscaEstatisticaProduto($id, null, $request->codprodutovariacao, $request->codestoquelocal);
            //$res = EstoqueEstatisticaRepository::buscaEstatisticaProduto($id, null, $request->codprodutovariacao, $request->codestoquelocal);
            //return response()->json($dados, 206);

            return response()->json($res, 206);
        }
}
