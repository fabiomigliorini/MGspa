<?php

namespace Mg\Mercos;

use Illuminate\Support\Facades\DB;

use \Carbon\Carbon;

use Mg\Produto\Produto;
use Mg\Produto\ProdutoBarra;
use Mg\Produto\ProdutoVariacao;
use Mg\Produto\ProdutoEmbalagem;
use Mg\Produto\ProdutoImagem;

class MercosProdutoService {

    // Exporta Produto para o Mercos
    public static function exportaProduto ($codproduto, $codprodutovariacao, $codprodutoembalagem)
    {

        $qry = MercosProduto::where([
            'codproduto' => $codproduto,
            'codprodutovariacao' => $codprodutovariacao,
        ])->whereNull('inativo');
        if (!empty($codprodutoembalagem)) {
            $qry->where('codprodutoembalagem', $codprodutoembalagem);
        } else {
            $qry->whereNull('codprodutoembalagem');
        }
        $mp = $qry->first();

        $api = new MercosApi();

        $p = Produto::findOrFail($codproduto);
        $nome = $p->produto;
        $preco_tabela = (double)$p->preco;
        $codigo = formataCodigo($codproduto, 6);
        $codigo .= '-' . formataCodigo($codprodutovariacao, 8);
        $unidade = $p->UnidadeMedida->sigla;
        $peso_bruto = round((double) $p->peso, 3);
        $largura = (double) $p->largura;
        $altura = (double) $p->altura;
        $comprimento = (double) $p->profundidade;

        $saldo_estoque = 10000;
        if ($p->estoque) {
            $locais = env('MERCOS_CODESTOQUELOCAL_DISPONIVEL');
            $sql = '
                select sum(es.saldoquantidade) as saldoquantidade
                from tblestoquelocalprodutovariacao elpv
                inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
                where elpv.codprodutovariacao = :codprodutovariacao
                and elpv.codestoquelocal in (' . $locais . ')
                and es.fiscal = false
            ';
            $data = DB::select($sql, [
                'codprodutovariacao' => $codprodutovariacao,
            ]);
            if (isset($data[0])) {
                $saldo_estoque = floor($data[0]->saldoquantidade)??0;
            }
        }

        $pv = ProdutoVariacao::findOrFail($codprodutovariacao);
        if (!empty($pv->variacao)) {
            $nome .= ' ' . $pv->variacao;
        }

        if (!empty($codprodutoembalagem)) {
            $pe = ProdutoEmbalagem::findOrFail($codprodutoembalagem);
            $nome .= ' C/' . formataNumero($pe->quantidade, 0);
            if (!empty($pe->preco)) {
                $preco_tabela = $pe->preco;
            } else {
                $preco_tabela *= $pe->quantidade;
            }
            $codigo .= '-' . formataCodigo($codprodutoembalagem, 8);
            $unidade = $pe->UnidadeMedida->sigla;
            $saldo_estoque = floor($saldo_estoque / $pe->quantidade);

            $peso_bruto = round((double) $pe->peso, 3);
            $largura = (double) $pe->largura;
            $altura = (double) $pe->altura;
            $comprimento = (double) $pe->profundidade;
        }
        $preco_tabela = round($preco_tabela, 2);
        $preco_minimo = $preco_tabela;
        $comissao = null;
        $ipi = null;
        $tipo_ipi = 'P';
        $st = null;
        $moeda = 0;
        $observacoes = $p->descricaosite;

        $barras = collect([]);
        // $barras[] = (object) [
        //     'barras' => $codigo,
        //     'quantidade' => 1,
        //     'unidademedida' => $p->UnidadeMedida->unidademedida,
        // ];
        foreach ($pv->ProdutoBarraS()->get() as $pb) {
            $barra = (object) [
                'barras' => $pb->barras,
                'quantidade' => 1,
                'unidademedida' => $p->UnidadeMedida->unidademedida,
            ];
            if (!empty($pb->codprodutoembalagem)) {
                $barra->unidademedida = $pb->ProdutoEmbalagem->UnidadeMedida->unidademedida;
                $barra->quantidade = intval($pb->ProdutoEmbalagem->quantidade);
            }
            $barras[] = $barra;
        }

        $observacoes .= "\n<b>Código de Barras:</b><ul>";
        foreach ($barras->sortBy('quantidade') as $barra) {
            if ($barra->quantidade > 1) {
                $lbl = "({$barra->unidademedida} com {$barra->quantidade})";
            } else {
                $lbl = "({$barra->unidademedida})";
            }
            $observacoes .= "<li>{$barra->barras} {$lbl}</li>";
        }
        $observacoes .= "</ul>";

        $outrasVariacoes = collect();
        $outrasEmbalagens = collect();
        foreach ($p->MercosProdutoS()->whereNull('inativo')->get() as $out) {
            if ($out->codprodutovariacao == $codprodutovariacao &&
                $out->codprodutoembalagem == $codprodutoembalagem) {
                continue;
            }

            if ($out->codprodutoembalagem == $codprodutoembalagem) {
                $outrasVariacoes[] = (object) [
                    'variacao' => $out->ProdutoVariacao->variacao,
                    'produtoid' => $out->produtoid,
                ];
            } elseif ($out->codprodutovariacao == $codprodutovariacao) {
                $emb = (object) [
                    'embalagem' => $p->UnidadeMedida->unidademedida,
                    'quantidade' => 1,
                    'produtoid' => $out->produtoid,
                ];
                if (!empty($out->codprodutoembalagem)) {
                    $emb->embalagem = $out->ProdutoEmbalagem->UnidadeMedida->unidademedida;
                    $emb->sigla = $out->ProdutoEmbalagem->UnidadeMedida->sigla;
                    $emb->quantidade = intval($out->ProdutoEmbalagem->quantidade);
                }
                $outrasEmbalagens[] = $emb;
            }
        }

        if (count($outrasEmbalagens) > 0)  {
            $observacoes .= " <b>Outras Embalagens:</b><ul>";
            foreach ($outrasEmbalagens->sortBy('quantidade') as $emb) {
                $lbl = $emb->embalagem;
                if ($emb->quantidade > 1) {
                    $lbl .= ' com ' . $emb->quantidade;
                }
                $observacoes .= "<li> <a href='/produtos/{$emb->produtoid}'>{$lbl}</a> </li>";
            }
            $observacoes .= " </ul>";
        }

        if (count($outrasVariacoes) > 0)  {
            $observacoes .= " <b>Outras Variações:</b> <ul>";
            foreach ($outrasVariacoes->sortBy('variacao') as $var) {
                $observacoes .= "<li> <a href='/produtos/{$var->produtoid}'>{$var->variacao}</a> </li>";
            }
            $observacoes .= "</ul>";
        }
        // $observacoes = str_replace("\r\n", "\n", $observacoes);

        $grade_cores = null;
        $grade_tamanhos = null;
        $excluido = (!empty($p->inativo));
        $ativo = true;
        // $categoria_id = null;
        $codigo_ncm = $p->Ncm->ncm;
        $multiplo = null;
        $peso_dimensoes_unitario = true;
        $exibir_no_b2b = true;

        $alt = Carbon::now();

        if (!empty($mp)) {
            $ret = $api->putProdutos(
                $mp->produtoid,
                $nome,
                $preco_tabela,
                $preco_minimo,
                $codigo,
                $comissao,
                $ipi,
                $tipo_ipi,
                $st,
                $moeda,
                $unidade,
                $saldo_estoque,
                $observacoes,
                $grade_cores,
                $grade_tamanhos,
                $excluido,
                $ativo,
                // $categoria_id,
                $codigo_ncm,
                $multiplo,
                $peso_bruto,
                $largura,
                $altura,
                $comprimento,
                $peso_dimensoes_unitario,
                $exibir_no_b2b
            );
        } else {
            $ret = $api->postProdutos(
                $nome,
                $preco_tabela,
                $preco_minimo,
                $codigo,
                $comissao,
                $ipi,
                $tipo_ipi,
                $st,
                $moeda,
                $unidade,
                $saldo_estoque,
                $observacoes,
                $grade_cores,
                $grade_tamanhos,
                $excluido,
                $ativo,
                // $categoria_id,
                $codigo_ncm,
                $multiplo,
                $peso_bruto,
                $largura,
                $altura,
                $comprimento,
                $peso_dimensoes_unitario,
                $exibir_no_b2b
            );
            $mp = MercosProduto::firstOrNew([
                'produtoid' => $api->headers['meuspedidosid']
            ]);
        }

        // Salva dados da ultima modificacao
        $mp->codproduto = $codproduto;
        $mp->codprodutovariacao = $codprodutovariacao;
        $mp->codprodutoembalagem = $codprodutoembalagem;
        $mp->preco = $preco_tabela;
        $mp->precoatualizado = $alt;
        $mp->saldoquantidade = $saldo_estoque;
        $mp->saldoquantidadeatualizado = $alt;
        // Verifica se o produto foi excluido no mercos
        if ($api->status == 412) {
            $excluido = true;
        } elseif (isset($api->responseObject->excluido)) {
            $excluido = $api->responseObject->excluido;
        }
        // dd($api->response);
        if (($excluido) && (empty($mp->inativo))) {
            $mp->inativo = Carbon::now();
        }
        $ret = $mp->save();

        // exporta imagem principal
        if (!empty($pv->codprodutoimagem)) {
            static::exportaImagem($mp->produtoid, $pv->codprodutoimagem, $mp->codmercosproduto, 1);
        }

        // exporta imagens adicionais
        foreach ($pv->ProdutoImagemProdutoVariacaoS as $pipv) {
            static::exportaImagem($mp->produtoid, $pipv->codprodutoimagem, $mp->codmercosproduto, 2);
        }

        $inativo = null;
        if ($mp->inativo instanceof Carbon) {
            $inativo = $mp->inativo->toIso8601String();
        }

        // retorna
        return [
            'codmercosproduto' => $mp->codmercosproduto,
            'codproduto' => $codproduto,
            'codprodutovariacao' => $codprodutovariacao,
            'codprodutoembalagem' => $codprodutoembalagem,
            'inativo' => $inativo,
            'produtoid' => $mp->produtoid,
            'retorno' => $ret,
        ];
    }

