<?php

namespace Mg\Produto;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mg\MgController;

class TipoProdutoController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);

        $qry = TipoProdutoService::pesquisar($filter, $sort, $fields);
        $res = $qry->paginate()->appends($request->all());

        return response()->json($res, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipoproduto' => ['required', 'unique:tbltipoproduto', 'min:2'],
        ], [
            'tipoproduto.required' => 'O campo "Tipo de Produto" não pode ser vazio',
            'tipoproduto.unique' => 'Este tipo de produto já está cadastrado',
            'tipoproduto.min' => 'O campo "Tipo de Produto" deve ter no mínimo 2 caracteres',
        ]);

        $model = new TipoProduto();
        $model->fill($request->all());
        $model->save();

        return response()->json($model, 201);
    }

    public function show(Request $request, $id)
    {
        $model = TipoProduto::findOrFail($id);
        return response()->json($model, 200);
    }

    public function update(Request $request, $id)
    {
        $model = TipoProduto::findOrFail($id);

        $request->validate([
            'tipoproduto' => [
                'required',
                Rule::unique('tbltipoproduto')->ignore($model->codtipoproduto, 'codtipoproduto'),
                'min:2',
            ],
        ], [
            'tipoproduto.required' => 'O campo "Tipo de Produto" não pode ser vazio',
            'tipoproduto.unique' => 'Este tipo de produto já está cadastrado',
            'tipoproduto.min' => 'O campo "Tipo de Produto" deve ter no mínimo 2 caracteres',
        ]);

        $model->fill($request->all());
        $model->update();

        return response()->json($model, 200);
    }

    public function destroy($id)
    {
        $model = TipoProduto::findOrFail($id);
        $model->delete();
        return response()->noContent();
    }

    public function autocompletar(Request $request)
    {
        $res = TipoProdutoService::autocompletar($request->all());
        return response()->json($res, 200);
    }
}
