<?php

namespace Mg\Produto;

use DB;

class ProdutoEmbalagemService
{
    public static function embalagemParaUnidade($codprodutoembalagem)
    {
        $pe = ProdutoEmbalagem::findOrFail($codprodutoembalagem);

        DB::beginTransaction();

        // Converte Negocios
        $sql = '
            with neg as (
            	select
            		npb.quantidade * pe.quantidade as quantidade,
            		npb.valorunitario / pe.quantidade as valorunitario,
            		npb.codnegocioprodutobarra
            	from tblnegocioprodutobarra npb
            	inner join tblprodutobarra pb on (pb.codprodutobarra = npb.codprodutobarra)
            	inner join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
            	where pb.codproduto = :codproduto
            	and pb.codprodutoembalagem = :codprodutoembalagem
            )
            update tblnegocioprodutobarra
            set codprodutobarra = (select min(pb.codprodutobarra) from tblprodutobarra pb where pb.codproduto = :codproduto and pb.codprodutoembalagem is null)
            , valorunitario = neg.valorunitario
            , quantidade = neg.quantidade
            from neg
            where tblnegocioprodutobarra.codnegocioprodutobarra = neg.codnegocioprodutobarra;
        ';
        DB::statement($sql, [
            'codproduto' => $pe->codproduto,
            'codprodutoembalagem' => $pe->codprodutoembalagem,
        ]);

        // Converte Notas
        $sql = '
            with neg as (
            	select
            		npb.quantidade * pe.quantidade as quantidade,
            		npb.valorunitario / pe.quantidade as valorunitario,
            		npb.codnotafiscalprodutobarra
            	from tblnotafiscalprodutobarra npb
            	inner join tblprodutobarra pb on (pb.codprodutobarra = npb.codprodutobarra)
            	inner join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
            	where pb.codproduto = :codproduto
            	and pb.codprodutoembalagem = :codprodutoembalagem
            )
            update tblnotafiscalprodutobarra
            set codprodutobarra = (select min(pb.codprodutobarra) from tblprodutobarra pb where pb.codproduto = :codproduto and pb.codprodutoembalagem is null)
            , valorunitario = neg.valorunitario
            , quantidade = neg.quantidade
            from neg
            where tblnotafiscalprodutobarra.codnotafiscalprodutobarra = neg.codnotafiscalprodutobarra;
        ';
        DB::statement($sql, [
            'codproduto' => $pe->codproduto,
            'codprodutoembalagem' => $pe->codprodutoembalagem,
        ]);


        // Cupom Fiscal
        $sql = '
            with neg as (
            	select
            		npb.quantidade * pe.quantidade as quantidade,
            		npb.valorunitario / pe.quantidade as valorunitario,
            		npb.codcupomfiscalprodutobarra
            	from tblcupomfiscalprodutobarra npb
            	inner join tblprodutobarra pb on (pb.codprodutobarra = npb.codprodutobarra)
            	inner join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
            	where pb.codproduto = :codproduto
            	and pb.codprodutoembalagem = :codprodutoembalagem
            )
            update tblcupomfiscalprodutobarra
            set codprodutobarra = (select min(pb.codprodutobarra) from tblprodutobarra pb where pb.codproduto = :codproduto and pb.codprodutoembalagem is null)
            , valorunitario = neg.valorunitario
            , quantidade = neg.quantidade
            from neg
            where tblcupomfiscalprodutobarra.codcupomfiscalprodutobarra = neg.codcupomfiscalprodutobarra;
        ';
        DB::statement($sql, [
            'codproduto' => $pe->codproduto,
            'codprodutoembalagem' => $pe->codprodutoembalagem,
        ]);

        // Nfe Terceiro
        $sql = '
            with neg as (
            	select
            		npb.qcom * pe.quantidade as qcom,
            		npb.vuncom / pe.quantidade as vuncom,
            		npb.codnfeterceiroitem
            	from tblnfeterceiroitem npb
            	inner join tblprodutobarra pb on (pb.codprodutobarra = npb.codprodutobarra)
            	inner join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
            	where pb.codproduto = :codproduto
            	and pb.codprodutoembalagem = :codprodutoembalagem
            )
            update tblnfeterceiroitem
            set codprodutobarra = (select min(pb.codprodutobarra) from tblprodutobarra pb where pb.codproduto = :codproduto and pb.codprodutoembalagem is null)
            , vuncom = neg.vuncom
            , qcom = neg.qcom
            from neg
            where tblnfeterceiroitem.codnfeterceiroitem = neg.codnfeterceiroitem;
        ';
        DB::statement($sql, [
            'codproduto' => $pe->codproduto,
            'codprodutoembalagem' => $pe->codprodutoembalagem,
        ]);

        DB::commit();

        return $pe;
    }
}
