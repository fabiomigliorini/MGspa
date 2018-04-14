<?php

namespace Mg\Marca;

use Illuminate\Http\Request;
use Mg\MgController;

use App\Http\Requests;
use Illuminate\Validation\Rule;

class MarcaController extends MgController
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        list($filter, $sort, $fields) = $this->filtros($request);
        $filter['abccategoria'] = json_decode($filter['abccategoria'], true);

        $qry = MarcaRepository::pesquisar($filter, $sort, $fields)->with('Imagem');
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
            'marca' => [
                'required',
                'unique:tblmarca',
                'min:2',
            ],
        ], [
            'marca.required' => 'O campo "Marca" deve ser preenchido!',
            'marca.unique' => 'Esta " Marca " já esta cadastrado',
            'marca.min' => 'O campo " Marca " deve ter no mínimo 2 caracteres.',
        ]);

        $model = new Marca();
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
    public function detalhes($id)
    {
        $model = MarcaRepository::detalhes($id);
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

    public function ativar(Request $request, $id) {
        $model = Marca::findOrFail($id);
        $model = MarcaRepository::ativar($model);

        return response()->json($model, 200);
    }

    public function inativar(Request $request, $id) {
        $model = Marca::findOrFail($id);
        $model = MarcaRepository::inativar($model);

        return response()->json($model, 200);
    }

    public function autor(Request $request, $id) {
        $model = Marca::findOrFail($id);
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

}