    public static function atualizaProduto($codproduto)
    {
        $prod = Produto::findOrFail($codproduto);
        $res = [];
        foreach ($prod->MercosProdutoS()->whereNull('inativo')->get() as $mp) {
            $res[] = static::exportaProduto($mp->codproduto, $mp->codprodutovariacao, $mp->codprodutoembalagem);
            // break;
        }
        return [
            'retorno' => true,
            'atualizados' => count($res),
            'resultados' => $res,
        ];
    }

    // Exporta Imagem do Produto para o Mercos
    public static function exportaImagem($produtoid, $codprodutoimagem, $codmercosproduto, $ordem = 2)
    {
        $pi = ProdutoImagem::findOrFail($codprodutoimagem);
        $qtd = MercosProdutoImagem::where([
            'codmercosproduto' => $codmercosproduto,
            'codimagem' => $pi->codimagem,
        ])->count();
        if ($qtd > 0) {
            return;
        }
        $api = new MercosApi();
        $arquivo = './public/imagens/' . $pi->Imagem->arquivo;
        $data = file_get_contents($arquivo);
        $base64 = base64_encode($data);

        $ret = $api->postImagensProduto(
            $produtoid,
            $ordem,
            $base64
        );
        if ($ret) {
            $mpi = MercosProdutoImagem::firstOrNew([
                'codmercosproduto' => $codmercosproduto,
                'codimagem' => $pi->codimagem,
            ]);
            $mpi->save();
        }
        return $ret;
    }

