<?php

namespace Mg\Pagamento;

use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

/**
 * Pagamentos no contas (M6 doc-3, era a Liquidacao; listagem unica no
 * M6.1): lista todos os pagamentos e baixa titulos com o wizard de
 * cobranca. Rotas v1/pagamento.
 */
class PagamentoController extends Controller
{
    private const GRUPOS_LEITURA = ['Administrador', 'Financeiro', 'Cobranca', 'Gerente', 'Caixa'];
    private const GRUPOS_MUTACAO = ['Administrador', 'Financeiro', 'Cobranca', 'Gerente', 'Caixa'];
    private const GRUPOS_RECIBO = ['Recursos Humanos', 'Financeiro', 'Administrador', 'Cobranca', 'Gerente', 'Caixa'];

    public function index(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS_LEITURA);
        $filtros = $request->only(PagamentoListaService::FILTROS);
        $filtros['filiais_permitidas'] = PagamentoTituloAutorizador::filiaisRestritas(Auth::user()->codusuario);
        return PagamentoListaResource::collection(PagamentoListaService::listar($filtros));
    }

    public function show(int $id)
    {
        Autorizador::autoriza(self::GRUPOS_LEITURA);
        $pag = PagamentoListaService::carregar($id);
        if (!PagamentoTituloAutorizador::podeVer($pag, Auth::user()->codusuario)) {
            abort(403, 'Pagamento não pertence à sua filial.');
        }
        return new PagamentoDetalheResource($pag);
    }

    // pagamentos nao resolvidos: o saldo (pago - devolvido) nao bate com o
    // que esta' amarrado (tela "Pagamentos nao resolvidos" e a forma "Ja'
    // recebido" do wizard); so' os portadores em que o usuario tem papel
    public function pendentes(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS_LEITURA);
        $filtros = $request->only(['codpessoa', 'codfilial', 'sentido', 'codpagamento']);
        return ['data' => PagamentoPendenciaService::formatar(PagamentoPendenciaService::listar($filtros))];
    }

    // baixa os titulos com as formas do wizard (um pagamento por forma;
    // recebimento ou pagamento conforme o liquido dos titulos)
    public function store(PagamentoTituloStoreRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS_MUTACAO);
        $dados = $request->validated();
        $bloqueio = PagamentoTituloAutorizador::motivoBloqueioBaixa(Auth::user()->codusuario, $dados);
        if ($bloqueio !== null) {
            abort(403, $bloqueio);
        }
        DB::beginTransaction();
        $pags = PagamentoTituloService::baixar($dados);
        DB::commit();
        return PagamentoDetalheResource::collection($pags);
    }

    // corrige pessoa, portador, meio, data e observacao
    public function update(PagamentoTituloUpdateRequest $request, int $id)
    {
        Autorizador::autoriza(self::GRUPOS_MUTACAO);
        $dados = $request->validated();
        DB::beginTransaction();
        $pag = PagamentoListaService::carregar($id);
        $bloqueio = PagamentoTituloAutorizador::motivoBloqueioEdicao($pag, Auth::user()->codusuario);
        if ($bloqueio !== null) {
            abort(403, $bloqueio);
        }
        if (!empty($dados['codportador']) && !PagamentoTituloAutorizador::podeCriar(Auth::user()->codusuario, (int) $dados['codportador'])) {
            abort(403, 'Portador não pertence à sua filial.');
        }
        $pag = PagamentoTituloService::atualizar($pag, $dados);
        DB::commit();
        return new PagamentoDetalheResource($pag);
    }

    // desamarrar: estorna as baixas (todas ou as linhas escolhidas); o
    // pagamento continua, sem amarracao. "estornar" e' o nome antigo.
    public function desamarrar(Request $request, int $id)
    {
        Autorizador::autoriza(self::GRUPOS_MUTACAO);
        $request->validate([
            'justificativa' => 'required|string|min:5|max:300',
            'codmovimentos' => 'nullable|array',
            'codmovimentos.*' => 'integer',
        ]);
        DB::beginTransaction();
        $pag = PagamentoListaService::carregar($id);
        $bloqueio = PagamentoTituloAutorizador::motivoBloqueioEstorno($pag, Auth::user()->codusuario);
        if ($bloqueio !== null) {
            abort(403, $bloqueio);
        }
        $pag = PagamentoTituloService::desamarrar($pag, $request->justificativa, $request->codmovimentos);
        DB::commit();
        return new PagamentoDetalheResource($pag);
    }

    public function estornar(Request $request, int $id)
    {
        return $this->desamarrar($request, $id);
    }

    // cancelar: o fato nao existiu (manual digitado errado, ja' desamarrado)
    public function cancelar(Request $request, int $id)
    {
        Autorizador::autoriza(self::GRUPOS_MUTACAO);
        $request->validate(['justificativa' => 'required|string|min:5|max:300']);
        DB::beginTransaction();
        $pag = PagamentoListaService::carregar($id);
        $bloqueio = PagamentoTituloAutorizador::motivoBloqueioEstorno($pag, Auth::user()->codusuario);
        if ($bloqueio !== null) {
            abort(403, $bloqueio);
        }
        $pag = PagamentoTituloService::cancelar($pag, $request->justificativa);
        DB::commit();
        return new PagamentoDetalheResource($pag);
    }

    public function relatorio(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS_LEITURA);
        $filtros = $request->only(PagamentoListaService::FILTROS);
        $filtros['filiais_permitidas'] = PagamentoTituloAutorizador::filiaisRestritas(Auth::user()->codusuario);
        if ($request->boolean('html')) {
            return response(PagamentoRelatorioService::html($filtros), 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }
        return response(PagamentoRelatorioService::pdf($filtros), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="pagamentos.pdf"',
        ]);
    }

    // ==== Recibos ====

    public function recibo(int $id)
    {
        $pag = static::carregarParaRecibo($id);
        $html = '';
        if (PagamentoTituloService::temRecebimento($pag)) {
            $html .= view('pagamento.recibo-recebimento', compact('pag'))->render();
        }
        if (PagamentoTituloService::temPagamento($pag)) {
            $html .= view('pagamento.recibo-pagamento', compact('pag'))->render();
        }
        if (empty($html)) {
            abort(422, 'Pagamento sem valores para recibo.');
        }
        return static::pdfResponse($html, 'recibo-' . $id);
    }

    public function reciboRecebimento(int $id)
    {
        $pag = static::carregarParaRecibo($id);
        if (!PagamentoTituloService::temRecebimento($pag)) {
            abort(422, 'Pagamento sem recebimentos para recibo de recebimento.');
        }
        return static::pdfResponse(view('pagamento.recibo-recebimento', compact('pag'))->render(), 'recibo-recebimento-' . $id);
    }

    public function reciboPagamento(int $id)
    {
        $pag = static::carregarParaRecibo($id);
        if (!PagamentoTituloService::temPagamento($pag)) {
            abort(422, 'Pagamento sem pagamentos para recibo de pagamento.');
        }
        return static::pdfResponse(view('pagamento.recibo-pagamento', compact('pag'))->render(), 'recibo-pagamento-' . $id);
    }

    protected static function carregarParaRecibo(int $id): Pagamento
    {
        Autorizador::autoriza(self::GRUPOS_RECIBO);
        $pag = Pagamento::with([
            'MovimentoTituloS.Titulo.Filial.Pessoa.Cidade.Estado',
            'MovimentoTituloS.Titulo.PeriodoColaboradorS.ColaboradorRubricaS',
            'Pessoa.Cidade.Estado',
            'UsuarioCriacao',
        ])->findOrFail($id);
        if (!PagamentoTituloAutorizador::podeVer($pag, Auth::user()->codusuario)) {
            abort(403, 'Pagamento não pertence à sua filial.');
        }
        if ($pag->estado == PagamentoService::ESTADO_CANCELADO) {
            abort(422, 'Pagamento estornado.');
        }
        return $pag;
    }

    protected static function pdfResponse(string $html, string $filename)
    {
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();
        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '.pdf"',
        ]);
    }
}
