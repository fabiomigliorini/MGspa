<?php

namespace Mg\NfeTerceiro;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use NFePHP\DA\NFe\Danfe;

use Mg\NfeTerceiro\NfeTerceiroImportarService;
use Mg\NfeTerceiro\NfeTerceiroIcmsStService;
use Mg\NfeTerceiro\NfeTerceiroItemService;
use Mg\NfeTerceiro\NfeTerceiroService;
use Mg\NfeTerceiro\Resources\NfeTerceiroResource;
use Mg\Titulo\TituloNfeTerceiro;

class NfeTerceiroController
{

    const RELATIONS_LIST = ['Filial', 'Pessoa', 'NaturezaOperacao', 'UsuarioRevisao', 'UsuarioConferencia'];

    const RELATIONS_SHOW = [
        'Filial',
        'Pessoa.Cidade.Estado',
        'NaturezaOperacao',
        'UsuarioAlteracao',
        'UsuarioRevisao',
        'UsuarioConferencia',
        'NfeTerceiroItemS.ProdutoBarra.ProdutoVariacao.Produto',
        'NfeTerceiroDuplicataS.Titulo',
        'NfeTerceiroPagamentoS',
    ];

    public function index(Request $request)
    {
        $request->validate([
            'nfechave' => 'nullable|string|max:44',
            'codfilial' => 'nullable|integer',
            'codpessoa' => 'nullable|integer',
            'codgrupoeconomico' => 'nullable|integer',
            'codnaturezaoperacao' => 'nullable|integer',
            'emissao_inicio' => 'nullable|date',
            'emissao_fim' => 'nullable|date',
            'indsituacao' => 'nullable|integer',
            'indmanifestacao' => 'nullable|integer',
            'importacao' => 'nullable|in:pendentes,importadas,ignoradas',
            'valortotal_inicio' => 'nullable|numeric',
            'valortotal_fim' => 'nullable|numeric',
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);

        return NfeTerceiroResource::collection(
            NfeTerceiroService::pesquisar($request->all())
                ->with(static::RELATIONS_LIST)
                ->orderByRaw('(codnotafiscal is null) desc')
                ->orderByDesc('codnfeterceiro')
                ->paginate($request->get('per_page', 50))
        );
    }

    public function show(int $codnfeterceiro)
    {
        return new NfeTerceiroResource(
            NfeTerceiro::with(static::RELATIONS_SHOW)->findOrFail($codnfeterceiro)
        );
    }

    public function update(Request $request, int $codnfeterceiro)
    {
        $request->validate([
            'codnaturezaoperacao' => 'nullable|integer|exists:tblnaturezaoperacao,codnaturezaoperacao',
            'codpessoa' => 'nullable|integer|exists:tblpessoa,codpessoa',
            'entrada' => 'nullable|date',
            'observacoes' => 'nullable|string|max:500',
            'ignorada' => 'nullable|boolean',
        ]);

        $nft = NfeTerceiro::findOrFail($codnfeterceiro);
        $nft = NfeTerceiroService::atualizar($nft, $request->only([
            'codnaturezaoperacao',
            'codpessoa',
            'entrada',
            'observacoes',
            'ignorada',
        ]));

        return new NfeTerceiroResource($nft->load(static::RELATIONS_LIST));
    }

    public function revisao(Request $request, int $codnfeterceiro)
    {
        $nft = NfeTerceiro::findOrFail($codnfeterceiro);
        $nft = NfeTerceiroService::alternarRevisao($nft);

        return new NfeTerceiroResource($nft->load(['UsuarioRevisao']));
    }

    public function conferencia(Request $request, int $codnfeterceiro)
    {
        $nft = NfeTerceiro::findOrFail($codnfeterceiro);
        $nft = NfeTerceiroService::alternarConferencia($nft);

        return new NfeTerceiroResource($nft->load(['UsuarioConferencia']));
    }

    // ==================== OPERAÇÕES SOBRE ITENS ====================

    public function buscarItem(Request $request, int $codnfeterceiro)
    {
        $request->validate(['barras' => 'required|string']);

        return response()->json([
            'codnfeterceiro' => $codnfeterceiro,
            'items' => NfeTerceiroItemService::buscar($codnfeterceiro, $request->barras),
        ]);
    }

    public function updateItem(Request $request, int $codnfeterceiro, int $codnfeterceiroitem)
    {
        $request->validate([
            'codprodutobarra' => 'nullable|integer|exists:tblprodutobarra,codprodutobarra',
            'qcom' => 'nullable|numeric|min:0',
            'vprod' => 'nullable|numeric|min:0',
            'margem' => 'nullable|numeric',
            'complemento' => 'nullable|numeric',
            'observacoes' => 'nullable|string|max:500',
            'modalidadeicmsgarantido' => 'nullable|boolean',
        ]);

        $nft = NfeTerceiro::findOrFail($codnfeterceiro);
        $item = $nft->NfeTerceiroItemS()->where('codnfeterceiroitem', $codnfeterceiroitem)->firstOrFail();

        NfeTerceiroItemService::atualizar($item, $request->only([
            'codprodutobarra', 'qcom', 'vprod', 'margem',
            'complemento', 'observacoes', 'modalidadeicmsgarantido',
        ]));

        return new NfeTerceiroResource($nft->fresh()->load(static::RELATIONS_SHOW));
    }

    public function conferenciaItem(Request $request, int $codnfeterceiro, int $codnfeterceiroitem)
    {
        $nft = NfeTerceiro::findOrFail($codnfeterceiro);
        $item = $nft->NfeTerceiroItemS()->where('codnfeterceiroitem', $codnfeterceiroitem)->firstOrFail();

        NfeTerceiroItemService::alternarConferencia($item);

        return new NfeTerceiroResource($nft->fresh()->load(static::RELATIONS_SHOW));
    }

    public function marcarTipoProduto(Request $request, int $codnfeterceiro)
    {
        $request->validate([
            'codtipoproduto' => 'required|integer|exists:tbltipoproduto,codtipoproduto',
        ]);

        $nft = NfeTerceiro::findOrFail($codnfeterceiro);
        $nft = NfeTerceiroItemService::marcarTipoProduto($nft, (int) $request->codtipoproduto);

        return new NfeTerceiroResource($nft->load(static::RELATIONS_SHOW));
    }

    public function conferirTodos(Request $request, int $codnfeterceiro)
    {
        $nft = NfeTerceiro::findOrFail($codnfeterceiro);
        $nft = NfeTerceiroItemService::conferirTodos($nft);

        return new NfeTerceiroResource($nft->load(static::RELATIONS_SHOW));
    }

    public function informarComplemento(Request $request, int $codnfeterceiro)
    {
        $request->validate([
            'valor' => 'nullable|numeric',
        ]);

        $nft = NfeTerceiro::findOrFail($codnfeterceiro);
        $nft = NfeTerceiroItemService::informarComplemento($nft, $request->valor !== null ? (float) $request->valor : null);

        return new NfeTerceiroResource($nft->load(static::RELATIONS_SHOW));
    }

    public function dividirItem(Request $request, int $codnfeterceiro, int $codnfeterceiroitem)
    {
        $request->validate([
            'parcelas' => 'required|integer|in:2,3,4,5,6,10',
        ]);

        $nft = NfeTerceiro::findOrFail($codnfeterceiro);
        $item = $nft->NfeTerceiroItemS()->where('codnfeterceiroitem', $codnfeterceiroitem)->firstOrFail();

        $nft = NfeTerceiroItemService::dividir($item, (int) $request->parcelas);

        return new NfeTerceiroResource($nft->load(static::RELATIONS_SHOW));
    }

    // ==================== ICMS-ST ====================

    public function icmsst(int $codnfeterceiro)
    {
        NfeTerceiro::findOrFail($codnfeterceiro);

        return response()->json(
            NfeTerceiroIcmsStService::relatorio($codnfeterceiro)
        );
    }

    public function gerarGuiaSt(Request $request, int $codnfeterceiro)
    {
        $request->validate([
            'valor' => 'required|numeric|min:0.01',
            'vencimento' => 'required|date',
        ]);

        $nft = NfeTerceiro::findOrFail($codnfeterceiro);
        NfeTerceiroIcmsStService::gerarGuiaSt(
            $nft,
            (float) $request->valor,
            $request->vencimento
        );

        return new NfeTerceiroResource($nft->fresh()->load(static::RELATIONS_SHOW));
    }

    public function guiaStPdf(int $codnfeterceiro, int $codtitulonfeterceiro)
    {
        NfeTerceiro::findOrFail($codnfeterceiro);
        $tnft = TituloNfeTerceiro::findOrFail($codtitulonfeterceiro);
        $arquivo = NfeTerceiroIcmsStService::guiaStPdf($tnft);

        if (!$arquivo) {
            return response()->json(['message' => 'PDF não encontrado'], 404);
        }

        return response()->file($arquivo, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="GuiaST-' . $codtitulonfeterceiro . '.pdf"',
        ]);
    }

