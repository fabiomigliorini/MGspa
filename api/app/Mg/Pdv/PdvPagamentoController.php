<?php

namespace Mg\Pdv;

use Illuminate\Support\Facades\DB;
use Mg\Pagamento\PagamentoDetalheResource;
use Mg\Pagamento\PagamentoListaResource;
use Mg\Pagamento\PagamentoListaService;
use Mg\Pagamento\PagamentoPendenciaService;
use Mg\Pagamento\PagamentoTituloStoreRequest;
use Mg\Titulo\TituloAbertosFechamentoService;

/**
 * Pagamentos vistos e feitos no PDV (M6.1 doc-3): a listagem unica travada
 * no PDV, receber titulo / pagar vale do cliente, estorno e recibo termico.
 * Rotas v1/pdv/pagamento; o dispositivo autoriza.
 */
class PdvPagamentoController
{
    public function index(PdvRequest $request)
    {
        $pdv = PdvService::autoriza($request->pdv);
        $filtros = $request->only(PagamentoListaService::FILTROS);
        $filtros['codpdv'] = $pdv->codpdv;
        unset($filtros['codfilial']);
        return PagamentoListaResource::collection(PagamentoListaService::listar($filtros));
    }

    // pagamentos nao resolvidos (a forma "Ja' recebido" do wizard e a tela),
    // pelo papel do usuario nos portadores
    public function pendentes(PdvRequest $request)
    {
        $pdv = PdvService::autoriza($request->pdv);
        // no PDV, so' os da loja dele (o admin tambem), o cliente primeiro
        $filtros = $request->only(['codpessoa', 'sentido', 'codpagamento', 'codpessoaprimeiro']);
        $filtros['codfilial'] = $pdv->codfilial;
        return ['data' => PagamentoPendenciaService::formatar(PagamentoPendenciaService::listar($filtros))];
    }

    // "Ja' recebido" na venda: amarra o pagamento sem amarracao na venda aberta
    public function amarrarVenda(PdvRequest $request, int $codnegocio, int $codpagamento)
    {
        $pdv = PdvService::autoriza($request->pdv);
        DB::beginTransaction();
        $pag = PdvPagamentoService::amarrarVenda($pdv, $codnegocio, $codpagamento);
        DB::commit();
        return ['data' => PdvNegocioPagamentoService::pagamento($pag)];
    }

    // "Ja' lancado": os digitados que casam com este integrado
    public function duplicados(PdvRequest $request, int $id)
    {
        PdvService::autoriza($request->pdv);
        return ['data' => PagamentoPendenciaService::duplicados(\Mg\Pagamento\Pagamento::findOrFail($id))];
    }

    // "Ja' lancado": fica este (o integrado), o digitado e' cancelado como
    // registro indevido e as amarracoes dele passam para este
    public function jaLancado(PdvRequest $request, int $id)
    {
        PdvService::autoriza($request->pdv);
        $request->validate([
            'codpagamento' => 'required|integer|exists:tblpagamento,codpagamento',
            'justificativa' => 'required|string|min:5|max:300',
        ]);
        DB::beginTransaction();
        $integrado = \Mg\Pagamento\Pagamento::findOrFail($id);
        $digitado = \Mg\Pagamento\Pagamento::findOrFail($request->codpagamento);
        PagamentoPendenciaService::autorizar($digitado, 'Cancelar o digitado');
        $pag = PagamentoPendenciaService::jaLancado($integrado, $digitado, $request->justificativa);
        DB::commit();
        return new PagamentoDetalheResource($pag);
    }

    // devolver o PIX ou o cartao que entrou por engano (o que esta' livre)
    public function devolver(PdvRequest $request, int $id)
    {
        PdvService::autoriza($request->pdv);
        $request->validate([
            'valor' => 'required|numeric|min:0.01',
            'justificativa' => 'required|string|min:5|max:300',
        ]);
        DB::beginTransaction();
        $pag = \Mg\Pagamento\Pagamento::findOrFail($id);
        PagamentoPendenciaService::autorizar($pag, 'Devolver');
        $dev = PagamentoPendenciaService::devolver($pag, (float) $request->valor, $request->justificativa);
        DB::commit();
        return new PagamentoDetalheResource($dev);
    }

    public function show(PdvRequest $request, int $id)
    {
        $pdv = PdvService::autoriza($request->pdv);
        return new PagamentoDetalheResource(PdvPagamentoService::carregar($pdv, $id));
    }

    // títulos abertos para o seletor (os mesmos filtros e formato do contas); no PDV só com
    // pessoa ou grupo econômico
    public function titulos(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        if (empty($request->codpessoa) && empty($request->codgrupoeconomico)) {
            abort(422, 'Informe a pessoa ou o grupo econômico!');
        }
        return ['data' => TituloAbertosFechamentoService::listar($request->only([
            'codpessoa', 'codgrupoeconomico', 'codfilial',
            'vencimento_de', 'vencimento_ate', 'natureza',
            'codtipotitulo', 'codcontacontabil', 'codportador',
        ]))];
    }

    public function originais(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        $request->validate(['codpessoa' => 'required|integer']);
        return ['data' => PdvPagamentoService::originais((int) $request->codpessoa)];
    }

    public function store(PagamentoTituloStoreRequest $request)
    {
        $pdv = PdvService::autoriza($request->pdv);
        DB::beginTransaction();
        $pags = PdvPagamentoService::baixar($pdv, $request->validated());
        DB::commit();
        return PagamentoDetalheResource::collection($pags);
    }

    // desamarrar: estorna as baixas (todas ou as linhas escolhidas); o
    // pagamento continua. "estornar" e' o nome antigo da mesma rota.
    public function desamarrar(PdvRequest $request, int $id)
    {
        $pdv = PdvService::autoriza($request->pdv);
        $request->validate([
            'justificativa' => 'required|string|min:5|max:300',
            'codmovimentos' => 'nullable|array',
            'codmovimentos.*' => 'integer',
        ]);
        DB::beginTransaction();
        $pag = PdvPagamentoService::desamarrar($pdv, $id, $request->justificativa, $request->codmovimentos);
        DB::commit();
        return new PagamentoDetalheResource($pag);
    }

    public function estornar(PdvRequest $request, int $id)
    {
        return $this->desamarrar($request, $id);
    }

    // cancelar: so' o pagamento manual ja' desamarrado
    public function cancelar(PdvRequest $request, int $id)
    {
        $pdv = PdvService::autoriza($request->pdv);
        $request->validate(['justificativa' => 'required|string|min:5|max:300']);
        DB::beginTransaction();
        $pag = PdvPagamentoService::cancelar($pdv, $id, $request->justificativa);
        DB::commit();
        return new PagamentoDetalheResource($pag);
    }

    // recibo termico de um recebimento (um ou mais pagamentos)
    public function imprimirRecibo(PdvRequest $request, string $impressora)
    {
        $pdv = PdvService::autoriza($request->pdv);
        $request->validate(['codpagamento' => 'required|array|min:1', 'codpagamento.*' => 'integer']);
        foreach ($request->codpagamento as $cod) {
            PdvPagamentoService::carregar($pdv, (int) $cod);
        }
        PdvPagamentoService::imprimirRecibo(array_map('intval', $request->codpagamento), $impressora);
    }

    // PDF do recibo termico (rota assinada, aberta pela impressora)
    public function recibo(string $codpagamentos)
    {
        $cods = array_filter(array_map('intval', explode(',', $codpagamentos)));
        return response()->make(PdvPagamentoService::reciboPdf($cods), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Recibo' . implode('-', $cods) . '.pdf"',
        ]);
    }
}
