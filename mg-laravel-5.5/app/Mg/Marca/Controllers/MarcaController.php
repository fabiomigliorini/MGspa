<?php

namespace App\Mg\Marca\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Validation\Rule;
use App\Mg\Marca\Repositories\MarcaRepository;
use App\Mg\Marca\Models\Marca;
use App\Mg\Controllers\MgController;


class MarcaController extends MgController
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        list($filter, $sort, $fields) = $this->parseSearchRequest($request);
        $filter['abccategoria'] = json_decode($filter['abccategoria'], true);

        $qry = Marca::search($filter, $sort, $fields)->with('Imagem');
        $res = $qry->paginate()->appends($request->all());

        foreach ($res as $i => $marca) {
            if (!empty($marca->codimagem)) {
                $res[$i]->imagem->url = $marca->Imagem->url;
            }
        }

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
        $model = Marca::findOrFail($id, $request->get('fields'));

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
        $model = MarcaRepository::details($id);
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
        $model = Marca::findOrFail($id);

        $request->validate([
            'marca' => [
                'required',
                Rule::unique('tblmarca')->ignore($model->codmarca, 'codmarca'),
                'min:2',
            ],
        ], [
            'marca.required' => 'O campo "Marca" deve ser preenchido!',
            'marca.unique' => 'Esta "Marca" já esta cadastrada',
            'marca.min' => 'O campo "Marca" deve ter no mínimo 2 caracteres.',
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
        $model = Marca::findOrFail($id);
        $model->delete();
    }

    public function activate(Request $request, $id) {
        $model = Marca::findOrFail($id);
        $model->activate();
        return response()->json($model, 200);
    }

    public function inactivate(Request $request, $id) {
        $model = Marca::findOrFail($id);
        $model->inactivate();
        return response()->json($model, 200);
    }

}
