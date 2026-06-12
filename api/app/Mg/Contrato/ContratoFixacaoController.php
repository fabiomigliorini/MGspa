<?php

namespace Mg\Contrato;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mg\MgController;
use Mg\MgService;

/**
 * Fixacoes de preco aninhadas no contrato: contrato/{codcontrato}/fixacao.
 * Permite fixacao parcial (varias fixacoes) e USD com trava de R$ (precoreal).
 */
class ContratoFixacaoController extends MgController
{
    public function index(Request $request, $codcontrato)
    {
        $res = ContratoFixacao::where('codcontrato', $codcontrato)->orderBy('data')->get();
        return response()->json($res, 200);
    }

    public function store(Request $request, $codcontrato)
    {
        // FIXO tem fixação automática (espelho do contrato); não se fixa à mão.
        if (Contrato::findOrFail($codcontrato)->tipo === 'FIXO') {
            abort(422, 'Contrato FIXO já tem o preço travado; a fixação é automática.');
        }

        $request->validate($this->regras());

        $model = new ContratoFixacao();
        $model->fill($request->all());
        $model->codcontrato = $codcontrato;
        $model->precoreal = ContratoService::precoReal($request->all());
        $model->save();

        return response()->json($model, 201);
    }

    public function update(Request $request, $codcontrato, $codfixacao)
    {
        $model = ContratoFixacao::where('codcontrato', $codcontrato)->findOrFail($codfixacao);
        $request->validate($this->regras());

        $model->fill($request->all());
        $model->codcontrato = $codcontrato;
        $model->precoreal = ContratoService::precoReal($request->all());
        $model->update();

        return response()->json($model, 200);
    }

    public function destroy($codcontrato, $codfixacao)
    {
        ContratoFixacao::where('codcontrato', $codcontrato)->findOrFail($codfixacao)->delete();
        return response()->noContent();
    }

    public function inativar(Request $request, $codcontrato, $codfixacao)
    {
        $m = ContratoFixacao::where('codcontrato', $codcontrato)->findOrFail($codfixacao);
        return response()->json(MgService::inativar($m), 200);
    }

    public function ativar(Request $request, $codcontrato, $codfixacao)
    {
        $m = ContratoFixacao::where('codcontrato', $codcontrato)->findOrFail($codfixacao);
        return response()->json(MgService::ativar($m), 200);
    }

    protected function regras(): array
    {
        return [
            'data' => ['required', 'date'],
            'quantidade' => ['required', 'numeric', 'gt:0'],
            'preco' => ['required', 'numeric', 'gte:0'],
            'moeda' => ['nullable', Rule::in(['BRL', 'USD'])],
            'dolar' => ['nullable', 'numeric', 'gt:0'],
        ];
    }
}
