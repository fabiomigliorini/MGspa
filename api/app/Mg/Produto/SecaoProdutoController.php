<?php

namespace Mg\Produto;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mg\MgController;

class SecaoProdutoController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $qry = SecaoProdutoService::pesquisar($filter, $sort, $fields);
        $res = $qry->paginate()->appends($request->all());
        return response()->json($res, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'secaoproduto' => ['required', 'unique:tblsecaoproduto', 'min:2'],
        ], [
            'secaoproduto.required' => 'O campo "Seção" não pode ser vazio',
            'secaoproduto.unique' => 'Esta seção já está cadastrada',
            'secaoproduto.min' => 'A seção deve ter no mínimo 2 caracteres',
        ]);

        $model = new SecaoProduto();
        $model->fill($request->all());
        $model->save();

        return response()->json($model, 201);
    }

    public function show(Request $request, $id)
    {
        $model = SecaoProduto::findOrFail($id);
        return response()->json($model, 200);
    }

    public function update(Request $request, $id)
    {
        $model = SecaoProduto::findOrFail($id);

        $request->validate([
            'secaoproduto' => [
                'required',
                Rule::unique('tblsecaoproduto')->ignore($model->codsecaoproduto, 'codsecaoproduto'),
                'min:2',
            ],
        ], [
            'secaoproduto.required' => 'O campo "Seção" não pode ser vazio',
            'secaoproduto.unique' => 'Esta seção já está cadastrada',
            'secaoproduto.min' => 'A seção deve ter no mínimo 2 caracteres',
        ]);

        $model->fill($request->all());
        $model->update();

        return response()->json($model, 200);
    }

    public function destroy($id)
    {
        $model = SecaoProduto::findOrFail($id);
        $model->delete();
        return response()->noContent();
    }

    public function ativar(Request $request, $id)
    {
        $model = SecaoProduto::findOrFail($id);
        $model = SecaoProdutoService::ativar($model);
        return response()->json($model, 200);
    }

    public function inativar(Request $request, $id)
    {
        $model = SecaoProduto::findOrFail($id);
        $model = SecaoProdutoService::inativar($model);
        return response()->json($model, 200);
    }

    public function autocompletar(Request $request)
    {
        $res = SecaoProdutoService::autocompletar($request->all());
        return response()->json($res, 200);
    }
}
