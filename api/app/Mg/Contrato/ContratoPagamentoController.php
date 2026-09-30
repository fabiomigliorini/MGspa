<?php

namespace Mg\Contrato;

use App\Http\Requests\Mg\Contrato\ContratoPagamentoRequest;
use Illuminate\Http\Request;
use Mg\MgController;
use Mg\MgService;
use Mg\Usuario\Autorizador;

/**
 * Recebimentos aninhados na fixação:
 *   contrato/{codcontrato}/fixacao/{codfixacao}/pagamento.
 * Cada linha = dinheiro que entrou (data + valor + portador). O "a receber"
 * (líquido), o recebido e o saldo são derivados no ContratoFixacaoResource.
 */
class ContratoPagamentoController extends MgController
{
    private const GRUPOS = ['Administrador', 'Gerente'];

    public function index(Request $request, $codcontrato, $codfixacao)
    {
        Autorizador::autoriza(self::GRUPOS);

        $res = ContratoPagamento::with('Portador')
            ->where('codcontratofixacao', $codfixacao)
            ->orderBy('data')
            ->get();
        return ContratoPagamentoResource::collection($res);
    }

    public function store(ContratoPagamentoRequest $request, $codcontrato, $codfixacao)
    {
        Autorizador::autoriza(self::GRUPOS);

        $fixacao = ContratoFixacao::where('codcontrato', $codcontrato)->findOrFail($codfixacao);

        $model = new ContratoPagamento();
        $model->fill($request->validated());
        $model->codcontratofixacao = $fixacao->codcontratofixacao;
        $model->save();

        return new ContratoPagamentoResource($model->load('Portador'));
    }

    public function update(ContratoPagamentoRequest $request, $codcontrato, $codfixacao, $codpagamento)
    {
        Autorizador::autoriza(self::GRUPOS);

        $model = ContratoPagamento::where('codcontratofixacao', $codfixacao)->findOrFail($codpagamento);
        $model->fill($request->validated());
        $model->update();

        return new ContratoPagamentoResource($model->load('Portador'));
    }

    public function destroy($codcontrato, $codfixacao, $codpagamento)
    {
        Autorizador::autoriza(self::GRUPOS);

        ContratoPagamento::where('codcontratofixacao', $codfixacao)->findOrFail($codpagamento)->delete();
        return response()->noContent();
    }

    public function inativar(Request $request, $codcontrato, $codfixacao, $codpagamento)
    {
        Autorizador::autoriza(self::GRUPOS);

        $m = ContratoPagamento::where('codcontratofixacao', $codfixacao)->findOrFail($codpagamento);
        MgService::inativar($m);
        return new ContratoPagamentoResource($m->fresh('Portador'));
    }

    public function ativar(Request $request, $codcontrato, $codfixacao, $codpagamento)
    {
        Autorizador::autoriza(self::GRUPOS);

        $m = ContratoPagamento::where('codcontratofixacao', $codfixacao)->findOrFail($codpagamento);
        MgService::ativar($m);
        return new ContratoPagamentoResource($m->fresh('Portador'));
    }
}
