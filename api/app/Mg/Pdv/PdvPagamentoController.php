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
        PdvService::autoriza($request->pdv);
        $filtros = $request->only(['codpessoa', 'sentido', 'codpagamento']);
        return ['data' => PagamentoPendenciaService::formatar(PagamentoPendenciaService::listar($filtros))];
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

    public function estornar(PdvRequest $request, int $id)
    {
        $pdv = PdvService::autoriza($request->pdv);
        $request->validate(['justificativa' => 'required|string|min:5|max:300']);
        DB::beginTransaction();
        $pag = PdvPagamentoService::estornar($pdv, $id, $request->justificativa);
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
