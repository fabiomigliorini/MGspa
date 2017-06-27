<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Repositories\MarcaRepository;


class MarcaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        MarcaRepository::authorize('listing');

        list($filter, $sort, $fields) = $this->parseSearchRequest($request);
        $qry = MarcaRepository::query($filter, $sort, $fields);
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
        $this->validate($request, [
            'marca' => 'required',
        ]);

        $model = new Marca($request->all());
        $model->save();

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
    public function show($id)
    {
        $model = MarcaRepository::findOrFail($id);
        return response()->json(
            $model,
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
        $model = MarcaRepository::findOrFail($id);
        $model->fill($request->all());
        $model->save();

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
    public function destroy($id)
    {
        try{
            Marca::find($id)->delete();
            $ret = ['resultado' => true, 'mensagem' => 'Marca excluída com sucesso!'];
        }
        catch(\Exception $e){
            $ret = ['resultado' => false, 'mensagem' => 'Erro ao excluir marca!', 'exception' => $e];
        }
        return json_encode($ret);
    }


}
