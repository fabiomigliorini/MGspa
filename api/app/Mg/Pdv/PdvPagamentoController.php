<?php

namespace Mg\Pdv;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mg\Pagamento\PagamentoDetalheResource;
use Mg\Pagamento\PagamentoListaResource;
use Mg\Pagamento\PagamentoListaService;
use Mg\Pagamento\PagamentoTituloService;
use Mg\Pagamento\PagamentoTituloStoreRequest;
use Mg\Titulo\TituloAbertosFechamentoService;

/**
 * Pagamentos vistos e feitos no PDV (M6.1 doc-3): a listagem unica travada
 * no PDV, receber titulo / pagar vale do cliente, estorno e recibo termico.
 * Rotas v1/pdv/pagamento; o dispositivo autoriza (middleware `pdv`).
 */
class PdvPagamentoController
{
    public function index(Request $request)
    {
        $pdv = $request->attributes->get('pdv');
        $filtros = $request->only(PagamentoListaService::FILTROS);
        $filtros['codpdv'] = $pdv->codpdv;
        unset($filtros['codfilial']);
        return PagamentoListaResource::collection(PagamentoListaService::listar($filtros));
    }

    public function show(Request $request, int $id)
    {
        $pdv = $request->attributes->get('pdv');
        return new PagamentoDetalheResource(PdvPagamentoService::carregar($pdv, $id));
    }

    // títulos abertos para o seletor (os mesmos filtros e formato do contas); no PDV só com
    // pessoa ou grupo econômico
    public function titulos(Request $request)
    {
        if (empty($request->codpessoa) && empty($request->codgrupoeconomico)) {
            abort(422, 'Informe a pessoa ou o grupo econômico!');
        }
        return ['data' => TituloAbertosFechamentoService::listar($request->only([
            'codpessoa', 'codgrupoeconomico', 'codfilial',
            'vencimento_de', 'vencimento_ate', 'natureza',
            'codtipotitulo', 'codcontacontabil', 'codportador',
        ]))];
    }

    public function originais(Request $request)
    {
        $request->validate(['codpessoa' => 'required|integer']);
        return ['data' => PdvPagamentoService::originais((int) $request->codpessoa)];
    }

    public function store(PagamentoTituloStoreRequest $request)
    {
        $pdv = $request->attributes->get('pdv');
        DB::beginTransaction();
        $pags = PdvPagamentoService::baixar($pdv, $request->validated());
        DB::commit();
        return PagamentoDetalheResource::collection($pags);
    }

    // o "Referente a" (sai no recibo), em qualquer pagamento efetivado que o PDV ve
    public function referente(Request $request, int $id)
    {
        $pdv = $request->attributes->get('pdv');
        $request->validate(['referente' => 'nullable|string|max:255']);
        DB::beginTransaction();
        $pag = PagamentoTituloService::alterarReferente(PdvPagamentoService::carregar($pdv, $id), $request->referente);
        DB::commit();
        return new PagamentoDetalheResource($pag);
    }

    // cancelar: estorna as baixas junto (TASK-218)
    public function cancelar(Request $request, int $id)
    {
        $pdv = $request->attributes->get('pdv');
        $request->validate(['justificativa' => 'required|string|min:5|max:300']);
        DB::beginTransaction();
        $pag = PdvPagamentoService::cancelar($pdv, $id, $request->justificativa);
        DB::commit();
        return new PagamentoDetalheResource($pag);
    }

    // recibo termico de um recebimento (um ou mais pagamentos)
    public function imprimirRecibo(Request $request, string $impressora)
    {
        $pdv = $request->attributes->get('pdv');
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
        return response()->make(\Mg\Pagamento\PagamentoReciboService::pdfTermica($cods), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Recibo' . implode('-', $cods) . '.pdf"',
        ]);
    }
}
