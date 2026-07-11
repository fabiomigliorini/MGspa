<?php

namespace Mg\Contrato;

use App\Http\Requests\Mg\Contrato\ContratoFixacaoCambioRequest;
use Illuminate\Http\Request;
use Mg\MgController;
use Mg\MgService;

/**
 * Travas de câmbio aninhadas na fixação:
 *   contrato/{codcontrato}/fixacao/{codfixacao}/cambio.
 * Toda operação regrava os 4 totais da fixação-pai (ContratoFixacaoService).
 */
class ContratoFixacaoCambioController extends MgController
{
    public function index(Request $request, $codcontrato, $codfixacao)
    {
        $res = ContratoFixacaoCambio::where('codcontratofixacao', $codfixacao)
            ->orderBy('data')
            ->get();
        return ContratoFixacaoCambioResource::collection($res);
    }

    public function store(ContratoFixacaoCambioRequest $request, $codcontrato, $codfixacao)
    {
        $fixacao = ContratoFixacao::where('codcontrato', $codcontrato)->findOrFail($codfixacao);

        $model = new ContratoFixacaoCambio();
        $model->fill($request->validated());
        $model->codcontratofixacao = $fixacao->codcontratofixacao;
        $model->save();

        ContratoFixacaoService::recalcular($fixacao);

        return new ContratoFixacaoCambioResource($model);
    }

    public function update(ContratoFixacaoCambioRequest $request, $codcontrato, $codfixacao, $codcambio)
    {
        $fixacao = ContratoFixacao::where('codcontrato', $codcontrato)->findOrFail($codfixacao);
        $model = ContratoFixacaoCambio::where('codcontratofixacao', $codfixacao)->findOrFail($codcambio);

        $model->fill($request->validated());
        $model->update();

        ContratoFixacaoService::recalcular($fixacao);

        return new ContratoFixacaoCambioResource($model);
    }

    public function destroy($codcontrato, $codfixacao, $codcambio)
    {
        $fixacao = ContratoFixacao::where('codcontrato', $codcontrato)->findOrFail($codfixacao);
        ContratoFixacaoCambio::where('codcontratofixacao', $codfixacao)->findOrFail($codcambio)->delete();

        ContratoFixacaoService::recalcular($fixacao);

        return response()->noContent();
    }

    public function inativar(Request $request, $codcontrato, $codfixacao, $codcambio)
    {
        $fixacao = ContratoFixacao::where('codcontrato', $codcontrato)->findOrFail($codfixacao);
        $m = ContratoFixacaoCambio::where('codcontratofixacao', $codfixacao)->findOrFail($codcambio);
        MgService::inativar($m);

        ContratoFixacaoService::recalcular($fixacao);

        return new ContratoFixacaoCambioResource($m->fresh());
    }

    public function ativar(Request $request, $codcontrato, $codfixacao, $codcambio)
    {
        $fixacao = ContratoFixacao::where('codcontrato', $codcontrato)->findOrFail($codfixacao);
        $m = ContratoFixacaoCambio::where('codcontratofixacao', $codfixacao)->findOrFail($codcambio);
        MgService::ativar($m);

        ContratoFixacaoService::recalcular($fixacao);

        return new ContratoFixacaoCambioResource($m->fresh());
    }
}
