<?php

namespace Mg\Usuario;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class GrupoUsuarioController extends MgController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        list($filter, $sort, $fields) = $this->filtros($request);
        $qry = GrupoUsuarioService::pesquisar($filter, $sort, $fields);
        $res = $qry->paginate()->appends($request->all());

        return GrupoUsuarioResource::collection($res);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'grupousuario' => [
                'required',
                'unique:tblgrupousuario',
                'min:2',
            ],
        ], [
            'grupousuario.required' => 'O campo "Grupo Usuario" deve ser preenchido!',
            'grupousuario.unique' => 'Este "Grupo Usuario" já esta cadastrado',
            'grupousuario.min' => 'O campo "Grupo Usuario" deve ter no mínimo 2 caracteres.',
        ]);

        $model = new GrupoUsuario();
        $model->fill($request->all());
        $model->save();

        return response()->json($model, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $model = GrupoUsuario::findOrFail($id, $request->get('fields'));

        return response()->json($model, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function detalhes($id)
    {
        $model = GrupoUsuarioService::detalhes($id);
        return new GrupoUsuarioResource($model);
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
        $model = GrupoUsuario::findOrFail($id);

        $request->validate([
            'grupousuario' => [
                'required',
                Rule::unique('tblgrupousuario')->ignore($model->codgrupousuario, 'codgrupousuario'),
                'min:2',
            ],
        ], [
            'grupousuario.required' => 'O campo "Grupo Usuario" deve ser preenchido!',
            'grupousuario.unique' => 'Este "Grupo Usuario" já esta cadastrado',
            'grupousuario.min' => 'O campo "Grupo Usuario" deve ter no mínimo 2 caracteres.',
        ]);

        $model->fill($request->all());
        $model->update();
        return new GrupoUsuarioResource($model);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $model = GrupoUsuario::findOrFail($id);
        $model->delete();

        return response()->json([
            'result' => $model
        ], 200);
    }

    public function autor(Request $request, $id) {
        $model = Usuario::findOrFail($id);
        $res = [
            'codusuario' => $model->codusuario,
            'usuario' => $model->usuario,
            'pessoa' => null,
            'imagem' => null,
        ];
        if (!empty($model->codpessoa)) {
            $res['pessoa'] = $model->Pessoa->pessoa;
        }
        if (!empty($model->codimagem)) {
            $res['imagem'] = $model->Imagem->url;
        }

        return response()->json($res, 200);
    }

    public function ativar(Request $request, $id) {
        $model = GrupoUsuario::findOrFail($id);
        $model = GrupoUsuarioService::ativar($model);

        return new GrupoUsuarioResource($model);
    }

    public function inativar(Request $request, $id) {
        $model = GrupoUsuario::findOrFail($id);
        $model = GrupoUsuarioService::inativar($model);

        return new GrupoUsuarioResource($model);
    }

}