    // Tenta descobrir qual o codprodutobarra
    public static function procurarProdutoBarra($id, $codigo, $agregador_id)
    {
        $mp = static::procurarPeloId($id);
        if ($mp == null) {
            $mp = static::procurarPeloId($agregador_id);
            if ($mp == null) {
                return ProdutoBarra::findOrFail(env('MERCOS_CODPRODUTOBARRA_NAO_CADASTRADO'));
            }
        }
        if (empty($mp)) {
            $mp = static::criarPeloCodigo($id, $codigo);
        }
        $qry = ProdutoBarra::where([
            'codproduto' => $mp->codproduto,
            'codprodutovariacao' => $mp->codprodutovariacao,
        ]);
        if (empty($mp->codprodutoembalagem)) {
            $qry->whereNull('codprodutoembalagem');
        } else {
            $qry->where('codprodutoembalagem', $mp->codprodutoembalagem);
        }
        if ($pb = $qry->first()) {
            return $pb;
        }
        return ProdutoBarra::findOrFail(env('MERCOS_CODPRODUTOBARRA_NAO_CADASTRADO'));
    }

    // carrega model Produto Pelo ID do Mercos
    public static function procurarPeloId ($id)
    {
        $mp = MercosProduto::where([
            'produtoid' => $id
        ])->first();
        return $mp;
    }

