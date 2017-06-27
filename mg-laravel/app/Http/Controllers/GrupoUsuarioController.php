<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\GrupoUsuarioRepository;

class GrupoUsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Permissao
        GrupoUsuarioRepository::authorize('listing');

        list($filter, $sort, $fields) = $this->parseSearchRequest($request);
        $qry = GrupoUsuarioRepository::query($filter, $sort, $fields);
        return response()->json($qry->paginate()->appends($request->all()), 206);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $model = GrupoUsuarioRepository::findOrFail($id);
        GrupoUsuarioRepository::authorize('view');

        return response()->json(
            $model,
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
        GrupoUsuarioRepository::authorize('create');

        $data = $request->all();

        $model = GrupoUsuarioRepository::new();

        if (!GrupoUsuarioRepository::validate($model, $data, $errors)) {
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $request->all();

        $model = GrupoUsuarioRepository::findOrFail($id);

        if (!GrupoUsuarioRepository::validate($model, $data, $errors)) {
            return response()->json(
                $errors,
                422
            );
        }

        $model->fill($data);

        GrupoUsuarioRepository::authorize('update');

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
        $model = GrupoUsuarioRepository::findOrFail($id);

        GrupoUsuarioRepository::authorize('delete');

        if ($mensagem = GrupoUsuarioRepository::used($model)) {
            return response()->json(['mensagem' => $mensagem], 422);
        }

        return response()->json(['mensagem' => GrupoUsuarioRepository::delete($model)], 204);
    }
}
