<?php

namespace Mg\Produto;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mg\MgController;

class SubGrupoProdutoController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $qry = SubGrupoProdutoService::pesquisar($filter, $sort, $fields);
        $res = $qry->paginate()->appends($request->all());
        return response()->json($res, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'codgrupoproduto' => ['required', 'numeric', 'exists:tblgrupoproduto,codgrupoproduto'],
            'subgrupoproduto' => [
                'required',
                'min:3',
                Rule::unique('tblsubgrupoproduto')->where('codgrupoproduto', $request->codgrupoproduto),
            ],
        ], self::mensagens());

        $model = new SubGrupoProduto();
        $model->fill($request->all());
        $model->save();

        return response()->json($model, 201);
    }

    public function show(Request $request, $id)
    {
        $model = SubGrupoProduto::findOrFail($id);
        return response()->json($model, 200);
    }

    public function update(Request $request, $id)
    {
        $model = SubGrupoProduto::findOrFail($id);

        $request->validate([
            'codgrupoproduto' => ['required', 'numeric', 'exists:tblgrupoproduto,codgrupoproduto'],
            'subgrupoproduto' => [
                'required',
                'min:3',
                Rule::unique('tblsubgrupoproduto')
                    ->where('codgrupoproduto', $request->codgrupoproduto)
                    ->ignore($model->codsubgrupoproduto, 'codsubgrupoproduto'),
            ],
        ], self::mensagens());

        $model->fill($request->all());
        $model->update();

        return response()->json($model, 200);
    }

    public function destroy($id)
    {
        $model = SubGrupoProduto::findOrFail($id);
        $model->delete();
        return response()->noContent();
    }

    public function ativar(Request $request, $id)
    {
        $model = SubGrupoProduto::findOrFail($id);
        $model = SubGrupoProdutoService::ativar($model);
        return response()->json($model, 200);
    }

    public function inativar(Request $request, $id)
    {
        $model = SubGrupoProduto::findOrFail($id);
        $model = SubGrupoProdutoService::inativar($model);
        return response()->json($model, 200);
    }

    public function autocompletar(Request $request)
    {
        $res = SubGrupoProdutoService::autocompletar($request->all());
        return response()->json($res, 200);
    }

    private static function mensagens(): array
    {
        return [
            'codgrupoproduto.required' => 'O grupo é obrigatório',
            'codgrupoproduto.exists' => 'Grupo inválido',
            'subgrupoproduto.required' => 'O campo "Subgrupo" não pode ser vazio',
            'subgrupoproduto.min' => 'O subgrupo deve ter no mínimo 3 caracteres',
            'subgrupoproduto.unique' => 'Já existe este subgrupo neste grupo',
        ];
    }
}
