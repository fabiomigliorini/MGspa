<?php

namespace Mg\Contrato;

use Illuminate\Http\Request;
use Mg\MgController;
use Mg\MgService;

/**
 * Pagamentos do comprador aninhados no contrato: contrato/{codcontrato}/pagamento.
 */
class ContratoPagamentoController extends MgController
{
    public function index(Request $request, $codcontrato)
    {
        $res = ContratoPagamento::where('codcontrato', $codcontrato)->orderBy('data')->get();
        return response()->json($res, 200);
    }

    public function store(Request $request, $codcontrato)
    {
        $request->validate($this->regras());

        $model = new ContratoPagamento();
        $model->fill($request->all());
        $model->codcontrato = $codcontrato;
        $model->save();

        return response()->json($model, 201);
    }

    public function update(Request $request, $codcontrato, $codpagamento)
    {
        $model = ContratoPagamento::where('codcontrato', $codcontrato)->findOrFail($codpagamento);
        $request->validate($this->regras());
        $model->fill($request->all());
        $model->codcontrato = $codcontrato;
        $model->update();
        return response()->json($model, 200);
    }

    public function destroy($codcontrato, $codpagamento)
    {
        ContratoPagamento::where('codcontrato', $codcontrato)->findOrFail($codpagamento)->delete();
        return response()->noContent();
    }

    public function inativar(Request $request, $codcontrato, $codpagamento)
    {
        $m = ContratoPagamento::where('codcontrato', $codcontrato)->findOrFail($codpagamento);
        return response()->json(MgService::inativar($m), 200);
    }

    public function ativar(Request $request, $codcontrato, $codpagamento)
    {
        $m = ContratoPagamento::where('codcontrato', $codcontrato)->findOrFail($codpagamento);
        return response()->json(MgService::ativar($m), 200);
    }

    protected function regras(): array
    {
        return [
            'data' => ['required', 'date'],
            'valor' => ['required', 'numeric', 'gt:0'],
            'observacao' => ['nullable', 'string'],
        ];
    }
}