    // Cria De/Para de Produto pelo Codigo
    public static function criarPeloCodigo ($id, $codigo)
    {
        $arr = explode('-', $codigo);
        $codproduto = null;
        if (isset($arr[0])) {
            $codproduto = numeroLimpo($arr[0]);
        }
        $codprodutovariacao = null;
        if (isset($arr[1])) {
            $codprodutovariacao = numeroLimpo($arr[1]);
        } else {
            return null;
        }
        $codprodutoembalagem = null;
        if (isset($arr[2])) {
            $codprodutoembalagem = numeroLimpo($arr[2]);
        }
        $mp = new MercosProduto([
            'produtoid' => $id,
            'codproduto' => $codproduto,
            'codprodutovariacao' => $codprodutovariacao,
            'codprodutoembalagem' => $codprodutoembalagem,
        ]);
        $mp->save();
        return $mp;
    }

    // Sincroniza Listagem de Produtos pelo SQL Generico
    public static function sincronizaPeloSql ($sql, $params = [])
    {
        $prods = DB::select($sql, $params);
        foreach ($prods as $prod) {
            static::exportaProduto (
                $prod->codproduto,
                $prod->codprodutovariacao,
                $prod->codprodutoembalagem
            );
        }
    }

    // Inativa Produtos no Mercos que foram Inativados no MGLara
    public static function sincronizaInativos()
    {
        $sql = '
            select mp.codproduto, mp.codprodutovariacao, mp.codprodutoembalagem
            from tblmercosproduto mp
            inner join tblproduto p on (p.codproduto = mp.codproduto)
            where mp.inativo is null
            and p.inativo is not null
            order by p.alteracao
        ';
        static::sincronizaPeloSql($sql);
    }

    // ALtera precos unitarios e detalhes de produtos no mercos
    public static function sincronizaProdutos()
    {
        $sql = '
            select mp.codproduto, mp.codprodutovariacao, mp.codprodutoembalagem, p.alteracao, mp.alteracao
            from tblmercosproduto mp
            inner join tblproduto p on (p.codproduto = mp.codproduto)
            where mp.inativo is null
            and p.alteracao > mp.alteracao
            order by p.alteracao
        ';
        static::sincronizaPeloSql($sql);
    }

    // Altera Precos e detalhes de Embalagens no Mercos
    public static function sincronizaEmbalagens()
    {
        $sql = '
            select mp.codproduto, mp.codprodutovariacao, mp.codprodutoembalagem
            from tblmercosproduto mp
            inner join tblprodutoembalagem pe on (pe.codprodutoembalagem = mp.codprodutoembalagem)
            where mp.inativo is null
            and pe.alteracao > mp.alteracao
            order by pe.alteracao
        ';
        static::sincronizaPeloSql($sql);
    }

