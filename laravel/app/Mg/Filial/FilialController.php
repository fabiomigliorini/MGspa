<?php

namespace Mg\Filial;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class FilialController extends MgController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        list($filter, $sort, $fields) = $this->filtros($request);
        $qry = FilialService::pesquisar($filter, $sort, $fields);
        $res = $qry->paginate()->appends($request->all());

        return response()->json($res, 200);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $model = Filial::findOrFail($id, $request->get('fields'));

        return response()->json($model, 200);
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
            'filial' => [
                'required',
                'min:3',
            ],

        ], [
            'filial.required' => 'O campo "Filial" deve ser preenchido!',
            'filial.min' => 'O campo "Filial" deve ter no mínimo 3 caracteres.',
        ]);

        $model = new Filial();
        $model->fill($request->all());
        $model->save();

        return response()->json($model, 201);
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
        $model = Filial::findOrFail($id);

        $request->validate([
            'filial' => [
                'required',
                'min:3',
                Rule::unique('tblfilial')->ignore($model->codfilial, 'codfilial'),
            ],

        ], [
            'filial.required' => 'O campo "Filial" deve ser preenchido!',
            'filial.min' => 'O campo "Filial" deve ter no mínimo 3 caracteres.',
            'filial.unique' => 'Esta "Filial" já esta cadastrada',

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
        $model = Filial::findOrFail($id);
        $model->delete();
    }

    public function autor(Request $request, $id) {
        $model = Filial::findOrFail($id);
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
