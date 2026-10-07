<?php

namespace Mg\Conferencia;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Maquineta\Maquineta;
use Mg\Maquineta\MaquinetaLote;
use Mg\Maquineta\MaquinetaLoteService;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioAcerto;
use Mg\Negocio\NegocioAnexoService;
use Mg\Pagamento\Pagamento;

/**
 * Tela Fechamentos do contas (M9 doc-3): pendencias da filial e a
 * conferencia de cada uma (lote da maquineta, cheque, vale, venda
 * desbalanceada), com as correcoes. Rotas v1/conferencia. A sessao da
 * gaveta fecha na tela do caixa (v1/caixa, M13).
 */
class ConferenciaController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['codfilial' => 'nullable|integer']);
        return ['data' => ConferenciaService::pendencias($request->codfilial ? (int) $request->codfilial : null)];
    }

    // ---- lote da maquineta ----

    private function lote(int $id): MaquinetaLote
    {
        $lote = MaquinetaLote::with('Maquineta')->findOrFail($id);
        ConferenciaAutorizador::autorizarMaquineta($lote->Maquineta);
        return $lote;
    }

    public function showLote(int $id)
    {
        return (new MaquinetaLoteResource($this->lote($id)))->comLancamentos();
    }

    public function lotes(Request $request, int $codmaquineta)
    {
        $maquineta = Maquineta::findOrFail($codmaquineta);
        ConferenciaAutorizador::autorizarMaquineta($maquineta);
        $lotes = MaquinetaLote::where('codmaquineta', $codmaquineta)
            ->orderBy('codmaquinetalote', 'desc')
            ->paginate(30);
        return MaquinetaLoteResource::collection($lotes);
    }

    public function fecharLote(Request $request, int $id)
    {
        $dados = $request->validate([
            'creditoinformado' => 'required|numeric',
            'debitoinformado' => 'required|numeric',
            'observacoes' => 'nullable|string|max:500',
        ]);
        $lote = $this->lote($id);
        DB::transaction(fn () => MaquinetaLoteService::fechar(
            $lote,
            (float) $dados['creditoinformado'],
            (float) $dados['debitoinformado'],
            $dados['observacoes'] ?? null
        ));
        return (new MaquinetaLoteResource($lote->fresh('Maquineta')))->comLancamentos();
    }

    public function reabrirLote(int $id)
    {
        $lote = $this->lote($id);
        DB::transaction(fn () => MaquinetaLoteService::reabrir($lote));
        return (new MaquinetaLoteResource($lote->fresh('Maquineta')))->comLancamentos();
    }

    public function fotoLote(Request $request, int $id)
    {
        $request->validate(['anexoBase64' => 'required|string']);
        $lote = $this->lote($id);
        MaquinetaLoteService::anexarFoto($lote, $request->anexoBase64);
        return new MaquinetaLoteResource($lote);
    }

    public function mostrarFotoLote(int $id, string $arquivo)
    {
        $lote = $this->lote($id);
        return NegocioAnexoService::mostrarFoto(MaquinetaLoteService::diretorioFoto($lote), $arquivo);
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
            'codmaquinetalote' => 'nullable|integer|exists:tblmaquinetalote,codmaquinetalote',
            'bandeira' => 'nullable|integer',
            'autorizacao' => 'nullable|string|max:40',
            'parcelas' => 'nullable|integer|min:1',
            'justificativa' => 'required|string|min:5|max:300',
        ]);
        $pag = $this->pagamento($id);
        $pag = DB::transaction(fn () => PagamentoCorrecaoService::corrigir(
            $pag,
            $request->only(['meio', 'principal', 'codmaquineta', 'codmaquinetalote', 'bandeira', 'autorizacao', 'parcelas']),
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