    // Altera detalhes de Variacoes no Mercos
    public static function sincronizaVariacoes()
    {
        $sql = '
            select mp.codproduto, mp.codprodutovariacao, mp.codprodutoembalagem
            from tblmercosproduto mp
            inner join tblprodutovariacao pv on (pv.codprodutovariacao = mp.codprodutovariacao)
            where mp.inativo is null
            and pv.alteracao > mp.alteracao
            order by pv.alteracao
        ';
        static::sincronizaPeloSql($sql);
    }

    // Altera Estoque no Mercos
    public static function sincronizaEstoque()
    {
        $locais = env('MERCOS_CODESTOQUELOCAL_DISPONIVEL');
        $sql = '
            select mp.codproduto, mp.codprodutovariacao, mp.codprodutoembalagem, mp.saldoquantidade, floor(sum(es.saldoquantidade) / coalesce(pe.quantidade, 1))
            from tblmercosproduto mp
            inner join tblproduto p on (p.codproduto = mp.codproduto)
            left join tblprodutoembalagem pe on (pe.codprodutoembalagem = mp.codprodutoembalagem)
            inner join tblestoquelocalprodutovariacao elpv on (elpv.codestoquelocal in (' . $locais . ') and elpv.codprodutovariacao = mp.codprodutovariacao)
            inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
            where mp.inativo is null
            group by mp.codproduto, mp.codprodutovariacao, mp.codprodutoembalagem, pe.quantidade, mp.saldoquantidade 
            having mp.saldoquantidade != floor(sum(es.saldoquantidade) / coalesce(pe.quantidade, 1))
            order by codproduto, codprodutovariacao, codprodutoembalagem 
        ';
        static::sincronizaPeloSql($sql);
    }

    // Exporta Imagens Principais para Mercos que não haviam sido enviadas ainda
    public static function sincronizaImagensPrincipais()
    {
        $sql = '
            select mp.produtoid, pim.codprodutoimagem, mp.codmercosproduto, mp.codproduto
            from tblmercosproduto mp
            inner join tblprodutovariacao pv on (pv.codprodutovariacao = mp.codprodutovariacao)
            inner join tblprodutoimagem pim on (pim.codprodutoimagem = pv.codprodutoimagem)
            left join tblmercosprodutoimagem mpi on (mpi.codmercosproduto = mp.codmercosproduto and mpi.codimagem = pim.codimagem)
            where mp.inativo is null
            and mpi.codmercosprodutoimagem is null
        ';
        $prods = DB::select($sql);
        foreach ($prods as $prod) {
            static::exportaImagem(
                $prod->produtoid,
                $prod->codprodutoimagem,
                $prod->codmercosproduto,
                1
            );
        }
    }

    // Exporta Imagens Principais para Mercos que não haviam sido enviadas ainda
    public static function sincronizaImagensAdicionais()
    {
        $sql = '
            select mp.produtoid, pim.codprodutoimagem, mp.codmercosproduto, mp.codproduto
            from tblmercosproduto mp
            inner join tblprodutovariacao pv on (pv.codprodutovariacao = mp.codprodutovariacao)
            inner join tblprodutoimagemprodutovariacao pipv on (pipv.codprodutovariacao = pv.codprodutovariacao)
            inner join tblprodutoimagem pim on (pim.codprodutoimagem = pipv.codprodutoimagem)
            left join tblmercosprodutoimagem mpi on (mpi.codmercosproduto = mp.codmercosproduto and mpi.codimagem = pim.codimagem)
            where mp.inativo is null
            and mpi.codmercosprodutoimagem is null
        ';
        $prods = DB::select($sql);
        foreach ($prods as $prod) {
            static::exportaImagem(
                $prod->produtoid,
                $prod->codprodutoimagem,
                $prod->codmercosproduto,
                2
            );
        }
    }

    // Sincroniza Inativos/Precos/Estoque no Mercos
    public static function sincroniza()
    {
        static::sincronizaInativos();
        static::sincronizaProdutos();
        static::sincronizaEmbalagens();
        static::sincronizaVariacoes();
        static::sincronizaImagensPrincipais();
        static::sincronizaImagensAdicionais();
        static::sincronizaEstoque();
    }

}
