<?php

namespace Mg\Pagamento;

use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

/**
 * Recebimentos e pagamentos de titulos no contas (M6 doc-3; era a
 * Liquidacao). Rotas v1/pagamento.
 */
class PagamentoTituloController extends Controller
{
    private const GRUPOS_LEITURA = ['Administrador', 'Financeiro', 'Cobranca', 'Gerente', 'Caixa'];
    private const GRUPOS_MUTACAO = ['Administrador', 'Financeiro', 'Cobranca', 'Gerente', 'Caixa'];
    private const GRUPOS_RECIBO = ['Recursos Humanos', 'Financeiro', 'Administrador', 'Cobranca', 'Gerente', 'Caixa'];

    const FILTROS = [
        'codpagamento',
        'codpessoa',
        'codgrupoeconomico',
        'codgrupocliente',
        'codportador',
        'meio',
        'sentido',
        'codusuariocriacao',
        'cancelado',
        'criacao_de',
        'criacao_ate',
        'lancamento_de',
        'lancamento_ate',
    ];

    public function index(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS_LEITURA);
        $filtros = $request->only(self::FILTROS);
        $filtros['filiais_permitidas'] = PagamentoTituloAutorizador::filiaisRestritas(Auth::user()->codusuario);
        return PagamentoTituloListaResource::collection(PagamentoTituloService::listar($filtros));
    }

    public function show(int $id)
    {
        Autorizador::autoriza(self::GRUPOS_LEITURA);
        $pag = PagamentoTituloService::carregar($id);
        if (!PagamentoTituloAutorizador::podeVer($pag, Auth::user()->codusuario)) {
            abort(403, 'Pagamento não pertence à sua filial.');
        }
        return new PagamentoTituloDetalheResource($pag);
    }

    // recebimento ou pagamento conforme o liquido dos titulos
    public function store(PagamentoTituloStoreRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS_MUTACAO);
        $dados = $request->validated();
        $codportador = $dados['codportador'] ?? null;
        if (!PagamentoTituloAutorizador::podeCriar(Auth::user()->codusuario, $codportador ? (int) $codportador : null)) {
            abort(403, 'Portador não pertence à sua filial.');
        }
        DB::beginTransaction();
        $liquido = PagamentoTituloService::liquido($dados['titulos']);
        $pag = ($liquido > 0)
            ? PagamentoTituloService::pagar($dados)
            : PagamentoTituloService::receber($dados);
        DB::commit();
        return new PagamentoTituloDetalheResource($pag);
    }

    // corrige pessoa, portador, meio, data e observacao
    public function update(PagamentoTituloUpdateRequest $request, int $id)
    {
        Autorizador::autoriza(self::GRUPOS_MUTACAO);
        $dados = $request->validated();
        DB::beginTransaction();
        $pag = PagamentoTituloService::carregar($id);
        $bloqueio = PagamentoTituloAutorizador::motivoBloqueioEdicao($pag, Auth::user()->codusuario);
        if ($bloqueio !== null) {
            abort(403, $bloqueio);
        }
        if (!empty($dados['codportador']) && !PagamentoTituloAutorizador::podeCriar(Auth::user()->codusuario, (int) $dados['codportador'])) {
            abort(403, 'Portador não pertence à sua filial.');
        }
        $pag = PagamentoTituloService::atualizar($pag, $dados);
        DB::commit();
        return new PagamentoTituloDetalheResource($pag);
    }

    public function estornar(Request $request, int $id)
    {
        Autorizador::autoriza(self::GRUPOS_MUTACAO);
        $request->validate(['justificativa' => 'required|string|min:5|max:300']);
        DB::beginTransaction();
        $pag = PagamentoTituloService::carregar($id);
        $bloqueio = PagamentoTituloAutorizador::motivoBloqueioEstorno($pag, Auth::user()->codusuario);
        if ($bloqueio !== null) {
            abort(403, $bloqueio);
        }
        $pag = PagamentoTituloService::estornar($pag, $request->justificativa);
        DB::commit();
        return new PagamentoTituloDetalheResource($pag);
    }

    public function relatorio(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS_LEITURA);
        $filtros = $request->only(self::FILTROS);
        $filtros['filiais_permitidas'] = PagamentoTituloAutorizador::filiaisRestritas(Auth::user()->codusuario);
        if ($request->boolean('html')) {
            return response(PagamentoRelatorioService::html($filtros), 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }
        return response(PagamentoRelatorioService::pdf($filtros), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="recebimentos-e-pagamentos.pdf"',
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
        $pag = PagamentoTituloService::query()->with([
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
