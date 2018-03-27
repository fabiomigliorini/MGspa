<?php

namespace App\Mg\Usuario\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

use App\Mg\Usuario\Models\GrupoUsuario;
use App\Mg\Usuario\Models\Usuario;
use App\Mg\Usuario\Repositories\GrupoUsuarioRepository;

class GrupoUsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        list($filter, $sort, $fields) = $this->parseSearchRequest($request);
        $qry = GrupoUsuario::search($filter, $sort, $fields);
        $res = $qry->paginate()->appends($request->all());

        return response()->json($res, 206);

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
                'unique:tblusuario',
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
    public function details ($id)
    {
        $model = GrupoUsuarioRepository::details($id);
        return response()->json($model, 200);
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

        return response()->json($model, 201);
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
    }

    public function author(Request $request, $id) {
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

    public function activate(Request $request, $id) {
        $model = GrupoUsuario::findOrFail($id);
        $model->activate();
        return response()->json($model, 200);
    }

    public function inactivate(Request $request, $id) {
        $model = GrupoUsuario::findOrFail($id);
        $model->inactivate();
        return response()->json($model, 200);
    }

    public function parseSearchRequest(Request $request)
    {
        $req = $request->all();

        $sort = $request->sort;
        if (!empty($sort)) {
            $sort = explode(',', $sort);
        }

        $fields = $request->fields;
        if (!empty($fields)) {
            $fields = explode(',', $fields);
        }

        $filter = $request->all();

        unset($filter['fields']);
        unset($filter['sort']);
        unset($filter['page']);

        return [
            $filter,
            $sort,
            $fields,
        ];
    }

}
