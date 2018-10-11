<?php

namespace Mg\Estoque\MinimoMaximo;

use Illuminate\Support\Facades\Log;
use DB;
use Carbon\Carbon;

use Mg\Marca\Marca;
use Mg\Produto\Produto;
use Mg\Produto\ProdutoVariacao;
use Mg\Estoque\EstoqueLocalProdutoVariacaoRepository;
use Mg\Estoque\EstoqueLocalProdutoVariacaoVenda;
use Mg\Estoque\EstoqueLocalProdutoVariacao;

class VendaMensalRepository
{

    /**
     * Sumariza venda mensal de cada variacao em
     * cada local na tabela tblestoquelocalprodutovariacaovenda
     */
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

    /**
     * Atualiza tblprodutovariacao.vendainicio
     */
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

    /**
     * atualiza na tblprodutovariacao os campos
     * - dataultimacompra
     * - quantidadeultimacompra
     * - custoultimacompra
     * - lotecompra
     */
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
            if ($quantidadeultimacompra > 0) {
                $custoultimacompra = $compra[0]->valortotal / $quantidadeultimacompra;
                $lotecompra = $compra[0]->lotecompra;
                if (empty($lotecompra)) {
                    $lotecompra = 1;
                    $embs = $pv->Produto->ProdutoEmbalagemS()->orderBy('quantidade', 'desc')->get();
                    foreach ($embs as $emb) {
                        $v = $quantidadeultimacompra / $emb->quantidade;
                        if (ceil($v) == $v) {
                            $lotecompra = $emb->quantidade;
                            break;
                        }
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

    /**
     * Calcula o Estoquem Minimo e Maximo da Variacao para toda empresa,
     * independente das filiais
     */
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
            inner join tblestoquelocalprodutovariacaovenda elpvv
                on (elpvv.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
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
        $minimo = ceil($maximo / 2);

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

        return $pv->update([
          'estoqueminimo' => $minimo,
          'estoquemaximo' => $maximo,
        ]);
    }

    /**
     * Calcula o Estoquem Minimo e Maximo da Variacao para cada EstoqueLocal
     */
    public static function calcularMinimoMaximoEstoqueLocal(ProdutoVariacao $pv)
    {

      // atualiza produtovariacao
        $pv = $pv->fresh();

        // onde e deposito
        $codestoquelocal_deposito = 101001;

        // quais sao percentuais padrao das filiais
        $percentuais = collect(static::determinaPercentualEstoqueLocal($pv));

        // inicializa variaveis de controle pra iteracao
        $minimo = static::ratearQuantidadePelosPercentuais($pv->estoqueminimo, $percentuais);
        $maximo = static::ratearQuantidadePelosPercentuais($pv->estoquemaximo, $percentuais);

        // atualiza registros no Banco de Dados
        foreach ($percentuais as $codestoquelocal => $percentual) {
            EstoqueLocalProdutoVariacao::where([
            'codestoquelocal' => $codestoquelocal,
            'codprodutovariacao' => $pv->codprodutovariacao,
          ])->update([
            'estoquemaximo' => $maximo[$codestoquelocal],
            'estoqueminimo' => $minimo[$codestoquelocal],
          ]);
        }

        // Limpa Minimo e Maximo de locais inativos
        $ret = EstoqueLocalProdutoVariacao::where([
          'codprodutovariacao' => $pv->codprodutovariacao,
        ])->whereNotIn('codestoquelocal', $percentuais->keys())->update([
          'estoqueminimo' => null,
          'estoquemaximo' => null,
        ]);

        return true;
    }

    public static function ratearQuantidadePelosPercentuais($quantidade, $percentuais)
    {
        $saldo = $quantidade;
        if (is_array($percentuais)) {
            $percentuais = collect($percentuais);
        }
        $locais = $percentuais->count();
        $i = 1;

        // percorre todos os locais
        foreach ($percentuais->sort() as $codestoquelocal => $percentual) {

            // calcula maximo
            $qtd = round(($percentual * $quantidade) / 100, 0);
            if (($qtd > $saldo) || ($i == $locais)) {
                $qtd = $saldo;
            }
            $saldo -= $qtd;
            $ret[$codestoquelocal] = $qtd;

            $i++;
        }
        return $ret;
    }

    public static function determinaPercentualEstoqueDeposito($quantidade)
    {
        if ($quantidade >= 200) {
            return 70;
        }
        if ($quantidade >= 100) {
            return 50;
        }
        if ($quantidade > 30) {
            return 20;
        }
        return 0;
    }

    public static function determinaPercentualEstoqueLocal(ProdutoVariacao $pv, $quantidade = null)
    {
        if (empty($quantidade)) {
            $quantidade = $pv->estoquemaximo;
        }

        switch ($quantidade) {
        case 1:
          return [
            101001 => 0,
            102001 => 0,
            103001 => 100,
            104001 => 0
          ];

        case 2:
          return [
            101001 => 0,
            102001 => 50,
            103001 => 50,
            104001 => 0
          ];

        case 3:
          return [
            101001 => 0,
            102001 => 33.3,
            103001 => 33.4,
            104001 => 33.3
          ];

        case 4:
          return [
            101001 => 0,
            102001 => 25,
            103001 => 50,
            104001 => 25
          ];

        case 5:
          return [
            101001 => 0,
            102001 => 40,
            103001 => 40,
            104001 => 20
          ];

        default:

          // determina quanto do estoque deve estar no deposito
          $codestoquelocal_deposito = 101001;
          $deposito = static::determinaPercentualEstoqueDeposito($quantidade);

          // determina quanto do estoque deve estar nas lojas
          $lojas = 100 - $deposito;

          // determina um padrao de distribuicao de acordo com faturamento
          $ret = [
            $codestoquelocal_deposito => $deposito,
            102001 => ($lojas * 38)/100,
            103001 => ($lojas * 50)/100,
            104001 => ($lojas * 12)/100
          ];

            // busca vendas das lojas
            $sql = "
                select elpv.codestoquelocal, es.saldoquantidade, sum(vda.quantidade) as quantidade
                from tblestoquelocal el
                left join tblestoquelocalprodutovariacao elpv on (elpv.codestoquelocal = el.codestoquelocal and elpv.codprodutovariacao = :codprodutovariacao)
                left join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
                left join tblestoquelocalprodutovariacaovenda vda on (vda.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and vda.mes >= date_trunc('month', current_date - '3 months'::interval))
                where el.inativo is null
                --and el.codestoquelocal not in (101001, 102001, 103001, 104001)
                --and el.codestoquelocal not in (104001)
                group by elpv.codestoquelocal, es.saldoquantidade
                order by elpv.codestoquelocal asc
            ";
            $params = [
                'codprodutovariacao' => $pv->codprodutovariacao,
            ];
            $vendas = collect(DB::select($sql, $params));

            // calcula venda total
            $venda_total = $vendas->sum('quantidade');

            // calcula percentual de acordo com venda da filial
            foreach ($ret as $codestoquelocal => $perc) {
                if ($codestoquelocal == $codestoquelocal_deposito) {
                    continue;
                }
                if (!$venda_filial = $vendas->firstWhere('codestoquelocal', $codestoquelocal)) {
                    continue;
                }
                if ($venda_filial->quantidade <= 0) {
                    continue;
                }
                $ret[$codestoquelocal] = (($venda_filial->quantidade / $venda_total) * $lojas);
            }

            // Caso percentual distribuicao seja diferente de 100% recalcula proporcionalmente
            $total = array_sum($ret);
            if ($total != 100) {
                $total_lojas = $total - $deposito;
                foreach ($ret as $codestoquelocal => $perc) {
                    if ($codestoquelocal == $codestoquelocal_deposito) {
                        continue;
                    }
                    $ret[$codestoquelocal] = ($ret[$codestoquelocal] / $total_lojas) * $lojas;
                }
            }

            return $ret;

        }
    }

    public static function determinarMesesVendas (Carbon $data)
    {
        $ret = [];
        switch ($data->month) {
            case 1:
            case 2:
            case 3:
            case 4: // até abril = Outubro/Novembro/Dezembro do ano anterior
                $ret[] = Carbon::create($data->year - 1, 10, 1, 0, 0, 0);
                $ret[] = Carbon::create($data->year - 1, 11, 1, 0, 0, 0);
                $ret[] = Carbon::create($data->year - 1, 12, 1, 0, 0, 0);
            break;

            case 5: // Maio = Novembro/Dezembro/Abril
                $ret[] = Carbon::create($data->year - 1, 11, 1, 0, 0, 0);
                $ret[] = Carbon::create($data->year - 1, 12, 1, 0, 0, 0);
                $ret[] = (clone $data)->subMonth(1);
            break;

            case 6: // Junho = Dezembro/Abril/Maio
                $ret[] = Carbon::create($data->year - 1, 12, 1, 0, 0, 0);
                $ret[] = (clone $data)->subMonth(2);
                $ret[] = (clone $data)->subMonth(1);
            break;

            default: // ultimos dois meses
                $ret[] = (clone $data)->subMonth(3);
                $ret[] = (clone $data)->subMonth(2);
                $ret[] = (clone $data)->subMonth(1);
            break;
        }
        return $ret;
    }

    public static function calcularMinimoMaximo(ProdutoVariacao $pv)
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
            select
                elpv.codestoquelocal,
                elpvv.mes,
                sum(elpvv.quantidade) as quantidade
            from tblestoquelocalprodutovariacao elpv
            inner join tblestoquelocalprodutovariacaovenda elpvv
                on (elpvv.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
            where elpv.codprodutovariacao = :codprodutovariacao
            and mes >= current_date - '2 years'::interval
            group by elpv.codestoquelocal, elpvv.mes
        ";
        $vendas = collect(DB::select($sql, [
          'codprodutovariacao' => $pv->codprodutovariacao,
        ]));

        // transforma coluna mes em instancia do Carbon
        foreach ($vendas as $key => $venda) {
            $venda->mes = Carbon::parse($venda->mes);
            $venda->quantidade = (double) $venda->quantidade;
        }


        // Decide quais os utilmos tres meses de vendas considerar
        static $mesesVendas = [];
        if (empty($mesesVendas)) {
            $mesesVendas = static::determinarMesesVendas($mesCorrente);
        }
        dd($vendas);

        // Se mes Corrente vendeu mais que primeiro mês da Série, utiliza corrente
        $vendaMesCorrente = $vendas->firstWhere('mes', $mesCorrente)->quantidade??0;
        $vendaPrimeiroMes = $vendas->firstWhere('mes', $mesesVendas[0])->quantidade??0;
        $maximo = max($vendaMesCorrente, $vendaPrimeiroMes);

        // Soma outros dois meses da série
        $maximo += $vendas->firstWhere('mes', $mesesVendas[1])->quantidade??0;
        $maximo += $vendas->firstWhere('mes', $mesesVendas[2])->quantidade??0;
        $minimo = ceil($maximo / 2);

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

        return $pv->update([
          'estoqueminimo' => $minimo,
          'estoquemaximo' => $maximo,
        ]);

        dd('aqui');
    }

    /**
     * Atualiza todos os dados de estatistica de estoque da Variacao
     */
    public static function atualizarVariacao(ProdutoVariacao $pv)
    {
        // static::sumarizarVendaMensal($pv);
        // static::atualizarPrimeiraVenda($pv);
        // static::atualizarUltimaCompra($pv);

        // Substituir
        // static::calcularMinimoMaximoGlobal($pv);
        // static::calcularMinimoMaximoEstoqueLocal($pv);

        static::calcularMinimoMaximo($pv);
        static::calcularMinimoMaximo($pv);

        return true;
    }

    /**
     * Atualiza todos os dados de estatistica de estoque do produto
     */
    public static function atualizarProduto(Produto $p)
    {
        foreach ($p->ProdutoVariacaoS()->orderBy('codprodutovariacao')->get() as $pv) {
            // Log::info("Min/Max PV - #{$pv->codprodutovariacao}");
            static::atualizarVariacao($pv);
        }
        return true;
    }

    /**
     * Atualiza todos os dados de estatistica de estoque da marca
     */
    public static function atualizarMarca(Marca $m)
    {
        $prods = $m->ProdutoS()->orderBy('codproduto')->get();
        $total = $prods->count();
        $i = 1;
        foreach ($prods as $p) {
            Log::info("Min/Max {$m->marca} - #{$p->codproduto} ($i/$total)");
            static::atualizarProduto($p);
            $i++;
        }
        return true;
    }

    /**
     * Atualiza todos os dados de estatistica de estoque de todos os produtos
     */
    public static function atualizar()
    {
        $marcas = Marca::orderBy('codmarca')->get();
        $total = $marcas->count();
        $i = 1;
        foreach ($marcas as $m) {
            Log::info("Min/Max {$m->marca} ($i/$total)");
            static::atualizarMarca($m);
            $i++;
        }
        return true;
    }
}
