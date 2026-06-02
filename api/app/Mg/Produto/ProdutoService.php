<?php

namespace Mg\Produto;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Mg\MgService;

class ProdutoService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $filter = $filter ?? [];
        $qry = Produto::query();

        if (!empty($filter['codproduto'])) {
            $qry->where('codproduto', $filter['codproduto']);
        }

        if (!empty($filter['barras'])) {
            $barras = $filter['barras'];
            $qry->whereHas('ProdutoBarraS', function ($q) use ($barras) {
                $q->where('barras', 'ilike', "%{$barras}%");
            });
        }

        if (!empty($filter['produto'])) {
            $qry->palavras('produto', $filter['produto']);
        }

        if (!empty($filter['referencia'])) {
            $qry->palavras('referencia', $filter['referencia']);
        }

        if (!empty($filter['preco_de'])) {
            $qry->where('preco', '>=', $filter['preco_de']);
        }

        if (!empty($filter['preco_ate'])) {
            $qry->where('preco', '<=', $filter['preco_ate']);
        }

        if (!empty($filter['codsubgrupoproduto'])) {
            $qry->where('codsubgrupoproduto', $filter['codsubgrupoproduto']);
        }

        if (!empty($filter['codgrupoproduto'])) {
            $qry->whereHas('SubGrupoProduto', function ($q) use ($filter) {
                $q->where('codgrupoproduto', $filter['codgrupoproduto']);
            });
        }

        if (!empty($filter['codfamiliaproduto'])) {
            $qry->whereHas('SubGrupoProduto.GrupoProduto', function ($q) use ($filter) {
                $q->where('codfamiliaproduto', $filter['codfamiliaproduto']);
            });
        }

        if (!empty($filter['codsecaoproduto'])) {
            $qry->whereHas('SubGrupoProduto.GrupoProduto.FamiliaProduto', function ($q) use ($filter) {
                $q->where('codsecaoproduto', $filter['codsecaoproduto']);
            });
        }

        if (!empty($filter['codmarca'])) {
            $qry->where('codmarca', $filter['codmarca']);
        }

        if (!empty($filter['codtributacao'])) {
            $qry->where('codtributacao', $filter['codtributacao']);
        }

        if (!empty($filter['codncm'])) {
            $qry->where('codncm', $filter['codncm']);
        }

        if (isset($filter['site']) && $filter['site'] !== '' && $filter['site'] !== null) {
            $qry->where('site', filter_var($filter['site'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        if (!empty($filter['criacao_de'])) {
            $qry->where('criacao', '>=', Carbon::parse($filter['criacao_de']));
        }
        if (!empty($filter['criacao_ate'])) {
            $qry->where('criacao', '<=', Carbon::parse($filter['criacao_ate']));
        }
        if (!empty($filter['alteracao_de'])) {
            $qry->where('alteracao', '>=', Carbon::parse($filter['alteracao_de']));
        }
        if (!empty($filter['alteracao_ate'])) {
            $qry->where('alteracao', '<=', Carbon::parse($filter['alteracao_ate']));
        }

        if (empty($sort)) {
            $qry->orderBy('produto');
        }

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    /**
     * Cria produto + variação default + barra default (igual ao legado).
     */
    public static function criar(array $dados): Produto
    {
        return DB::transaction(function () use ($dados) {
            $model = new Produto();
            $model->fill($dados);
            foreach (['importado', 'estoque', 'conferenciaperiodica', 'site'] as $bool) {
                $model->$bool = filter_var($dados[$bool] ?? false, FILTER_VALIDATE_BOOLEAN);
            }
            $model->save();

            $pv = new ProdutoVariacao();
            $pv->codproduto = $model->codproduto;
            $pv->save();

            ProdutoBarraService::criar([
                'codproduto' => $model->codproduto,
                'codprodutovariacao' => $pv->codprodutovariacao,
            ]);

            return $model;
        });
    }

    /**
     * Atualiza produto, registrando histórico se o preço mudar.
     */
    public static function atualizar(Produto $model, array $dados): Produto
    {
        return DB::transaction(function () use ($model, $dados) {
            $precoAntigo = $model->preco;
            $model->fill($dados);
            foreach (['importado', 'estoque', 'conferenciaperiodica', 'site'] as $bool) {
                if (array_key_exists($bool, $dados)) {
                    $model->$bool = filter_var($dados[$bool], FILTER_VALIDATE_BOOLEAN);
                }
            }
            $model->save();

            if ((float) $precoAntigo != (float) $model->preco) {
                $hist = new ProdutoHistoricoPreco();
                $hist->codproduto = $model->codproduto;
                $hist->precoantigo = $precoAntigo;
                $hist->preconovo = $model->preco;
                $hist->save();
            }

            return $model;
        });
    }

    /**
     * Saldo de estoque (físico/fiscal) por local e variação. Porta o
     * getArraySaldoEstoque() do legado em estrutura aninhada.
     */
    public static function saldoEstoque($codproduto): array
    {
        $produto = Produto::findOrFail($codproduto);
        $locais = [];

        foreach ($produto->ProdutoVariacaoS()->orderByRaw('variacao asc nulls first')->get() as $pv) {
            foreach ($pv->EstoqueLocalProdutoVariacaoS()->orderBy('codestoquelocal')->get() as $elpv) {
                $cod = $elpv->codestoquelocal;
                if (!isset($locais[$cod])) {
                    $locais[$cod] = [
                        'codestoquelocal' => $cod,
                        'estoquelocal' => $elpv->EstoqueLocal->estoquelocal ?? '',
                        'fisico' => ['saldoquantidade' => 0, 'saldovalor' => 0],
                        'fiscal' => ['saldoquantidade' => 0, 'saldovalor' => 0],
                        'variacoes' => [],
                    ];
                }

                $var = [
                    'codprodutovariacao' => $pv->codprodutovariacao,
                    'variacao' => $pv->variacao,
                    'estoqueminimo' => $elpv->estoqueminimo,
                    'estoquemaximo' => $elpv->estoquemaximo,
                    'corredor' => $elpv->corredor,
                    'prateleira' => $elpv->prateleira,
                    'coluna' => $elpv->coluna,
                    'bloco' => $elpv->bloco,
                    'fisico' => ['saldoquantidade' => 0, 'saldovalor' => 0, 'customedio' => 0, 'ultimaconferencia' => null],
                    'fiscal' => ['saldoquantidade' => 0, 'saldovalor' => 0, 'customedio' => 0, 'ultimaconferencia' => null],
                ];

                foreach ($elpv->EstoqueSaldoS as $es) {
                    $alvo = $es->fiscal ? 'fiscal' : 'fisico';
                    $var[$alvo]['saldoquantidade'] += (float) $es->saldoquantidade;
                    $var[$alvo]['saldovalor'] += (float) $es->saldovalor;
                    $var[$alvo]['customedio'] = (float) $es->customedio;
                    $var[$alvo]['ultimaconferencia'] = $es->ultimaconferencia;
                    $locais[$cod][$alvo]['saldoquantidade'] += (float) $es->saldoquantidade;
                    $locais[$cod][$alvo]['saldovalor'] += (float) $es->saldovalor;
                }

                $locais[$cod]['variacoes'][] = $var;
            }
        }

        return array_values($locais);
    }

    public static function negocios($codproduto, array $filter): array
    {
        $sql = "
            select npb.codnegocioprodutobarra, n.codnegocio, n.lancamento,
                coalesce(p.fantasia, p.pessoa) as pessoa, nat.naturezaoperacao,
                f.filial, pv.variacao, pb.barras,
                npb.quantidade, npb.valorunitario, npb.valortotal
            from tblnegocioprodutobarra npb
            inner join tblnegocio n on (n.codnegocio = npb.codnegocio)
            inner join tblprodutobarra pb on (pb.codprodutobarra = npb.codprodutobarra)
            inner join tblprodutovariacao pv on (pv.codprodutovariacao = pb.codprodutovariacao)
            left join tblpessoa p on (p.codpessoa = n.codpessoa)
            left join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = n.codnaturezaoperacao)
            left join tblfilial f on (f.codfilial = n.codfilial)
            where pb.codproduto = :codproduto
            and n.codnegociostatus = 2
        ";
        $params = ['codproduto' => $codproduto];
        if (!empty($filter['de'])) {
            $sql .= " and n.lancamento >= :de";
            $params['de'] = Carbon::parse($filter['de']);
        }
        if (!empty($filter['ate'])) {
            $sql .= " and n.lancamento <= :ate";
            $params['ate'] = Carbon::parse($filter['ate']);
        }
        if (!empty($filter['codprodutovariacao'])) {
            $sql .= " and pv.codprodutovariacao = :cpv";
            $params['cpv'] = $filter['codprodutovariacao'];
        }
        $sql .= " order by n.lancamento desc limit 100";
        return DB::select($sql, $params);
    }

    public static function notas($codproduto, array $filter): array
    {
        $sql = "
            select nfpb.codnotafiscalprodutobarra, nf.codnotafiscal, nf.numero, nf.serie,
                nf.modelo, nf.saida, coalesce(p.fantasia, p.pessoa) as pessoa,
                nat.naturezaoperacao, f.filial, pv.variacao, pb.barras,
                nfpb.quantidade, nfpb.valorunitario, nfpb.valortotal
            from tblnotafiscalprodutobarra nfpb
            inner join tblnotafiscal nf on (nf.codnotafiscal = nfpb.codnotafiscal)
            inner join tblprodutobarra pb on (pb.codprodutobarra = nfpb.codprodutobarra)
            inner join tblprodutovariacao pv on (pv.codprodutovariacao = pb.codprodutovariacao)
            left join tblpessoa p on (p.codpessoa = nf.codpessoa)
            left join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = nf.codnaturezaoperacao)
            left join tblfilial f on (f.codfilial = nf.codfilial)
            where pb.codproduto = :codproduto
        ";
        $params = ['codproduto' => $codproduto];
        if (!empty($filter['de'])) {
            $sql .= " and nf.saida >= :de";
            $params['de'] = Carbon::parse($filter['de']);
        }
        if (!empty($filter['ate'])) {
            $sql .= " and nf.saida <= :ate";
            $params['ate'] = Carbon::parse($filter['ate']);
        }
        if (!empty($filter['codprodutovariacao'])) {
            $sql .= " and pv.codprodutovariacao = :cpv";
            $params['cpv'] = $filter['codprodutovariacao'];
        }
        $sql .= " order by nf.saida desc limit 100";
        return DB::select($sql, $params);
    }

    /**
     * Últimas 50 compras (NfeTerceiro). Porta o getArrayCompras() legado.
     */
    public static function compras($codproduto): array
    {
        $sql = "
            select
                nti.codnfeterceiroitem, nt.codnfeterceiro, nt.emissao, nt.entrada,
                nti.cprod, nti.xprod, nti.qcom, nti.ucom,
                coalesce(pe.quantidade, 1) as embalagem,
                nti.qcom * coalesce(pe.quantidade, 1) as quantidadetotal,
                nti.vuncom,
                nti.voutro / nullif(nti.qcom, 0) as voutro,
                nti.vfrete / nullif(nti.qcom, 0) as vfrete,
                nti.vseg / nullif(nti.qcom, 0) as vseg,
                nti.vdesc / nullif(nti.qcom, 0) as vdesc,
                nti.complemento / nullif(nti.qcom, 0) as complemento,
                nti.ipipipi,
                ((( coalesce(nti.voutro, 0) + coalesce(nti.vfrete, 0) + coalesce(nti.vseg, 0)
                    + coalesce(nti.ipivipi, 0) - coalesce(nti.vdesc, 0) + coalesce(nti.complemento, 0)
                    ) / nullif(nti.qcom, 0)) + nti.vuncom) as valortotal,
                nti.margem,
                (coalesce(nti.vicmsst, 0) > 0) as icmsstdestacado
            from tblprodutobarra pb
            left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
            inner join tblnfeterceiroitem nti on (nti.codprodutobarra = pb.codprodutobarra)
            inner join tblnfeterceiro nt on (nt.codnfeterceiro = nti.codnfeterceiro)
            inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = nt.codnaturezaoperacao)
            where pb.codproduto = :codproduto
            and nat.compra = true
            and nt.indsituacao = 1
            and nt.ignorada = false
            order by nt.emissao desc
            limit 50
        ";
        return DB::select($sql, ['codproduto' => $codproduto]);
    }

    public static function buscaPorBarras($barras)
    {
        if ($pb = ProdutoBarra::where('barras', '=', $barras)->first()) {
            return $pb;
        }

        if (strlen($barras) == 6 && ($barras == (int) preg_replace('/[^0-9]/', '', $barras))) {
            if ($pb = ProdutoBarra::where('codproduto', '=', $barras)->whereNull('codprodutoembalagem')->first()) {
                return $pb;
            }
        }

        if (strstr($barras, '-')) {
            $arr = explode('-', $barras);
            if (count($arr == 2)) {
                $codigo = (int) preg_replace('/[^0-9]/', '', $arr[0]);
                $quantidade = (int) preg_replace('/[^0-9]/', '', $arr[1]);

                if ($barras == "$codigo-$quantidade") {
                    if ($pb = ProdutoBarra::where('codproduto', $codigo)->whereHas('ProdutoEmbalagem', function ($query) use ($quantidade) {
                        $query->where('quantidade', $quantidade);
                    })->first())
                        return $pb;
                }
            }
        }

        return false;
    }

    public static function detalheQuiosque($barras)
    {
        if (!$pb = static::buscaPorBarras($barras)) {
            return [
                'resultado' => false,
                'mensagem' => 'Nenhum produto localizado!',
            ];
        }

        $produto = $pb->Produto;

        // Imagens (codimagem - front monta a url pelo helper urlImagem)
        $imagens = [];
        foreach ($produto->ProdutoImagemS()->orderBy('ordem')->get() as $pi) {
            $imagens[] = $pi->codimagem;
        }

        // Variacoes + estoque por local
        $variacoes = [];
        $estoquelocais = [];
        foreach ($produto->ProdutoVariacaoS()->orderByRaw('variacao asc nulls first')->get() as $pv) {
            $saldos = [];
            $saldo = 0;
            foreach ($pv->EstoqueLocalProdutoVariacaoS()->orderBy('codestoquelocal')->get() as $elpv) {
                foreach ($elpv->EstoqueSaldoS()->where('fiscal', false)->get() as $es) {
                    $saldo += (float) $es->saldoquantidade;
                    $estoquelocais[$elpv->codestoquelocal] = [
                        'codestoquelocal' => $elpv->codestoquelocal,
                        'estoquelocal' => $elpv->EstoqueLocal->estoquelocal,
                        'sigla' => $elpv->EstoqueLocal->sigla,
                    ];
                    $saldos[$elpv->codestoquelocal] = (float) $es->saldoquantidade;
                }
            }
            $variacoes[] = [
                'codprodutovariacao' => $pv->codprodutovariacao,
                'variacao' => $pv->variacao,
                'saldo' => $saldo,
                'saldos' => $saldos,
            ];
        }

        // Embalagens
        $embalagens = [];
        $qryEmb = $produto->ProdutoEmbalagemS()->orderBy('quantidade')->whereNotNull('preco');
        $embalagens[] = [
            'codprodutoembalagem' => null,
            'quantidade' => null,
            'unidademedida' => $produto->UnidadeMedida->sigla,
            'preco' => (float) $produto->preco,
            'precocalculado' => false,
        ];
        if ($pb->codprodutoembalagem) {
            $qryEmb = $qryEmb->where('codprodutoembalagem', '!=', $pb->codprodutoembalagem);
        }
        $embs = $qryEmb->get();
        foreach ($embs as $emb) {
            $embalagens[] = [
                'codprodutoembalagem' => $emb->codprodutoembalagem,
                'quantidade' => (float) $emb->quantidade,
                'sigla' => $emb->UnidadeMedida->sigla,
                'unidademedida' => $emb->UnidadeMedida->unidademedida,
                'preco' => $emb->preco,
                'precocalculado' => empty($emb->preco),
            ];
        }

        return [
            'resultado' => true,
            'produto' => [
                'codproduto' => $produto->codproduto,
                'codprodutobarra' => $pb->codprodutobarra,
                'codprodutovariacao' => $pb->codprodutovariacao,
                'codprodutoembalagem' => $pb->codprodutoembalagem,
                'barras' => $pb->barras,
                'produto' => $pb->descricao,
                'titulosite' => $produto->titulosite,
                'descricaosite' => $produto->descricaosite,
                'inativo' => $produto->inativo,
                'sigla' => $pb->unidade,
                'referencia' => $produto->referencia,
                'preco' => (float) $pb->preco,
                'marca' => $produto->Marca?->marca,
                'imagens' => $imagens,
                'embalagens' => $embalagens,
                'variacoes' => $variacoes,
                'estoquelocais' => array_values($estoquelocais),
            ],
        ];
    }
}
