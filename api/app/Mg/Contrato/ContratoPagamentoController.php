<?php

namespace Mg\Contrato;

use App\Http\Requests\Mg\Contrato\ContratoPagamentoConfirmarRequest;
use App\Http\Requests\Mg\Contrato\ContratoPagamentoRequest;
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
        $res = ContratoPagamento::with('Portador')
            ->where('codcontrato', $codcontrato)
            ->orderBy('data')
            ->get();
        return ContratoPagamentoResource::collection($res);
    }

    public function store(ContratoPagamentoRequest $request, $codcontrato)
    {
        $model = new ContratoPagamento();
        $model->fill($request->validated());
        $model->codcontrato = $codcontrato;
        $model->save();

        return new ContratoPagamentoResource($model->load('Portador'));
    }

    public function update(ContratoPagamentoRequest $request, $codcontrato, $codpagamento)
    {
        $model = ContratoPagamento::where('codcontrato', $codcontrato)->findOrFail($codpagamento);
        $model->fill($request->validated());
        $model->codcontrato = $codcontrato;
        $model->update();

        return new ContratoPagamentoResource($model->load('Portador'));
    }

    public function destroy($codcontrato, $codpagamento)
    {
        ContratoPagamento::where('codcontrato', $codcontrato)->findOrFail($codpagamento)->delete();
        return response()->noContent();
    }

    /**
     * Confirma o recebimento de uma parcela (valor real pode divergir do
     * previsto). Registra data/valor recebidos e o portador que recebeu.
     */
    public function confirmar(ContratoPagamentoConfirmarRequest $request, $codcontrato, $codpagamento)
    {
        $m = ContratoPagamento::with('ContratoFixacao')
            ->where('codcontrato', $codcontrato)
            ->findOrFail($codpagamento);
        $m->datarecebido = $request->datarecebido;

        $fixacao = $m->ContratoFixacao;
        $usd = $fixacao && $fixacao->usd;
        if ($usd) {
            // R$ recebido nasce aqui: sacas × preço(US$) × cotação do dia. Fonte
            // única (sem drift entre front e back); a cotação fica auditada.
            $m->cotacaorecebido = $request->cotacaorecebido;
            $m->valorrecebido = round((float) $m->sacas * (float) $fixacao->preco * (float) $request->cotacaorecebido, 2);
        } else {
            // BRL: valor recebido é o digitado; sem cotação.
            $m->valorrecebido = $request->valorrecebido;
            $m->cotacaorecebido = null;
        }

        if ($request->filled('codportador')) {
            $m->codportador = $request->codportador;
        }
        $m->update();

        return new ContratoPagamentoResource($m->load('Portador'));
    }

    public function inativar(Request $request, $codcontrato, $codpagamento)
    {
        $m = ContratoPagamento::where('codcontrato', $codcontrato)->findOrFail($codpagamento);
        MgService::inativar($m);
        return new ContratoPagamentoResource($m->fresh('Portador'));
    }

    public function ativar(Request $request, $codcontrato, $codpagamento)
    {
        $m = ContratoPagamento::where('codcontrato', $codcontrato)->findOrFail($codpagamento);
        MgService::ativar($m);
        return new ContratoPagamentoResource($m->fresh('Portador'));
    }
}
