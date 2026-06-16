<?php

namespace Mg\Contrato;

use App\Http\Requests\Mg\Contrato\ContratoFixacaoRequest;
use Illuminate\Http\Request;
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
        $res = ContratoFixacao::with('Contrato.Filial')
            ->where('codcontrato', $codcontrato)
            ->orderBy('data')
            ->get();
        return ContratoFixacaoResource::collection($res);
    }

    public function store(ContratoFixacaoRequest $request, $codcontrato)
    {
        // FIXO tem fixação automática (espelho do contrato); não se fixa à mão.
        if (Contrato::findOrFail($codcontrato)->tipo === 'FIXO') {
            abort(422, 'Contrato FIXO já tem o preço travado; a fixação é automática.');
        }

        $model = new ContratoFixacao();
        $model->fill($request->validated());
        $model->codcontrato = $codcontrato;
        $model->precoreal = ContratoService::precoReal($request->validated());
        $model->save();

        return new ContratoFixacaoResource($model->load('Contrato.Filial'));
    }

    public function update(ContratoFixacaoRequest $request, $codcontrato, $codfixacao)
    {
        $model = ContratoFixacao::where('codcontrato', $codcontrato)->findOrFail($codfixacao);

        $model->fill($request->validated());
        $model->codcontrato = $codcontrato;
        $model->precoreal = ContratoService::precoReal($request->validated());
        $model->update();

        return new ContratoFixacaoResource($model->load('Contrato.Filial'));
    }

    public function destroy($codcontrato, $codfixacao)
    {
        ContratoFixacao::where('codcontrato', $codcontrato)->findOrFail($codfixacao)->delete();
        return response()->noContent();
    }

    public function inativar(Request $request, $codcontrato, $codfixacao)
    {
        $m = ContratoFixacao::where('codcontrato', $codcontrato)->findOrFail($codfixacao);
        MgService::inativar($m);
        return new ContratoFixacaoResource($m->fresh('Contrato.Filial'));
    }

    public function ativar(Request $request, $codcontrato, $codfixacao)
    {
        $m = ContratoFixacao::where('codcontrato', $codcontrato)->findOrFail($codfixacao);
        MgService::ativar($m);
        return new ContratoFixacaoResource($m->fresh('Contrato.Filial'));
    }
}
