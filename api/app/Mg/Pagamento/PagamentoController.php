<?php

namespace Mg\Pagamento;

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

    // Divergencias (TASK-218): vendas que nao fecham e pagamentos que nao
    // batem com as baixas ou com a venda; quem ve e' quem confere (painel)
    public function divergencias(Request $request)
    {
        $request->validate([
            'caso' => 'nullable|string|in:' . implode(',', array_keys(PagamentoDivergenciaService::CASOS)),
            'data_de' => 'nullable|date',
            'data_ate' => 'nullable|date',
            'codfilial' => 'nullable|integer',
        ]);
        return response()->json(PagamentoDivergenciaService::listar(
            $request->only(['caso', 'data_de', 'data_ate', 'codfilial'])
        ));
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

    // o lapis: pessoa e observacao; a data so' do manual
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
        $pag = PagamentoTituloService::atualizar($pag, $dados);
        DB::commit();
        return new PagamentoDetalheResource($pag);
    }

    // o "Referente a" (sai no recibo), em qualquer pagamento efetivado
    public function referente(Request $request, int $id)
    {
        Autorizador::autoriza(self::GRUPOS_MUTACAO);
        $request->validate(['referente' => 'nullable|string|max:255']);
        DB::beginTransaction();
        $pag = PagamentoTituloService::alterarReferente(PagamentoListaService::carregar($id), $request->referente);
        DB::commit();
        return new PagamentoDetalheResource($pag);
    }

    // cancelar: o fato nao existiu como foi lancado; estorna as baixas junto
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
        // o relatorio carrega tudo na memoria: exige o periodo
        $request->validate([
            'transacao_de' => 'required|date',
            'transacao_ate' => 'required|date',
        ]);
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

    // Titulos pagos no periodo, para a contabilidade (TASK-256)
    public function contabilidade(Request $request)
    {
        Autorizador::autoriza(['Administrador', 'Financeiro']);
        $request->validate([
            'transacao_de' => 'required|date',
            'transacao_ate' => 'required|date',
            'codfilial' => 'nullable|integer',
            'codportador' => 'nullable|integer',
            'gerencial' => 'nullable|boolean',
        ]);
        $filtros = $request->only(['transacao_de', 'transacao_ate', 'codfilial', 'codportador']);
        if ($request->filled('gerencial')) {
            $filtros['gerencial'] = $request->boolean('gerencial');
        }
        if ($request->boolean('html')) {
            return response(PagamentoContabilidadeService::html($filtros), 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }
        return response(PagamentoContabilidadeService::pdf($filtros), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="titulos-pagos.pdf"',
        ]);
    }

    // ==== Recibos ====

    // o recibo A5 (o mesmo texto do cupom da termica): PagamentoReciboService
    public function recibo(int $id)
    {
        Autorizador::autoriza(self::GRUPOS_RECIBO);
        $pag = Pagamento::findOrFail($id);
        if (!PagamentoTituloAutorizador::podeVer($pag, Auth::user()->codusuario)) {
            abort(403, 'Pagamento não pertence à sua filial.');
        }
        return response(PagamentoReciboService::pdf($id), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="recibo-' . $id . '.pdf"',
        ]);
    }
}
