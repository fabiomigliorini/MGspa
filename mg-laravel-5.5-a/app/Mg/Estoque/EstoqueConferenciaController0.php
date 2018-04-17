<?php

namespace Mg\Estoque;

use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Marca\MarcaRepository;
use Mg\Produto\ProdutoRepository;
use Mg\Estoque\EstoqueSaldoConferencia;

class EstoqueConferenciaController extends MgController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $res = MarcaRepository::buscaProdutosParaConferencia(
            $request->codmarca,
            $request->codestoquelocal,
            $request->fiscal
        );

        return response()->json($res, 206);
    }


    public function store(Request $request)
    {
        // $request->validate([
        //     'barras' => [
        //         'required',
        //     ],
        // ], [
        //     'barras.required' => 'O campo "Grupo Usuario" deve ser preenchido!',
        // ]);

        dd($request->all());
        $model = EstoqueSaldoConferenciaRepository::criaConferencia($request->all());
        $model->save();

        return response()->json($model, 201);
    }

    public function buscaProduto(Request $request)
    {
        $res = ProdutoRepository::buscaProduto($request->barras, $request->codestoquelocal, $request->fiscal);

        return response()->json($res, 206);
    }

}
