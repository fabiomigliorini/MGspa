<?php

namespace Mg\Produto;

use DB;
use Mg\MgService;

class ProdutoService extends MgService
{

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
                    if ($pb = ProdutoBarra::where('codproduto', $codigo)->whereHas('ProdutoEmbalagem', function($query) use ($quantidade) {
                        $query->where('quantidade', $quantidade);
                    })->first())
                    return $pb;
                }
            }
        }

        return false;

    }

    public static function listagemPdvCount()
    {
        $sql = '
            select
            	count(pb.codprodutobarra) as count
            from tblprodutobarra pb 
        ';
        $regs = DB::select($sql);
        return $regs[0];
    }

    public static function listagemPdv($codprodutobarra, $limite)
    {
        $sincronizado = date('Y-m-d h:i:s');
        $sql = '
            select
            	pb.codprodutobarra,
                p.codproduto,
            	pb.barras,
                p.produto,
                pv.variacao,
                p.abc,
            	coalesce(ume.sigla, um.sigla) as sigla,
            	pe.quantidade,
            	pri.codimagem,
            	coalesce(pe.preco, p.preco * coalesce(pe.quantidade, 1)) as preco,
            	coalesce(pv.inativo, p.inativo) as inativo,
                :sincronizado as sincronizado
            from tblproduto p
            inner join tblunidademedida um on (um.codunidademedida = p.codunidademedida)
            inner join tblprodutovariacao pv  on (pv.codproduto = p.codproduto)
            inner join tblprodutobarra pb on (pb.codprodutovariacao = pv.codprodutovariacao)
            left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
            left join tblunidademedida ume on (ume.codunidademedida = pe.codunidademedida)
            left join tblprodutoimagem pri on (pri.codprodutoimagem = pv.codprodutoimagem)
            where pb.codprodutobarra >= :codprodutobarra
            order by pb.codprodutobarra
            limit :limite
        ';
        $regs = DB::select($sql,[
            'codprodutobarra' => $codprodutobarra,
            'limite' => $limite,
            'sincronizado' => $sincronizado
        ]);
        return array_map(function ($item) {
            if ($item->quantidade) {
                $item->quantidade = floatval($item->quantidade);
            }
            $item->preco = floatval($item->preco);
            if (!empty($item->variacao)) {
                $item->produto .= ' ' . $item->variacao;
            }
            $item->palavras = explode(' ', $item->produto);
            if (!empty($item->quantidade)) {
                $item->produto .= ' C/' . intval($item->quantidade);
            }
            return $item;
        }, $regs);
        return $regs;
    }

}
