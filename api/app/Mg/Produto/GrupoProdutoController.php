<?php

namespace Mg\Produto;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mg\MgController;

class GrupoProdutoController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $qry = GrupoProdutoService::pesquisar($filter, $sort, $fields);
        $res = $qry->paginate()->appends($request->all());
        return response()->json($res, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'codfamiliaproduto' => ['required', 'numeric', 'exists:tblfamiliaproduto,codfamiliaproduto'],
            'grupoproduto' => [
                'required',
                'min:3',
                Rule::unique('tblgrupoproduto')->where('codfamiliaproduto', $request->codfamiliaproduto),
            ],
        ], self::mensagens());

        $model = new GrupoProduto();
        $model->fill($request->all());
        $model->save();

        return response()->json($model, 201);
    }

    public function show(Request $request, $id)
    {
        $model = GrupoProduto::findOrFail($id);
        return response()->json($model, 200);
    }

    public function update(Request $request, $id)
    {
        $model = GrupoProduto::findOrFail($id);

        $request->validate([
            'codfamiliaproduto' => ['required', 'numeric', 'exists:tblfamiliaproduto,codfamiliaproduto'],
            'grupoproduto' => [
                'required',
                'min:3',
                Rule::unique('tblgrupoproduto')
                    ->where('codfamiliaproduto', $request->codfamiliaproduto)
                    ->ignore($model->codgrupoproduto, 'codgrupoproduto'),
            ],
        ], self::mensagens());

        $model->fill($request->all());
        $model->update();

        return response()->json($model, 200);
    }

    public function destroy($id)
    {
        $model = GrupoProduto::findOrFail($id);
        $model->delete();
        return response()->noContent();
    }

    public function ativar(Request $request, $id)
    {
        $model = GrupoProduto::findOrFail($id);
        $model = GrupoProdutoService::ativar($model);
        return response()->json($model, 200);
    }

    public function inativar(Request $request, $id)
    {
        $model = GrupoProduto::findOrFail($id);
        $model = GrupoProdutoService::inativar($model);
        return response()->json($model, 200);
    }

    public function autocompletar(Request $request)
    {
        $res = GrupoProdutoService::autocompletar($request->all());
        return response()->json($res, 200);
    }

    private static function mensagens(): array
    {
        return [
            'codfamiliaproduto.required' => 'A família é obrigatória',
            'codfamiliaproduto.exists' => 'Família inválida',
            'grupoproduto.required' => 'O campo "Grupo" não pode ser vazio',
            'grupoproduto.min' => 'O grupo deve ter no mínimo 3 caracteres',
            'grupoproduto.unique' => 'Já existe este grupo nesta família',
        ];
    }
}
