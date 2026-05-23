<?php

namespace Mg\Usuario;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mg\MgController;

class GrupoUsuarioController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $qry = GrupoUsuarioService::pesquisar($filter, $sort, $fields);
        $res = $qry->paginate()->appends($request->all());

        return GrupoUsuarioResource::collection($res);
    }

    public function store(Request $request)
    {
        $request->validate([
            'grupousuario' => ['required', 'unique:tblgrupousuario', 'min:2'],
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

    public function show(Request $request, $id)
    {
        $model = GrupoUsuario::findOrFail($id);
        return response()->json($model, 200);
    }

    public function detalhes($id)
    {
        $model = GrupoUsuarioService::detalhes($id);
        return new GrupoUsuarioResource($model);
    }

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

    public function destroy($id)
    {
        $model = GrupoUsuario::findOrFail($id);
        $model->delete();

        return response()->json(['result' => $model], 200);
    }

    public function autor(Request $request, $id)
    {
        $model = Usuario::findOrFail($id);
        $res = [
            'codusuario' => $model->codusuario,
            'usuario' => $model->usuario,
            'pessoa' => null,
            'imagem' => null,
        ];
        if (!empty($model->codpessoa)) {
            $res['pessoa'] = $model->Pessoa->pessoa ?? null;
        }
        if (!empty($model->codimagem)) {
            $res['imagem'] = $model->Imagem->url ?? null;
        }

        return response()->json($res, 200);
    }

    public function ativar(Request $request, $id)
    {
        $model = GrupoUsuario::findOrFail($id);
        $model = GrupoUsuarioService::ativar($model);

        return new GrupoUsuarioResource($model);
    }

    public function inativar(Request $request, $id)
    {
        $model = GrupoUsuario::findOrFail($id);
        $model = GrupoUsuarioService::inativar($model);

        return new GrupoUsuarioResource($model);
    }
}
