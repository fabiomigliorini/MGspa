<?php

namespace Mg\Produto;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mg\MgController;

class UnidadeMedidaController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);

        $qry = UnidadeMedidaService::pesquisar($filter, $sort, $fields);
        $res = $qry->paginate()->appends($request->all());

        return response()->json($res, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'unidademedida' => ['required', 'unique:tblunidademedida', 'min:2', 'max:15'],
            'sigla' => ['required', 'unique:tblunidademedida', 'max:3'],
        ], [
            'unidademedida.required' => 'O campo "Descrição" não pode ser vazio',
            'unidademedida.unique' => 'Esta descrição já está cadastrada',
            'sigla.required' => 'O campo "Sigla" não pode ser vazio',
            'sigla.unique' => 'Esta sigla já está cadastrada',
        ]);

        $model = new UnidadeMedida();
        $model->fill($request->all());
        $model->save();

        return response()->json($model, 201);
    }

    public function show(Request $request, $id)
    {
        $model = UnidadeMedida::findOrFail($id);
        return response()->json($model, 200);
    }

    public function update(Request $request, $id)
    {
        $model = UnidadeMedida::findOrFail($id);

        $request->validate([
            'unidademedida' => [
                'required',
                Rule::unique('tblunidademedida')->ignore($model->codunidademedida, 'codunidademedida'),
                'min:2',
                'max:15',
            ],
            'sigla' => [
                'required',
                Rule::unique('tblunidademedida')->ignore($model->codunidademedida, 'codunidademedida'),
                'max:3',
            ],
        ], [
            'unidademedida.required' => 'O campo "Descrição" não pode ser vazio',
            'unidademedida.unique' => 'Esta descrição já está cadastrada',
            'sigla.required' => 'O campo "Sigla" não pode ser vazio',
            'sigla.unique' => 'Esta sigla já está cadastrada',
        ]);

        $model->fill($request->all());
        $model->update();

        return response()->json($model, 200);
    }

    public function destroy($id)
    {
        $model = UnidadeMedida::findOrFail($id);
        $model->delete();
        return response()->noContent();
    }

    public function ativar(Request $request, $id)
    {
        $model = UnidadeMedida::findOrFail($id);
        $model = UnidadeMedidaService::ativar($model);
        return response()->json($model, 200);
    }

    public function inativar(Request $request, $id)
    {
        $model = UnidadeMedida::findOrFail($id);
        $model = UnidadeMedidaService::inativar($model);
        return response()->json($model, 200);
    }

    public function autocompletar(Request $request)
    {
        $res = UnidadeMedidaService::autocompletar($request->all());
        return response()->json($res, 200);
    }
}
