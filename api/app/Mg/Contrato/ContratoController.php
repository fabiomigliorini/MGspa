<?php

namespace Mg\Contrato;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mg\MgController;

class ContratoController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = ContratoService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return response()->json($res, 200);
    }

    public function show(Request $request, $id)
    {
        return response()->json(ContratoService::detalhe((int) $id), 200);
    }

    public function store(Request $request)
    {
        $request->validate($this->regras());

        $model = new Contrato();
        $model->fill($request->all());
        $model->save();

        return response()->json($model->fresh(ContratoService::WITH), 201);
    }

    public function update(Request $request, $id)
    {
        $model = Contrato::findOrFail($id);
        $request->validate($this->regras());
        $model->fill($request->all());
        $model->update();
        return response()->json($model->fresh(ContratoService::WITH), 200);
    }

    public function destroy($id)
    {
        Contrato::findOrFail($id)->delete();
        return response()->noContent();
    }

    public function inativar(Request $request, $id)
    {
        return response()->json(ContratoService::inativar(Contrato::findOrFail($id)), 200);
    }

    public function ativar(Request $request, $id)
    {
        return response()->json(ContratoService::ativar(Contrato::findOrFail($id)), 200);
    }

    protected function regras(): array
    {
        return [
            'contrato' => ['required', 'min:1'],
            'codpessoa' => ['required', 'exists:tblpessoa,codpessoa'],
            'codcultura' => ['required', 'exists:tblcultura,codcultura'],
            'codsafra' => ['nullable', 'exists:tblsafra,codsafra'],
            'tipo' => ['required', Rule::in(['FIXO', 'FIXAR', 'BARTER'])],
            'quantidade' => ['required', 'numeric', 'gt:0'],
            'preco' => ['nullable', 'numeric', 'gte:0'],
            'moeda' => ['nullable', Rule::in(['BRL', 'USD'])],
            'codnaturezaoperacao' => ['nullable', 'exists:tblnaturezaoperacao,codnaturezaoperacao'],
            'codpessoanf' => ['nullable', 'exists:tblpessoa,codpessoa'],
        ];
    }
}
