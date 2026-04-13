<?php

namespace Mg\NfeTerceiro;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use NFePHP\DA\NFe\Danfe;

use Mg\Dfe\DfeTipo;
use Mg\NFePHP\NFePHPDistDfeService;
use Mg\NFePHP\NFePHPPathService;
use Mg\NfeTerceiro\NfeTerceiroImportarService;
use Mg\NfeTerceiro\NfeTerceiroIcmsStService;
use Mg\NfeTerceiro\NfeTerceiroService;
use Mg\NfeTerceiro\Resources\NfeTerceiroResource;
use Mg\NfeTerceiro\Resources\NfeTerceiroItemResource;
use Mg\Titulo\TituloNfeTerceiro;

class NfeTerceiroController
{

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
            'valortotal_inicio' => 'nullable|numeric',
            'valortotal_fim' => 'nullable|numeric',
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);

        $query = NfeTerceiro::with([
            'Filial',
            'Pessoa',
            'NaturezaOperacao',
            'UsuarioRevisao',
            'UsuarioConferencia',
        ]);

        if ($request->filled('codfilial')) {
            $query->where('codfilial', $request->codfilial);
        }

        if ($request->filled('codpessoa')) {
            $query->where('codpessoa', $request->codpessoa);
        }

        if ($request->filled('codgrupoeconomico')) {
            $query->whereHas('Pessoa', function ($q) use ($request) {
                $q->where('codgrupoeconomico', $request->codgrupoeconomico);
            });
        }

        if ($request->filled('codnaturezaoperacao')) {
            $query->where('codnaturezaoperacao', $request->codnaturezaoperacao);
        }

        if ($request->filled('nfechave')) {
            $query->where('nfechave', 'like', '%' . $request->nfechave . '%');
        }

        if ($request->filled('emissao_inicio')) {
            $query->where('emissao', '>=', "$request->emissao_inicio 00:00:00");
        }

        if ($request->filled('emissao_fim')) {
            $query->where('emissao', '<=', "$request->emissao_fim 23:59:59");
        }

        if ($request->filled('indsituacao')) {
            $query->where('indsituacao', $request->indsituacao);
        }

        if ($request->filled('indmanifestacao')) {
            $query->where('indmanifestacao', $request->indmanifestacao);
        }

        if ($request->filled('ignorada')) {
            $ignorada = filter_var($request->ignorada, FILTER_VALIDATE_BOOLEAN);
            $query->where('ignorada', $ignorada);
        }

        if ($request->filled('revisao')) {
            if (filter_var($request->revisao, FILTER_VALIDATE_BOOLEAN)) {
                $query->whereNotNull('revisao');
            } else {
                $query->whereNull('revisao');
            }
        }

        if ($request->filled('conferencia')) {
            if (filter_var($request->conferencia, FILTER_VALIDATE_BOOLEAN)) {
                $query->whereNotNull('conferencia');
            } else {
                $query->whereNull('conferencia');
            }
        }

        if ($request->filled('valortotal_inicio')) {
            $query->where('valortotal', '>=', $request->valortotal_inicio);
        }

        if ($request->filled('valortotal_fim')) {
            $query->where('valortotal', '<=', $request->valortotal_fim);
        }

        $query->orderByDesc('emissao');
        $query->orderByDesc('codnfeterceiro');

        return NfeTerceiroResource::collection(
            $query->paginate($request->get('per_page', 50))
        );
    }

    public function show(int $codnfeterceiro)
    {
        return new NfeTerceiroResource(
            NfeTerceiro::with([
                'Filial',
                'Pessoa',
                'NaturezaOperacao',
                'UsuarioAlteracao',
                'UsuarioRevisao',
                'UsuarioConferencia',
                'NfeTerceiroItemS.ProdutoBarra.ProdutoVariacao.Produto',
                'NfeTerceiroDuplicataS.Titulo',
                'NfeTerceiroPagamentoS',
            ])->findOrFail($codnfeterceiro)
        );
    }

    public function update(Request $request, int $codnfeterceiro)
    {
        $nfeTerceiro = NfeTerceiro::findOrFail($codnfeterceiro);

        if (!empty($nfeTerceiro->codnotafiscal)) {
            abort(409, 'NFe já foi importada e não pode mais ser editada.');
        }

        $request->validate([
            'codnaturezaoperacao' => 'nullable|integer|exists:tblnaturezaoperacao,codnaturezaoperacao',
            'codpessoa' => 'nullable|integer|exists:tblpessoa,codpessoa',
            'entrada' => 'nullable|date',
            'observacoes' => 'nullable|string|max:500',
            'ignorada' => 'nullable|boolean',
        ]);

        $nfeTerceiro->fill($request->only([
            'codnaturezaoperacao',
            'codpessoa',
            'entrada',
            'observacoes',
            'ignorada',
        ]));
        $nfeTerceiro->save();

        return new NfeTerceiroResource($nfeTerceiro->fresh()->load([
            'Filial',
            'Pessoa',
            'NaturezaOperacao',
        ]));
    }

    public function revisao(Request $request, int $codnfeterceiro)
    {
        $nfeTerceiro = NfeTerceiro::findOrFail($codnfeterceiro);

        if ($nfeTerceiro->revisao) {
            $nfeTerceiro->revisao = null;
            $nfeTerceiro->codusuariorevisao = null;
        } else {
            $nfeTerceiro->revisao = now();
            $nfeTerceiro->codusuariorevisao = Auth::id();
        }
        $nfeTerceiro->save();

        return new NfeTerceiroResource($nfeTerceiro->fresh()->load([
            'UsuarioRevisao',
        ]));
    }

    public function conferencia(Request $request, int $codnfeterceiro)
    {
        $nfeTerceiro = NfeTerceiro::findOrFail($codnfeterceiro);

        if ($nfeTerceiro->conferencia) {
            $nfeTerceiro->conferencia = null;
            $nfeTerceiro->codusuarioconferencia = null;
        } else {
            $nfeTerceiro->conferencia = now();
            $nfeTerceiro->codusuarioconferencia = Auth::id();
        }
        $nfeTerceiro->save();

        return new NfeTerceiroResource($nfeTerceiro->fresh()->load([
            'UsuarioConferencia',
        ]));
    }

    // ==================== OPERAÇÕES SOBRE ITENS ====================

    public function buscarItem(Request $request, int $codnfeterceiro)
    {
        $request->validate(['barras' => 'required|string']);
        $barras = trim($request->barras);

        $items = DB::select('
            select nti.codnfeterceiroitem, nti.cprod, nti.xprod, nti.cean, nti.ceantrib, nti.qcom, nti.vprod
            from tblnfeterceiroitem nti
            left join tblprodutobarra pb on (pb.codprodutobarra = nti.codprodutobarra)
            left join tblprodutobarra pbs on (pbs.codprodutovariacao = pb.codprodutovariacao)
            where nti.codnfeterceiro = ?
            and pbs.barras = ?
            union
            select nti.codnfeterceiroitem, nti.cprod, nti.xprod, nti.cean, nti.ceantrib, nti.qcom, nti.vprod
            from tblnfeterceiroitem nti
            where nti.codnfeterceiro = ?
            and ? in (nti.cean, nti.ceantrib)
            union
            select nti.codnfeterceiroitem, nti.cprod, nti.xprod, nti.cean, nti.ceantrib, nti.qcom, nti.vprod
            from tblnfeterceiroitem nti
            where nti.codnfeterceiro = ?
            and cprod ilike ?
        ', [$codnfeterceiro, $barras, $codnfeterceiro, $barras, $codnfeterceiro, "%{$barras}%"]);

        return response()->json([
            'codnfeterceiro' => $codnfeterceiro,
            'items' => $items,
        ]);
    }

    public function updateItem(Request $request, int $codnfeterceiro, int $codnfeterceiroitem)
    {
        $nfeTerceiro = NfeTerceiro::findOrFail($codnfeterceiro);

        if (!empty($nfeTerceiro->codnotafiscal)) {
            abort(409, 'NFe já foi importada e seus itens não podem mais ser editados.');
        }

        $item = $nfeTerceiro->NfeTerceiroItemS()->where('codnfeterceiroitem', $codnfeterceiroitem)->firstOrFail();

        $request->validate([
            'codprodutobarra' => 'nullable|integer|exists:tblprodutobarra,codprodutobarra',
            'qcom' => 'nullable|numeric|min:0',
            'vprod' => 'nullable|numeric|min:0',
            'margem' => 'nullable|numeric',
            'complemento' => 'nullable|numeric',
            'observacoes' => 'nullable|string|max:500',
            'modalidadeicmsgarantido' => 'nullable|boolean',
        ]);

        $item->fill($request->only([
            'codprodutobarra', 'qcom', 'vprod', 'margem',
            'complemento', 'observacoes', 'modalidadeicmsgarantido',
        ]));

        // Recalcula preço unitário
        if ($request->filled('vprod') && $request->filled('qcom') && $item->qcom > 0) {
            $item->vuncom = round($item->vprod / $item->qcom, 6);
        }

        $item->save();

        return new NfeTerceiroItemResource($item->fresh()->load('ProdutoBarra.ProdutoVariacao.Produto'));
    }

    public function conferenciaItem(Request $request, int $codnfeterceiro, int $codnfeterceiroitem)
    {
        $nfeTerceiro = NfeTerceiro::findOrFail($codnfeterceiro);
        $item = $nfeTerceiro->NfeTerceiroItemS()->where('codnfeterceiroitem', $codnfeterceiroitem)->firstOrFail();

        if ($item->conferencia) {
            $item->conferencia = null;
            $item->codusuarioconferencia = null;
        } else {
            $item->conferencia = now();
            $item->codusuarioconferencia = Auth::id();
        }
        $item->save();

        // Cascata: se todos itens conferidos, marca cabeçalho
        $pendentes = $nfeTerceiro->NfeTerceiroItemS()->whereNull('conferencia')->count();
        if ($pendentes === 0) {
            $nfeTerceiro->conferencia = now();
            $nfeTerceiro->codusuarioconferencia = Auth::id();
            $nfeTerceiro->save();
        }

        return response()->json([
            'codnfeterceiroitem' => $item->codnfeterceiroitem,
            'conferencia' => $item->conferencia,
            'conferencia_cabecalho' => $nfeTerceiro->fresh()->conferencia,
        ]);
    }

    public function marcarTipoProduto(Request $request, int $codnfeterceiro)
    {
        $nfeTerceiro = NfeTerceiro::findOrFail($codnfeterceiro);

        if (!empty($nfeTerceiro->codnotafiscal)) {
            abort(409, 'NFe já foi importada.');
        }

        $request->validate([
            'codtipoproduto' => 'required|integer|exists:tbltipoproduto,codtipoproduto',
        ]);

        $items = DB::transaction(function () use ($request, $codnfeterceiro) {
            return DB::select('
                update tblnfeterceiroitem
                set codprodutobarra = (
                        select min(pb.codprodutobarra)
                        from tblncm n
                        inner join tblproduto p on (p.codncm = n.codncm)
                        inner join tblprodutobarra pb on (pb.codproduto = p.codproduto)
                        where n.ncm = tblnfeterceiroitem.ncm
                        and p.codtipoproduto = ?
                    ),
                    complemento = null,
                    margem = null
                where tblnfeterceiroitem.codnfeterceiro = ?
                returning codnfeterceiroitem, codprodutobarra
            ', [$request->codtipoproduto, $codnfeterceiro]);
        });

        return response()->json([
            'codnfeterceiro' => $codnfeterceiro,
            'items' => $items,
        ]);
    }

    public function informarComplemento(Request $request, int $codnfeterceiro)
    {
        $nfeTerceiro = NfeTerceiro::findOrFail($codnfeterceiro);

        if (!empty($nfeTerceiro->codnotafiscal)) {
            abort(409, 'NFe já foi importada.');
        }

        $request->validate([
            'valor' => 'nullable|numeric',
        ]);

        if (empty($request->valor)) {
            DB::update('
                update tblnfeterceiroitem
                set complemento = null
                where codnfeterceiro = ?
            ', [$codnfeterceiro]);
        } else {
            DB::update('
                update tblnfeterceiroitem
                set complemento = round((? / n.valorprodutos) * vprod, 2)
                from tblnfeterceiro n
                where n.codnfeterceiro = tblnfeterceiroitem.codnfeterceiro
                and tblnfeterceiroitem.codnfeterceiro = ?
            ', [$request->valor, $codnfeterceiro]);
        }

        return new NfeTerceiroResource($nfeTerceiro->fresh()->load('NfeTerceiroItemS'));
    }

    public function dividirItem(Request $request, int $codnfeterceiro, int $codnfeterceiroitem)
    {
        $nfeTerceiro = NfeTerceiro::findOrFail($codnfeterceiro);

        if (!empty($nfeTerceiro->codnotafiscal)) {
            abort(409, 'NFe já foi importada e seus itens não podem mais ser divididos.');
        }

        $item = $nfeTerceiro->NfeTerceiroItemS()->where('codnfeterceiroitem', $codnfeterceiroitem)->firstOrFail();

        $request->validate([
            'parcelas' => 'required|integer|in:2,3,4,5,6,10',
        ]);

        $parcelas = (int) $request->parcelas;
        $percentuais = match ($parcelas) {
            2 => [55, 45],
            3 => [39, 33, 28],
            4 => [30, 27, 23, 20],
            5 => [26, 23, 20, 17, 14],
            6 => [24, 20, 17, 15, 13, 11],
            10 => [15, 14, 13, 12, 11, 9, 8, 7, 6, 5],
        };

        DB::beginTransaction();
        try {
            // Multiplica nitem por 100 se necessário
            $stats = DB::selectOne('
                select max(nitem) as max, count(*) as qtd
                from tblnfeterceiroitem
                where codnfeterceiro = ?
            ', [$codnfeterceiro]);

            if ($stats->max == $stats->qtd) {
                DB::update('
                    update tblnfeterceiroitem
                    set nitem = nitem * 100
                    where codnfeterceiro = ?
                ', [$codnfeterceiro]);
                $item->refresh();
            }

            // Acumula totais das parcelas novas para calcular resto
            $acum = ['vuncom' => 0, 'vprod' => 0, 'vbc' => 0, 'vicms' => 0,
                'vbcst' => 0, 'vicmsst' => 0, 'ipivbc' => 0, 'ipivipi' => 0,
                'complemento' => 0, 'vdesc' => 0];

            for ($i = $parcelas - 1; $i >= 0; $i--) {
                $nitem = $item->nitem + $i;
                $percentual = $percentuais[$i] / 100;
                $sufixo = ' ' . str_pad($i + 1, 2, '0', STR_PAD_LEFT) . '/' . str_pad($parcelas, 2, '0', STR_PAD_LEFT);

                if ($i === 0) {
                    // Original fica com o resto
                    $item->nitem = $nitem;
                    $item->cprod .= $sufixo;
                    $item->xprod .= $sufixo;
                    $item->vuncom -= $acum['vuncom'];
                    $item->vprod -= $acum['vprod'];
                    $item->vbc -= $acum['vbc'];
                    $item->vicms -= $acum['vicms'];
                    $item->vbcst -= $acum['vbcst'];
                    $item->vicmsst -= $acum['vicmsst'];
                    $item->ipivbc -= $acum['ipivbc'];
                    $item->ipivipi -= $acum['ipivipi'];
                    $item->complemento -= $acum['complemento'];
                    $item->vdesc -= $acum['vdesc'];
                    $item->save();
                } else {
                    $novo = $item->replicate();
                    $novo->nitem = $nitem;
                    $novo->cprod .= $sufixo;
                    $novo->xprod .= $sufixo;
                    $novo->vuncom = round($item->vuncom * $percentual, 6);
                    $novo->vprod = round($novo->vuncom * $novo->qcom, 2);
                    $novo->vbc = round($item->vbc * $percentual, 2);
                    $novo->vicms = round($item->vicms * $percentual, 2);
                    $novo->vbcst = round($item->vbcst * $percentual, 2);
                    $novo->vicmsst = round($item->vicmsst * $percentual, 2);
                    $novo->ipivbc = round($item->ipivbc * $percentual, 2);
                    $novo->ipivipi = round($item->ipivipi * $percentual, 2);
                    $novo->complemento = round(($item->complemento ?? 0) * $percentual, 2);
                    $novo->vdesc = round(($item->vdesc ?? 0) * $percentual, 2);
                    $novo->codprodutobarra = null;
                    $novo->save();

                    $acum['vuncom'] += $novo->vuncom;
                    $acum['vprod'] += $novo->vprod;
                    $acum['vbc'] += $novo->vbc;
                    $acum['vicms'] += $novo->vicms;
                    $acum['vbcst'] += $novo->vbcst;
                    $acum['vicmsst'] += $novo->vicmsst;
                    $acum['ipivbc'] += $novo->ipivbc;
                    $acum['ipivipi'] += $novo->ipivipi;
                    $acum['complemento'] += $novo->complemento;
                    $acum['vdesc'] += $novo->vdesc;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return new NfeTerceiroResource(
            $nfeTerceiro->fresh()->load('NfeTerceiroItemS.ProdutoBarra.ProdutoVariacao.Produto')
        );
    }

    // ==================== ICMS-ST ====================

    public function icmsst(int $codnfeterceiro)
    {
        NfeTerceiro::findOrFail($codnfeterceiro);

        $itens = DB::select('
            with final as (
                with itens as (
                    select
                        nti.codnfeterceiroitem,
                        nti.nitem,
                        nti.cprod,
                        nti.xprod,
                        nti.ncm as ncmnota,
                        n.ncm as ncmproduto,
                        nti.cest as cestnota,
                        c.cest as cestproduto,
                        round(1 + (coalesce(c.mva, 0) / 100), 4) as mva,
                        coalesce(vprod, 0) + coalesce(vfrete, 0) + coalesce(vseg, 0) + coalesce(voutro, 0) + coalesce(ipivipi, 0) - coalesce(vdesc, 0) as valor,
                        case when coalesce(n.bit, false) then 0.4117 else 1.0 end as reducao,
                        case when coalesce(picms, 0) > 7 then
                            (coalesce(vprod, 0) + coalesce(vfrete, 0) + coalesce(vseg, 0) + coalesce(voutro, 0) - coalesce(vdesc, 0)) * 0.07
                        else
                            case when coalesce(vicms, 0) = 0 then
                                case when p.importado then
                                    (coalesce(vprod, 0) + coalesce(vfrete, 0) + coalesce(vseg, 0) + coalesce(voutro, 0) - coalesce(vdesc, 0)) * 0.04
                                else
                                    (coalesce(vprod, 0) + coalesce(vfrete, 0) + coalesce(vseg, 0) + coalesce(voutro, 0) - coalesce(vdesc, 0)) * 0.07
                                end
                            else
                                coalesce(vicms, 0)
                            end
                        end as vicms,
                        vicmsst
                    from tblnfeterceiroitem nti
                    left join tblprodutobarra pb on (pb.codprodutobarra = nti.codprodutobarra)
                    left join tblproduto p on (p.codproduto = pb.codproduto)
                    left join tblncm n on (n.codncm = p.codncm)
                    left join tblcest c on (c.codcest = p.codcest)
                    where nti.codnfeterceiro = ?
                    order by nti.ncm, nti.xprod
                )
                select *, round((valor * reducao * mva * 0.17) - (vicms * reducao), 2) as vicmsstcalculado from itens
            )
            select *, coalesce(vicmsstcalculado, 0) - coalesce(vicmsst, 0) as diferenca from final
        ', [$codnfeterceiro]);

        // Totais
        $totalVicmsst = 0;
        $totalCalculado = 0;
        $totalDiferenca = 0;
        foreach ($itens as $item) {
            $totalVicmsst += $item->vicmsst ?? 0;
            $totalCalculado += $item->vicmsstcalculado ?? 0;
            $totalDiferenca += $item->diferenca ?? 0;
        }

        // Guias ST já geradas
        $guias = DB::select('
            select t.codtitulo, t.numero, t.emissao, t.vencimento, t.credito, t.creditosaldo,
                   tnft.codtitulonfeterceiro
            from tbltitulonfeterceiro tnft
            inner join tbltitulo t on (t.codtitulo = tnft.codtitulo)
            where tnft.codnfeterceiro = ?
            order by t.vencimento
        ', [$codnfeterceiro]);

        return response()->json([
            'itens' => $itens,
            'totais' => [
                'vicmsst' => round($totalVicmsst, 2),
                'vicmsstcalculado' => round($totalCalculado, 2),
                'diferenca' => round($totalDiferenca, 2),
            ],
            'guias' => $guias,
        ]);
    }

    public function gerarGuiaSt(Request $request, int $codnfeterceiro)
    {
        $nfeTerceiro = NfeTerceiro::findOrFail($codnfeterceiro);

        $request->validate([
            'valor' => 'required|numeric|min:0.01',
            'vencimento' => 'required|date',
        ]);

        $resultado = NfeTerceiroIcmsStService::gerarGuiaSt(
            $nfeTerceiro,
            (float) $request->valor,
            $request->vencimento
        );

        return response()->json($resultado);
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
        $nfeTerceiro = NfeTerceiro::findOrFail($codnfeterceiro);
        $erros = NfeTerceiroImportarService::podeImportar($nfeTerceiro);
        return response()->json([
            'podeImportar' => count($erros) === 0,
            'erros' => $erros,
        ]);
    }

    public function importar(int $codnfeterceiro)
    {
        $nfeTerceiro = NfeTerceiro::findOrFail($codnfeterceiro);
        $nfeTerceiro = NfeTerceiroImportarService::importar($nfeTerceiro);
        return new NfeTerceiroResource($nfeTerceiro->load([
            'Filial', 'Pessoa', 'NaturezaOperacao',
        ]));
    }

    // ==================== UPLOAD XML ====================

    public function uploadXml(Request $request)
    {
        $request->validate([
            'xml' => 'required|file|mimes:xml,txt|max:2048',
        ]);

        $xmlContent = file_get_contents($request->file('xml')->getRealPath());
        $gz = gzencode($xmlContent);

        NFePHPDistDfeService::processarProcNFe(null, $gz);

        // Extrai chave da NFe para retornar o registro criado
        // LIBXML_NONET impede acesso a recursos externos (proteção XXE)
        $dom = new \DOMDocument();
        $dom->loadXML($xmlContent, LIBXML_NONET);
        $chave = null;
        $infNFe = $dom->getElementsByTagName('infNFe')->item(0);
        if ($infNFe) {
            $chave = preg_replace('/[^0-9]/', '', $infNFe->getAttribute('Id'));
        }

        $nfeTerceiro = null;
        if ($chave) {
            $nfeTerceiro = NfeTerceiro::where('nfechave', $chave)->first();
        }

        if ($nfeTerceiro) {
            return new NfeTerceiroResource(
                $nfeTerceiro->load([
                    'Filial', 'Pessoa', 'NaturezaOperacao',
                    'NfeTerceiroItemS.ProdutoBarra.ProdutoVariacao.Produto',
                    'NfeTerceiroDuplicataS.Titulo',
                    'NfeTerceiroPagamentoS',
                ])
            );
        }

        return response()->json(['message' => 'XML processado'], 200);
    }

    // ==================== DOCUMENTOS ====================

    public function xml(Request $request, $codnfeterceiro)
    {
        $nfeTerceiro = NfeTerceiro::findOrFail($codnfeterceiro);
        $dfeTipo = DfeTipo::where(['schemaxml' => 'procNFe_v4.00.xsd'])->firstOrFail();
        $dd = $nfeTerceiro->DistribuicaoDfeS()->where('coddfetipo', $dfeTipo->coddfetipo)->first();
        $path = NFePHPPathService::pathDfeGz($dd);
        $gz = file_get_contents($path);
        $xml = gzdecode($gz);
        return response($xml, 200)->header('Content-Type', 'text/xml');
    }

    public function danfe(Request $request, $codnfeterceiro)
    {
        $nfeTerceiro = NfeTerceiro::findOrFail($codnfeterceiro);
        $dfeTipo = DfeTipo::where(['schemaxml' => 'procNFe_v4.00.xsd'])->firstOrFail();
        $dd = $nfeTerceiro->DistribuicaoDfeS()->where('coddfetipo', $dfeTipo->coddfetipo)->first();
        $path = NFePHPPathService::pathDfeGz($dd);
        $gz = file_get_contents($path);
        $xml = gzdecode($gz);
        $danfe = new Danfe($xml);
        $danfe->debugMode(false);
        $danfe->setDefaultFont('helvetica');
        $pdf = $danfe->render();
        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="NfeTerceiro'.$codnfeterceiro.'.pdf"'
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

        $manif = $request->indmanifestacao;
        $just = $request->justificativa??'';
        $nfeTerceiro = NfeTerceiro::findOrFail($codnfeterceiro);
        $ret = NfeTerceiroService::manifestacao($nfeTerceiro, $manif, $just);
        return $ret;
    }

    public function download($codnfeterceiro)
    {
        $nfeTerceiro = NfeTerceiro::findOrFail($codnfeterceiro);
        NfeTerceiroService::download($nfeTerceiro);
        return new NfeTerceiroResource($nfeTerceiro->fresh());
    }

}
