<?php

namespace Mg\Estoque\MinimoMaximo;

use DB;
use Carbon\Carbon;

use Mg\Produto\Produto;
use Mg\Produto\ProdutoVariacao;
use Mg\Estoque\EstoqueLocalProdutoVariacaoRepository;
use Mg\Estoque\EstoqueLocalProdutoVariacaoVenda;

class VendasRepository
{
    public static function sumarizarVendaMensal(ProdutoVariacao $pv)
    {
        // Busca Totais de Vendas Agrupados Por Mês e Local
        $sql = "
          select
        	      tblnegocio.codestoquelocal
        	    , date_trunc('month', tblnegocio.lancamento) as mes
        	    , sum(tblnegocioprodutobarra.quantidade * coalesce(tblprodutoembalagem.quantidade, 1) * (case when tblnaturezaoperacao.codoperacao = 1 then -1 else 1 end)) as quantidade
        	    , sum(tblnegocioprodutobarra.valortotal * (case when tblnaturezaoperacao.codoperacao = 1 then -1 else 1 end)) as valor
        	from tblnegocio
        	inner join tblnaturezaoperacao on (tblnaturezaoperacao.codnaturezaoperacao = tblnegocio.codnaturezaoperacao)
        	inner join tblnegocioprodutobarra on (tblnegocioprodutobarra.codnegocio = tblnegocio.codnegocio)
        	inner join tblprodutobarra on (tblprodutobarra.codprodutobarra = tblnegocioprodutobarra.codprodutobarra)
        	left join tblprodutoembalagem on (tblprodutoembalagem.codprodutoembalagem = tblprodutobarra.codprodutoembalagem)
          inner join tblpessoa on (tblpessoa.codpessoa = tblnegocio.codpessoa)
        	where tblprodutobarra.codprodutovariacao = :codprodutovariacao
          and tblnegocio.codnegociostatus = 2 --Fechado
        	and (tblnaturezaoperacao.venda = true or tblnaturezaoperacao.vendadevolucao = true)
          and tblpessoa.codpessoa not in (select tblfilial.codpessoa from tblfilial)
        	group by
        	      tblnegocio.codestoquelocal
        	    , date_trunc('month', tblnegocio.lancamento);
        ";

        $vendas = DB::select($sql, [
          'codprodutovariacao' => $pv->codprodutovariacao
        ]);

        $inicio = Carbon::now();

        // atualiza na tabela de totais de venda
        foreach ($vendas as $venda) {
            $elpv = EstoqueLocalProdutoVariacaoRepository::buscaOuCria($venda->codestoquelocal, $pv->codprodutovariacao);
            $v = EstoqueLocalProdutoVariacaoVenda::firstOrNew([
              'codestoquelocalprodutovariacao' => $elpv->codestoquelocalprodutovariacao,
              'mes' => $venda->mes,
            ]);
            $v->quantidade = $venda->quantidade;
            $v->valor = $venda->valor;
            $v->ignorar = false;
            $v->vendadiaquantidade = $venda->quantidade / $v->mes->daysInMonth;
            $v->save();
        }

        // apaga registros que nao foram atualizados
        $sql = "
          DELETE FROM tblestoquelocalprodutovariacaovenda
          WHERE alteracao < :inicio
          AND codestoquelocalprodutovariacao in (
            SELECT tblestoquelocalprodutovariacao.codestoquelocalprodutovariacao
            FROM tblestoquelocalprodutovariacao
            WHERE tblestoquelocalprodutovariacao.codprodutovariacao = :codprodutovariacao
          )
        ";
        $ret = DB::delete($sql, [
          'inicio' => $inicio,
          'codprodutovariacao' => $pv->codprodutovariacao,
        ]);

        return true;
    }

    public static function atualizarPrimeiraVenda(ProdutoVariacao $pv)
    {
        // Busca Primeira Venda da Variacao
        $sql = "
          select
                min(tblnegocio.lancamento) as vendainicio
          from tblnegocio
          inner join tblnaturezaoperacao on (tblnaturezaoperacao.codnaturezaoperacao = tblnegocio.codnaturezaoperacao)
          inner join tblnegocioprodutobarra on (tblnegocioprodutobarra.codnegocio = tblnegocio.codnegocio)
          inner join tblprodutobarra on (tblprodutobarra.codprodutobarra = tblnegocioprodutobarra.codprodutobarra)
          inner join tblpessoa on (tblpessoa.codpessoa = tblnegocio.codpessoa)
          where tblprodutobarra.codprodutovariacao = :codprodutovariacao
          and tblnegocio.codnegociostatus = 2 --Fechado
          and (tblnaturezaoperacao.venda = true or tblnaturezaoperacao.vendadevolucao = true)
          and tblpessoa.codpessoa not in (select tblfilial.codpessoa from tblfilial)
        ";
        $venda = DB::select($sql, [
          'codprodutovariacao' => $pv->codprodutovariacao
        ])[0];
        $pv->vendainicio = $venda->vendainicio;
        return $pv->save();
    }

    public static function atualizarUltimaCompra(ProdutoVariacao $pv)
    {
        // Busca Ultima Compra da Variacao
        $sql = "
          select
                tblnotafiscal.emissao
                , tblnotafiscalprodutobarra.valortotal
                , tblnotafiscalprodutobarra.quantidade * coalesce(tblprodutoembalagem.quantidade, 1) as quantidade
          from tblnotafiscal
          inner join tblnaturezaoperacao on (tblnaturezaoperacao.codnaturezaoperacao = tblnotafiscal.codnaturezaoperacao)
          inner join tblnotafiscalprodutobarra on (tblnotafiscalprodutobarra.codnotafiscal = tblnotafiscal.codnotafiscal)
          inner join tblprodutobarra on (tblprodutobarra.codprodutobarra = tblnotafiscalprodutobarra.codprodutobarra)
          left join tblprodutoembalagem on (tblprodutoembalagem.codprodutoembalagem = tblprodutobarra.codprodutoembalagem )
          inner join tblpessoa on (tblpessoa.codpessoa = tblnotafiscal.codpessoa)
          where tblprodutobarra.codprodutovariacao = :codprodutovariacao
          and tblnotafiscal.nfeinutilizacao is null
          and tblnotafiscal.nfecancelamento is null
          and (tblnaturezaoperacao.compra = true)
          and tblpessoa.codpessoa not in (select tblfilial.codpessoa from tblfilial)
          order by tblnotafiscal.emissao desc
          limit 1
        ";
        $compra = DB::select($sql, [
          'codprodutovariacao' => $pv->codprodutovariacao
        ]);
        $pv->dataultimacompra = null;
        $pv->quantidadeultimacompra = null;
        $pv->custoultimacompra = null;
        if (isset($compra[0])) {
            $pv->dataultimacompra = $compra[0]->emissao;
            $pv->quantidadeultimacompra = $compra[0]->quantidade;
            $pv->custoultimacompra = $compra[0]->valortotal / (($pv->quantidadeultimacompra>0)?$pv->quantidadeultimacompra:0);
        }
        return $pv->save();
    }
}
