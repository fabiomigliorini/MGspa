<?php

namespace Mg\NaturezaOperacao;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mg\MgController;

class NcmController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $qry = NcmService::pesquisar($filter, $sort, $fields);
        $res = $qry->paginate()->appends($request->all());
        return response()->json($res, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'ncm' => ['required', 'min:2', 'unique:tblncm'],
            'descricao' => ['required', 'min:3'],
            'codncmpai' => ['nullable', 'numeric', 'exists:tblncm,codncm'],
        ], self::mensagens());

        $model = new Ncm();
        $model->fill($request->all());
        $model->save();

        return response()->json($model, 201);
    }

    public function show(Request $request, $id)
    {
        $model = Ncm::findOrFail($id);
        return response()->json($model, 200);
    }

    public function update(Request $request, $id)
    {
        $model = Ncm::findOrFail($id);

        $request->validate([
            'ncm' => [
                'required',
                'min:2',
                Rule::unique('tblncm')->ignore($model->codncm, 'codncm'),
            ],
            'descricao' => ['required', 'min:3'],
            'codncmpai' => ['nullable', 'numeric', 'exists:tblncm,codncm'],
        ], self::mensagens());

        $model->fill($request->all());
        $model->update();

        return response()->json($model, 200);
    }

    public function destroy($id)
    {
        $model = Ncm::findOrFail($id);
        $model->delete();
        return response()->noContent();
    }

    public function ativar(Request $request, $id)
    {
        $model = Ncm::findOrFail($id);
        $model = NcmService::ativar($model);
        return response()->json($model, 200);
    }

    public function inativar(Request $request, $id)
    {
        $model = Ncm::findOrFail($id);
        $model = NcmService::inativar($model);
        return response()->json($model, 200);
    }

    public function autocompletar(Request $request)
    {
        $res = NcmService::autocompletar($request->all());
        return response()->json($res, 200);
    }

    public function arvore(Request $request)
    {
        $id = $request->get('id');
        $res = NcmService::arvoreFilhos($id ? (int) $id : null);
        return response()->json($res, 200);
    }

    private static function mensagens(): array
    {
        return [
            'ncm.required' => 'O campo "NCM" não pode ser vazio',
            'ncm.unique' => 'Este NCM já está cadastrado',
            'ncm.min' => 'O NCM deve ter no mínimo 2 caracteres',
            'descricao.required' => 'A descrição não pode ser vazia',
            'descricao.min' => 'A descrição deve ter no mínimo 3 caracteres',
            'codncmpai.exists' => 'NCM pai inválido',
        ];
    }
}
