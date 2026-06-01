<?php

namespace Mg\Cheque;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mg\MgController;
use Mg\Usuario\Autorizador;

class ChequeMotivoDevolucaoController extends MgController
{
    private const GRUPOS = ['Administrador', 'Financeiro'];

    public function index(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        [$filter, $sort, $fields] = $this->filtros($request);

        $qry = ChequeMotivoDevolucaoService::pesquisar($filter, $sort, $fields);
        $res = $qry->paginate()->appends($request->all());

        return response()->json($res, 200);
    }

    public function store(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        $request->validate([
            'numero' => ['required', 'integer', 'min:1', 'unique:tblchequemotivodevolucao,numero'],
            'chequemotivodevolucao' => ['required', 'min:5'],
        ], self::MENSAGENS);

        $model = new ChequeMotivoDevolucao();
        $model->fill($request->all());
        $model->save();

        return response()->json($model, 201);
    }

    public function show(Request $request, $id)
    {
        Autorizador::autoriza(self::GRUPOS);

        $model = ChequeMotivoDevolucao::findOrFail($id);
        return response()->json($model, 200);
    }

    public function update(Request $request, $id)
    {
        Autorizador::autoriza(self::GRUPOS);

        $model = ChequeMotivoDevolucao::findOrFail($id);

        $request->validate([
            'numero' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('tblchequemotivodevolucao', 'numero')
                    ->ignore($model->codchequemotivodevolucao, 'codchequemotivodevolucao'),
            ],
            'chequemotivodevolucao' => ['required', 'min:5'],
        ], self::MENSAGENS);

        $model->fill($request->all());
        $model->update();

        return response()->json($model, 200);
    }

    public function destroy($id)
    {
        Autorizador::autoriza(self::GRUPOS);

        $model = ChequeMotivoDevolucao::findOrFail($id);
        ChequeMotivoDevolucaoService::excluir($model);
        return response()->noContent();
    }

    public function autocompletar(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        $res = ChequeMotivoDevolucaoService::autocompletar($request->all());
        return response()->json($res, 200);
    }

    private const MENSAGENS = [
        'numero.required' => 'O campo "Número" deve ser preenchido!',
        'numero.integer' => 'O campo "Número" deve ser um número!',
        'numero.unique' => 'Já existe um motivo de devolução cadastrado com esse número!',
        'chequemotivodevolucao.required' => 'O campo "Descrição" deve ser preenchido!',
        'chequemotivodevolucao.min' => 'O campo "Descrição" deve ter no mínimo 5 caracteres!',
    ];
}
