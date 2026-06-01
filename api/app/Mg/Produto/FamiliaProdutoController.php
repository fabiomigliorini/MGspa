<?php

namespace Mg\Produto;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mg\MgController;

class FamiliaProdutoController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $qry = FamiliaProdutoService::pesquisar($filter, $sort, $fields);
        $res = $qry->paginate()->appends($request->all());
        return response()->json($res, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'codsecaoproduto' => ['required', 'numeric', 'exists:tblsecaoproduto,codsecaoproduto'],
            'familiaproduto' => [
                'required',
                'min:3',
                Rule::unique('tblfamiliaproduto')->where('codsecaoproduto', $request->codsecaoproduto),
            ],
        ], self::mensagens());

        $model = new FamiliaProduto();
        $model->fill($request->all());
        $model->save();

        return response()->json($model, 201);
    }

    public function show(Request $request, $id)
    {
        $model = FamiliaProduto::findOrFail($id);
        return response()->json($model, 200);
    }

    public function update(Request $request, $id)
    {
        $model = FamiliaProduto::findOrFail($id);

        $request->validate([
            'codsecaoproduto' => ['required', 'numeric', 'exists:tblsecaoproduto,codsecaoproduto'],
            'familiaproduto' => [
                'required',
                'min:3',
                Rule::unique('tblfamiliaproduto')
                    ->where('codsecaoproduto', $request->codsecaoproduto)
                    ->ignore($model->codfamiliaproduto, 'codfamiliaproduto'),
            ],
        ], self::mensagens());

        $model->fill($request->all());
        $model->update();

        return response()->json($model, 200);
    }

    public function destroy($id)
    {
        $model = FamiliaProduto::findOrFail($id);
        $model->delete();
        return response()->noContent();
    }

    public function ativar(Request $request, $id)
    {
        $model = FamiliaProduto::findOrFail($id);
        $model = FamiliaProdutoService::ativar($model);
        return response()->json($model, 200);
    }

    public function inativar(Request $request, $id)
    {
        $model = FamiliaProduto::findOrFail($id);
        $model = FamiliaProdutoService::inativar($model);
        return response()->json($model, 200);
    }

    public function autocompletar(Request $request)
    {
        $res = FamiliaProdutoService::autocompletar($request->all());
        return response()->json($res, 200);
    }

    private static function mensagens(): array
    {
        return [
            'codsecaoproduto.required' => 'A seção é obrigatória',
            'codsecaoproduto.exists' => 'Seção inválida',
            'familiaproduto.required' => 'O campo "Família" não pode ser vazio',
            'familiaproduto.min' => 'A família deve ter no mínimo 3 caracteres',
            'familiaproduto.unique' => 'Já existe esta família nesta seção',
        ];
    }
}
