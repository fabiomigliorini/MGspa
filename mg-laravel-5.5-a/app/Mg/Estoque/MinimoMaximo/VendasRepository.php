<?php

namespace Mg\Estoque\MinimoMaximo;

use DB;
use Carbon\Carbon;

use Mg\Marca\Marca;
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
        return $pv->update([
          'vendainicio' => $venda->vendainicio
        ]);
    }

    public static function atualizarUltimaCompra(ProdutoVariacao $pv)
    {
        // Busca Ultima Compra da Variacao
        $sql = "
          select
                tblnotafiscal.emissao
                , tblnotafiscalprodutobarra.valortotal
                , tblnotafiscalprodutobarra.quantidade * coalesce(tblprodutoembalagem.quantidade, 1) as quantidade
                , tblprodutoembalagem.quantidade as lotecompra
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
        $dataultimacompra = null;
        $quantidadeultimacompra = null;
        $custoultimacompra = null;
        $lotecompra = 1;
        if (isset($compra[0])) {
            $dataultimacompra = $compra[0]->emissao;
            $quantidadeultimacompra = $compra[0]->quantidade;
            if (!empty($quantidadeultimacompra)) {
                $custoultimacompra = $compra[0]->valortotal / $quantidadeultimacompra;
            }
            $lotecompra = $compra[0]->lotecompra;
            if (empty($lotecompra)) {
              $embs = $pv->Produto->ProdutoEmbalagemS()->orderBy('quantidade', 'desc')->get();
              $lotecompra = 1;
              foreach ($embs as $emb) {
                if (($quantidadeultimacompra % $emb->quantidade) == 0) {
                  $lotecompra = $emb->quantidade;
                  break;
                }
              }
            }
        }
        return $pv->update([
          'dataultimacompra' => $dataultimacompra,
          'quantidadeultimacompra' => $quantidadeultimacompra,
          'custoultimacompra' => $custoultimacompra,
          'lotecompra' => $lotecompra,
        ]);
    }

    public static function calcularMinimoMaximoGlobal(ProdutoVariacao $pv)
    {

        if (!empty($pv->inativo) || !empty($pv->Produto->inativo)) {
            return $pv->update([
              'estoqueminimo' => 0,
              'estoquemaximo' => 0,
            ]);
        }

        $mesCorrente = Carbon::today()->startOfMonth();
        //$mesCorrente = Carbon::parse('2017-05-01');

        // Busca ultimos 2 anos de vendas
        $sql = "
            select mes, sum(quantidade) as quantidade
            from tblestoquelocalprodutovariacao elpv
            inner join tblestoquelocalprodutovariacaovenda elpvv on (elpvv.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
            where elpv.codprodutovariacao = :codprodutovariacao
            and mes >= current_date - '2 years'::interval
            --and mes = '2017-05-01'
            group by mes
        ";
        $vendas = collect(DB::select($sql, [
          'codprodutovariacao' => $pv->codprodutovariacao,
        ]));

        // Decide quais os utilmos tres meses de vendas considerar
        $mesesVendas = [];
        switch ($mesCorrente->month) {
          case 1:
          case 2:
          case 3:
          case 4: // até abril = Outubro/Novembro/Dezembro do ano anterior
            $mesesVendas[] = Carbon::create($mesCorrente->year - 1, 10, 1, 0, 0, 0);
            $mesesVendas[] = Carbon::create($mesCorrente->year - 1, 11, 1, 0, 0, 0);
            $mesesVendas[] = Carbon::create($mesCorrente->year - 1, 12, 1, 0, 0, 0);
            break;

          case 5: // Maio = Novembro/Dezembro/Abril
            $mesesVendas[] = Carbon::create($mesCorrente->year - 1, 11, 1, 0, 0, 0);
            $mesesVendas[] = Carbon::create($mesCorrente->year - 1, 12, 1, 0, 0, 0);
            $mesesVendas[] = (clone $mesCorrente)->subMonth(1);
            break;

          case 6: // Junho = Dezembro/Abril/Maio
            $mesesVendas[] = Carbon::create($mesCorrente->year - 1, 12, 1, 0, 0, 0);
            $mesesVendas[] = (clone $mesCorrente)->subMonth(2);
            $mesesVendas[] = (clone $mesCorrente)->subMonth(1);
            break;

          default: // ultimos dois meses
            $mesesVendas[] = (clone $mesCorrente)->subMonth(3);
            $mesesVendas[] = (clone $mesCorrente)->subMonth(2);
            $mesesVendas[] = (clone $mesCorrente)->subMonth(1);
            break;
        }

        // transforma coluna mes em instancia do Carbon
        foreach ($vendas as $key => $venda) {
          $venda->mes = Carbon::parse($venda->mes);
          $venda->quantidade = (double) $venda->quantidade;
        }

        // Se mes Corrente vendeu mais que primeiro mês da Série, utiliza corrente
        $vendaMesCorrente = $vendas->firstWhere('mes', $mesCorrente)->quantidade??0;
        $vendaPrimeiroMes = $vendas->firstWhere('mes', $mesesVendas[0])->quantidade??0;
        $maximo = max($vendaMesCorrente, $vendaPrimeiroMes);

        // Soma outros dois meses da série
        $maximo += $vendas->firstWhere('mes', $mesesVendas[1])->quantidade??0;
        $maximo += $vendas->firstWhere('mes', $mesesVendas[2])->quantidade??0;

        // Faz Calculo do Volta As Aulas Caso seja no inicio ou final do ano
        if (in_array($mesCorrente->month, [1, 2, 3, 9, 10, 11, 12])) {

            // Decide quais os meses de volta as aulas
            $mesesAulas = [];
            $mesesAulasDescontar = [];
            switch ($mesCorrente->month) {
              case 1:
                $mesesAulas[] = Carbon::create($mesCorrente->year - 1, 1, 1, 0, 0, 0);
                $mesesAulas[] = Carbon::create($mesCorrente->year - 1, 2, 1, 0, 0, 0);
                $mesesAulas[] = Carbon::create($mesCorrente->year - 1, 3, 1, 0, 0, 0);
                $mesesAulasDescontar[] = Carbon::create($mesCorrente->year, 1, 1, 0, 0, 0);
                break;

              case 2:
                $mesesAulas[] = Carbon::create($mesCorrente->year - 1, 2, 1, 0, 0, 0);
                $mesesAulas[] = Carbon::create($mesCorrente->year - 1, 3, 1, 0, 0, 0);
                $mesesAulasDescontar[] = Carbon::create($mesCorrente->year, 2, 1, 0, 0, 0);
                break;

              case 3:
                $mesesAulas[] = Carbon::create($mesCorrente->year - 1, 3, 1, 0, 0, 0);
                $mesesAulasDescontar[] = Carbon::create($mesCorrente->year, 3, 1, 0, 0, 0);
                break;

              default:
                $mesesAulas[] = Carbon::create($mesCorrente->year, 1, 1, 0, 0, 0);
                $mesesAulas[] = Carbon::create($mesCorrente->year, 2, 1, 0, 0, 0);
                $mesesAulas[] = Carbon::create($mesCorrente->year, 3, 1, 0, 0, 0);
                break;
            }

            // Soma Vendas Volta as Aulas
            $maximoAulas = 0;
            foreach ($mesesAulas as $mes) {
              $maximoAulas += $vendas->firstWhere('mes', $mes)->quantidade??0;
            }

            // Desconta Venda do ano atual
            foreach ($mesesAulasDescontar as $mes) {
              $maximoAulas -= $vendas->firstWhere('mes', $mes)->quantidade??0;
            }

            // Maximo é o maior entre normal e volta as aulas
            $maximo = max($maximo, $maximoAulas);

        }

        $maximo = ceil($maximo);
        $minimo = ceil($maximo / 2);
        //dd($maximo);

        return $pv->update([
          'estoqueminimo' => $minimo,
          'estoquemaximo' => $maximo,
        ]);

    }

    public static function atualizar(ProdutoVariacao $pv)
    {
        static::sumarizarVendaMensal($pv);
        static::atualizarPrimeiraVenda($pv);
        static::atualizarUltimaCompra($pv);
        static::calcularMinimoMaximoGlobal($pv);
    }
}
