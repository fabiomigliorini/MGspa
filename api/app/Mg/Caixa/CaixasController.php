<?php

namespace Mg\Caixa;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Conferencia\ConferenciaAutorizador;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoDetalheResource;
use Mg\Pagamento\PagamentoListaService;
use Mg\Pagamento\PagamentoService;
use Mg\Pagamento\TransferenciaResource;
use Mg\Portador\Portador;
use Mg\Usuario\Autorizador;

/**
 * Pagina Caixas do contas (M11 doc-3): portadores em especie com saldo e
 * sessao, transferencias (registrar, confirmar, cancelar). Quem opera cada
 * lado: TransferenciaAutorizador (decisao 23).
 */
class CaixasController extends Controller
{
    private const GRUPOS = ['Financeiro', 'Gerente', 'Caixa'];

    public function caixas(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);
        $request->validate(['codfilial' => 'nullable|integer']);
        return ['data' => CaixasService::caixas($request->codfilial ? (int) $request->codfilial : null)];
    }

    public function saldo(int $id)
    {
        $portador = Portador::findOrFail($id);
        if (!$portador->ehGaveta() && !ConferenciaAutorizador::pode($portador->codfilial)) {
            abort(403, 'Saldo do portador: só Financeiro, Administrador ou Gerente da filial!');
        }
        $ultima = $portador->ehGaveta() ? CaixaService::ultimaSessao($portador->codportador) : null;
        if (!CaixasService::saldoVisivel($portador, $ultima)) {
            abort(403, 'O saldo do caixa só aparece depois da conferência do gerente (às cegas).');
        }
        return ['data' => CaixasService::saldo($portador)];
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
        ]);
        $pag = DB::transaction(fn () => PagamentoService::transferir(
            Portador::findOrFail($dados['codportadororigem']),
            Portador::findOrFail($dados['codportadordestino']),
            (float) $dados['valor'],
            $dados['observacoes'] ?? null
        ));
        return new PagamentoDetalheResource(PagamentoListaService::carregar($pag->codpagamento));
    }

    public function confirmar(int $id)
    {
        $pag = DB::transaction(fn () => PagamentoService::confirmar(Pagamento::findOrFail($id)));
        return new PagamentoDetalheResource(PagamentoListaService::carregar($pag->codpagamento));
    }

    public function cancelar(Request $request, int $id)
    {
        $request->validate(['justificativa' => 'required|string|min:5|max:300']);
        $pag = DB::transaction(fn () => PagamentoService::cancelarTransferencia(Pagamento::findOrFail($id), $request->justificativa));
        return new PagamentoDetalheResource(PagamentoListaService::carregar($pag->codpagamento));
    }
}
