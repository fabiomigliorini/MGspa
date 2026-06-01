<?php

namespace Mg\Produto;

use Illuminate\Support\Facades\DB;

class ProdutoEmbalagemService
{
    /**
     * Cria embalagem e gera 1 barra por variação existente do produto.
     */
    public static function criar(array $dados): ProdutoEmbalagem
    {
        return DB::transaction(function () use ($dados) {
            $pe = new ProdutoEmbalagem();
            $pe->fill($dados);
            $pe->save();

            foreach (ProdutoVariacao::where('codproduto', $pe->codproduto)->get() as $pv) {
                ProdutoBarraService::criar([
                    'codproduto' => $pe->codproduto,
                    'codprodutovariacao' => $pv->codprodutovariacao,
                    'codprodutoembalagem' => $pe->codprodutoembalagem,
                ]);
            }

            return $pe;
        });
    }

    /**
     * Atualiza embalagem; registra histórico se o preço mudar.
     */
    public static function atualizar(ProdutoEmbalagem $pe, array $dados): ProdutoEmbalagem
    {
        return DB::transaction(function () use ($pe, $dados) {
            $precoAntigo = $pe->preco;
            $pe->fill($dados);
            $pe->save();

            if ((float) $precoAntigo != (float) $pe->preco) {
                $h = new ProdutoHistoricoPreco();
                $h->codproduto = $pe->codproduto;
                $h->codprodutoembalagem = $pe->codprodutoembalagem;
                $h->precoantigo = $precoAntigo;
                $h->preconovo = $pe->preco;
                $h->save();
            }

            return $pe;
        });
    }

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
