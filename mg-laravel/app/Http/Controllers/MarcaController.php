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
         MarcaRepository::authorize('create');

         $data = $request->all();

         $model = MarcaRepository::new();

         if (!MarcaRepository::validate($model, $data, $errors)) {
             return response()->json(
                 $errors,
                 422
             );
         }

         $model->fill($data);

         if (!$model->save()) {
             abort(500);
         }

         return response()->json(
             $model,
             201
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
         $data = $request->all();

         $model = MarcaRepository::findOrFail($id);

         if (!MarcaRepository::validate($model, $data, $errors)) {
             return response()->json(
                 $errors,
                 422
             );
         }

         $model->fill($data);

         MarcaRepository::authorize('update');

         if (!$model->save()) {
             abort(500);
         }

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
     public function destroy($id)
     {
         $model = MarcaRepository::findOrFail($id);

         MarcaRepository::authorize('delete');

         if ($mensagem = MarcaRepository::used($model)) {
             return response()->json(['mensagem' => $mensagem], 422);
         }

         return response()->json(['mensagem' => MarcaRepository::delete($model)], 204);
     }
}
