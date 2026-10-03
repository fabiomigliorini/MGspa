<?php

namespace Mg\Caixa;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoDetalheResource;
use Mg\Pagamento\PagamentoListaService;
use Mg\Pagamento\PagamentoService;
use Mg\Pagamento\TransferenciaResource;
use Mg\Portador\Portador;
use Mg\Portador\PortadorPeriodoResource;
use Mg\Portador\PortadorPeriodoService;
use Mg\Usuario\Autorizador;

/**
 * Transferencias entre portadores (M11 doc-3): registrar, confirmar,
 * cancelar e a listagem. Quem opera cada lado: TransferenciaAutorizador
 * (decisao 23). Os portadores com saldo e sessao foram para o painel
 * /portador (doc-4).
 */
class CaixasController extends Controller
{
    private const GRUPOS = ['Financeiro', 'Gerente', 'Caixa'];

    // o pagamento e os periodos que ele mexeu, dos dois lados (R14 doc-4)
    private function pagamento(Pagamento $pag)
    {
        return (new PagamentoDetalheResource(PagamentoListaService::carregar($pag->codpagamento)))
            ->additional(['periodos' => PortadorPeriodoResource::lista(PortadorPeriodoService::afetados($pag->codpagamento))]);
    }

    public function transferencias(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);
        return TransferenciaResource::collection(CaixasService::transferencias($request->only([
            'codfilial', 'codportador', 'estado', 'transacao_de', 'transacao_ate',
        ])));
    }

    public function transferir(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);
        $dados = $request->validate([
            'codportadororigem' => 'required|integer|exists:tblportador,codportador',
            'codportadordestino' => 'required|integer|exists:tblportador,codportador|different:codportadororigem',
            'valor' => 'required|numeric|min:0.01',
            'observacoes' => 'nullable|string|max:300',
            'transacao' => 'nullable|date',
        ]);
        $pag = DB::transaction(fn () => PagamentoService::transferir(
            Portador::findOrFail($dados['codportadororigem']),
            Portador::findOrFail($dados['codportadordestino']),
            (float) $dados['valor'],
            $dados['observacoes'] ?? null,
            null,
            !empty($dados['transacao']) ? \Carbon\Carbon::parse($dados['transacao']) : null
        ));
        return $this->pagamento($pag);
    }

    public function confirmar(int $id)
    {
        $pag = DB::transaction(fn () => PagamentoService::confirmar(Pagamento::findOrFail($id)));
        return $this->pagamento($pag);
    }

    public function cancelar(Request $request, int $id)
    {
        $request->validate(['justificativa' => 'required|string|min:5|max:300']);
        $pag = DB::transaction(fn () => PagamentoService::cancelarTransferencia(Pagamento::findOrFail($id), $request->justificativa));
        return $this->pagamento($pag);
    }
}