    // ==================== IMPORTAÇÃO ====================

    public function validarImportacao(int $codnfeterceiro)
    {
        $nft = NfeTerceiro::findOrFail($codnfeterceiro);
        $erros = NfeTerceiroImportarService::podeImportar($nft);

        return response()->json([
            'podeImportar' => count($erros) === 0,
            'erros' => $erros,
        ]);
    }

    public function importar(int $codnfeterceiro)
    {
        $nft = NfeTerceiro::findOrFail($codnfeterceiro);
        $nft = NfeTerceiroImportarService::importar($nft);

        return new NfeTerceiroResource($nft->load(['Filial', 'Pessoa', 'NaturezaOperacao']));
    }

    // ==================== UPLOAD XML ====================

    public function uploadXml(Request $request)
    {
        $request->validate([
            'xml' => 'required|file|mimes:xml,txt|max:2048',
        ]);

        $xmlContent = file_get_contents($request->file('xml')->getRealPath());
        $nft = NfeTerceiroService::uploadXml($xmlContent);

        if ($nft) {
            return new NfeTerceiroResource($nft->load(static::RELATIONS_SHOW));
        }

        return response()->json(['message' => 'XML processado'], 200);
    }

    // ==================== DOCUMENTOS ====================

    public function xml(Request $request, $codnfeterceiro)
    {
        $nft = NfeTerceiro::findOrFail($codnfeterceiro);
        $xml = NfeTerceiroService::xml($nft);

        return response($xml, 200)->header('Content-Type', 'text/xml');
    }

