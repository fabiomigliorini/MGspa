<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Repositories\ProdutoRepository;

use App\Http\Requests\Produto\ProdutoStoreRequest;
use App\Http\Requests\Produto\ProdutoDeleteRequest;
use App\Http\Requests\Produto\ProdutoIndexRequest;
use App\Http\Requests\Produto\ProdutoShowRequest;
use App\Http\Requests\Produto\ProdutoUpdateRequest;

class ProdutoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(ProdutoIndexRequest $request)
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
    public function store(ProdutoStoreRequest $request)
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
    public function show(ProdutoShowRequest $request, $id)
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
    public function update(ProdutoUpdateRequest $request, $id)
    {
        $model = ProdutoRepository::findOrFail($id);
        $model = ProdutoRepository::update($model, $request->all());
        return response()->json(
            $model,
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProdutoDeleteRequest $request, $id)
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


    public function activate(ProdutoDeleteRequest $request, $id) {
        dd('ativando' . $id);
    }

    public function inactivate(ProdutoDeleteRequest $request, $id) {
        dd('inativando' . $id);
    }

}
