<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Repositories\ProdutoRepository;

class ProdutoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //ProdutoRepository::authorize('listing');

        list($filter, $sort, $fields) = $this->parseSearchRequest($request);
        $qry = ProdutoRepository::query($filter, $sort, $fields);
        return response()->json($qry->paginate()->appends($request->all()), 206);

        return response()->json(
            $data,
            200
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $dados =  $request->all();
        $model = ProdutoRepository::validate(null, $dados, $errors);
        return response()->json(
            $model,
            200
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $details = ProdutoRepository::detailsById($id);
        return response()->json(
            $details,
            200
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->authorize();

        $dados = $request->all();
        $model = ProdutoRepository::findOrFail($id);
        $model = ProdutoRepository::fill($model, $dados);

        if (!ProdutoRepository::validate($model, $errors)) {
            return response()->json(
                $errors,
                422
            );
        }

        $model = ProdutoRepository::update($model, $dados);
        return response()->json(
            $model,
            201
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try{
            Produto::find($id)->delete();
            $ret = ['resultado' => true, 'mensagem' => 'Produto excluída com sucesso!'];
        }
        catch(\Exception $e){
            $ret = ['resultado' => false, 'mensagem' => 'Erro ao excluir produto!', 'exception' => $e];
        }
        return json_encode($ret);
    }


    public function activate(Request $request, $id) {
        dd('ativando' . $id);
    }

    public function inactivate(Request $request, $id) {
        dd('inativando' . $id);
    }

}