    public function danfe(Request $request, $codnfeterceiro)
    {
        $nft = NfeTerceiro::findOrFail($codnfeterceiro);
        $xml = NfeTerceiroService::xml($nft);

        $danfe = new Danfe($xml);
        $danfe->debugMode(false);
        $danfe->setDefaultFont('helvetica');
        $pdf = $danfe->render();

        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="NfeTerceiro' . $codnfeterceiro . '.pdf"',
        ]);
    }

    public function manifestacao(Request $request, $codnfeterceiro)
    {
        $request->validate([
            'indmanifestacao' => [
                'required',
                'numeric',
                Rule::in([
                    '210200', // OPERACAO REALIZADA
                    '210210', // CIENCIA DA OPERACAO
                    '210220', // OPERACAO DESOCNHECIDA
                    '210240', // OPERACAO NAO REALIZADA
                ])
            ]
        ]);

        if ($request->indmanifestacao == 210240) {
            $request->validate([
                'justificativa' => 'string|required|min:15'
            ]);
        }

        $nft = NfeTerceiro::findOrFail($codnfeterceiro);

        return NfeTerceiroService::manifestacao(
            $nft,
            $request->indmanifestacao,
            $request->justificativa ?? ''
        );
    }

    public function download($codnfeterceiro)
    {
        $nft = NfeTerceiro::findOrFail($codnfeterceiro);
        NfeTerceiroService::download($nft);

        return new NfeTerceiroResource($nft->fresh());
    }
}
