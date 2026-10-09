<?php

namespace Mg\Conferencia;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioAcerto;
use Mg\Pagamento\Pagamento;

/**
 * Tela Fechamentos do contas (M9 doc-3): pendencias da filial e a
 * conferencia de cada uma (cheque, vale, venda desbalanceada), com as
 * correcoes. Rotas v1/conferencia. A sessao da gaveta fecha na tela do
 * caixa (v1/caixa, M13); o periodo da maquineta, na tela da maquineta
 * (MaquinetaPeriodoController, M9.8).
 */
class ConferenciaController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['codfilial' => 'nullable|integer']);
        return ['data' => ConferenciaService::pendencias($request->codfilial ? (int) $request->codfilial : null)];
    }

    // ---- venda desbalanceada ----

    private function negocio(int $id): Negocio
    {
        $negocio = Negocio::findOrFail($id);
        ConferenciaAutorizador::autorizar($negocio->codfilial);
        return $negocio;
    }

    public function showVenda(int $id)
    {
        return ['data' => VendaConferenciaService::detalhe($this->negocio($id))];
    }

    public function acertarVenda(Request $request, int $id)
    {
        $dados = $request->validate([
            'destino' => 'required|in:P,C,D,R',
            'codpessoa' => 'nullable|integer|exists:tblpessoa,codpessoa',
            'justificativa' => 'required|string|min:5|max:300',
        ]);
        $negocio = $this->negocio($id);
        DB::transaction(fn () => VendaConferenciaService::acertar(
            $negocio,
            $dados['destino'],
            $dados['codpessoa'] ?? null,
            $dados['justificativa']
        ));
        return ['data' => VendaConferenciaService::detalhe($negocio->fresh())];
    }

    public function desfazerAcerto(int $id)
    {
        $acerto = NegocioAcerto::findOrFail($id);
        $negocio = $this->negocio($acerto->codnegocio);
        DB::transaction(fn () => VendaConferenciaService::desfazer($acerto));
        return ['data' => VendaConferenciaService::detalhe($negocio)];
    }

    public function incluirPagamento(Request $request, int $id)
    {
        $dados = $request->validate([
            'meio' => 'required|integer',
            'principal' => 'required|numeric|gt:0',
            'codmaquineta' => 'nullable|integer|exists:tblmaquineta,codmaquineta',
            'bandeira' => 'nullable|integer',
            'autorizacao' => 'nullable|string|max:40',
            'parcelas' => 'nullable|integer|min:1',
            'justificativa' => 'required|string|min:5|max:300',
        ]);
        $negocio = $this->negocio($id);
        DB::transaction(fn () => PagamentoCorrecaoService::incluir($negocio, $dados, $dados['justificativa']));
        return ['data' => VendaConferenciaService::detalhe($negocio->fresh())];
    }

    // ---- pagamento: correcao, registro indevido, cheque e vale ----

    private function pagamento(int $id): Pagamento
    {
        $pag = Pagamento::findOrFail($id);
        PagamentoCorrecaoService::autorizar($pag);
        return $pag;
    }

    public function corrigirPagamento(Request $request, int $id)
    {
        $dados = $request->validate([
            'meio' => 'nullable|integer',
            'principal' => 'nullable|numeric|gt:0',
            'codmaquineta' => 'nullable|integer|exists:tblmaquineta,codmaquineta',
            'bandeira' => 'nullable|integer',
            'autorizacao' => 'nullable|string|max:40',
            'parcelas' => 'nullable|integer|min:1',
            'justificativa' => 'required|string|min:5|max:300',
        ]);
        $pag = $this->pagamento($id);
        $pag = DB::transaction(fn () => PagamentoCorrecaoService::corrigir(
            $pag,
            $request->only(['meio', 'principal', 'codmaquineta', 'bandeira', 'autorizacao', 'parcelas']),
            $dados['justificativa']
        ));
        return new ConferenciaPagamentoResource($pag);
    }

    public function indevido(Request $request, int $id)
    {
        $dados = $request->validate(['justificativa' => 'required|string|min:5|max:300']);
        $pag = $this->pagamento($id);
        $pag = DB::transaction(fn () => PagamentoCorrecaoService::indevido($pag, $dados['justificativa']));
        return new ConferenciaPagamentoResource($pag);
    }

    public function conferirPagamento(int $id)
    {
        $pag = $this->pagamento($id);
        return new ConferenciaPagamentoResource(ConferenciaService::conferirPagamento($pag));
    }

    public function reabrirPagamento(int $id)
    {
        $pag = $this->pagamento($id);
        return new ConferenciaPagamentoResource(ConferenciaService::reabrirPagamento($pag));
    }
}
