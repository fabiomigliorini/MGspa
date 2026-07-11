<?php

namespace Mg\Contrato;

use App\Http\Requests\Mg\Contrato\ContratoFixacaoRequest;
use Illuminate\Http\Request;
use Mg\MgController;
use Mg\MgService;

/**
 * Fixacoes de preco aninhadas no contrato: contrato/{codcontrato}/fixacao.
 * Permite fixacao parcial (varias fixacoes) e moeda estrangeira (o cambio e
 * travado a parte, em tblcontratofixacaocambio).
 *
 * Os 4 totais gravados (totalmoeda/saldomoeda/totalbrl/liquidobrl) sao SEMPRE
 * (re)calculados por ContratoFixacaoService::recalcular — nunca vem do cliente.
 */
class ContratoFixacaoController extends MgController
{
    public function index(Request $request, $codcontrato)
    {
        $res = ContratoFixacao::with('Moeda', 'ContratoFixacaoCambioS')
            ->where('codcontrato', $codcontrato)
            ->orderBy('data')
            ->get();
        return ContratoFixacaoResource::collection($res);
    }

    public function store(ContratoFixacaoRequest $request, $codcontrato)
    {
        // Precificacao vive na fixacao: um contrato "FIXO" e so um que recebe a
        // fixacao cheia na assinatura. Qualquer contrato pode receber fixacoes.
        Contrato::findOrFail($codcontrato);

        $model = new ContratoFixacao();
        $model->fill($request->validated());
        $model->codcontrato = $codcontrato;
        $model->save();

        ContratoFixacaoService::recalcular($model);

        return new ContratoFixacaoResource($model->load('Moeda', 'ContratoFixacaoCambioS'));
    }

    public function update(ContratoFixacaoRequest $request, $codcontrato, $codfixacao)
    {
        $model = ContratoFixacao::where('codcontrato', $codcontrato)->findOrFail($codfixacao);

        $model->fill($request->validated());
        $model->codcontrato = $codcontrato;
        $model->update();

        ContratoFixacaoService::recalcular($model);

        return new ContratoFixacaoResource($model->load('Moeda', 'ContratoFixacaoCambioS'));
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
        return new ContratoFixacaoResource($m->fresh('Moeda', 'ContratoFixacaoCambioS'));
    }

    public function ativar(Request $request, $codcontrato, $codfixacao)
    {
        $m = ContratoFixacao::where('codcontrato', $codcontrato)->findOrFail($codfixacao);
        MgService::ativar($m);
        return new ContratoFixacaoResource($m->fresh('Moeda', 'ContratoFixacaoCambioS'));
    }

    /**
     * Quita a fixação: marca RECEBIDA (encerra o saldo) mesmo quando o recebido
     * não bate no centavo com o líquido (diferencinha de imposto). reabrir() desfaz.
     */
    public function quitar(Request $request, $codcontrato, $codfixacao)
    {
        $m = ContratoFixacao::where('codcontrato', $codcontrato)->findOrFail($codfixacao);
        $m->quitado = now();
        $m->save();
        return new ContratoFixacaoResource(
            $m->fresh('Moeda', 'ContratoFixacaoCambioS', 'ContratoPagamentoS.Portador'),
        );
    }

    public function reabrir(Request $request, $codcontrato, $codfixacao)
    {
        $m = ContratoFixacao::where('codcontrato', $codcontrato)->findOrFail($codfixacao);
        $m->quitado = null;
        $m->save();
        return new ContratoFixacaoResource(
            $m->fresh('Moeda', 'ContratoFixacaoCambioS', 'ContratoPagamentoS.Portador'),
        );
    }
}
