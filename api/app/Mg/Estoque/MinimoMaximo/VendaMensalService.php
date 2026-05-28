<?php

namespace Mg\Estoque\MinimoMaximo;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use Mg\Marca\Marca;
use Mg\Produto\Produto;
use Mg\Produto\ProdutoVariacao;
use Mg\Estoque\EstoqueLocalProdutoVariacaoService;
use Mg\Estoque\EstoqueLocalProdutoVariacaoVenda;
use Mg\Estoque\EstoqueLocalProdutoVariacao;
use Mg\Estoque\EstoqueLocalService;

class VendaMensalService
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
            and tblnegocioprodutobarra.inativo is null
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
            $elpv = EstoqueLocalProdutoVariacaoService::buscaOuCria($venda->codestoquelocal, $pv->codprodutovariacao);
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
            and tblnegocioprodutobarra.inativo is null
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

    public static function determinarPercentualEstoqueDeposito($quantidade)
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

    public static function determinarQuantidadeEstoqueDeposito($quantidade)
    {
        $perc = static::determinarPercentualEstoqueDeposito($quantidade);
        return floor(($quantidade * $perc) / 100);
    }

    public static function determinarPercentualEstoqueLocal($quantidade = null)
    {
        // if (empty($quantidade)) {
        //     $quantidade = $pv->estoquemaximo;
        // }

        switch ($quantidade) {
            case 1:
                return [
                    101001 => 0,
                    102001 => 0,
                    103001 => 100,
                    104001 => 0,
                    105001 => 0
                ];

            case 2:
                return [
                    101001 => 0,
                    102001 => 50,
                    103001 => 50,
                    104001 => 0,
                    105001 => 0
                ];

            case 3:
                return [
                    101001 => 0,
                    102001 => 33.3,
                    103001 => 33.4,
                    104001 => 33.3,
                    105001 => 0
                ];

            case 4:
                return [
                    101001 => 0,
                    102001 => 25,
                    103001 => 25,
                    104001 => 25,
                    105001 => 25
                ];

            case 5:
                return [
                    101001 => 0,
                    102001 => 20,
                    103001 => 40,
                    104001 => 20,
                    105001 => 20
                ];

            default:

                // determina quanto do estoque deve estar no deposito
                $codestoquelocal_deposito = 101001;
                $deposito = static::determinarPercentualEstoqueDeposito($quantidade);

                // determina quanto do estoque deve estar nas lojas
                $lojas = 100 - $deposito;

                // determina um padrao de distribuicao de acordo com faturamento
                $ret = [
                    $codestoquelocal_deposito => $deposito,
                    102001 => ($lojas * 35) / 100,
                    103001 => ($lojas * 46) / 100,
                    104001 => ($lojas * 12) / 100,
                    105001 => ($lojas * 7) / 100
                ];

                return $ret;
        }
    }

    public static function determinarMesesVendas(Carbon $data)
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

    public static function determinarMesesVendasAulas(Carbon $data)
    {
        $somar = [];
        $diminuir = [];

        switch ($data->month) {
            case 1:
                $somar[] = Carbon::create($data->year - 1, 1, 1, 0, 0, 0);
                $somar[] = Carbon::create($data->year - 1, 2, 1, 0, 0, 0);
                $somar[] = Carbon::create($data->year - 1, 3, 1, 0, 0, 0);
                $diminuir[] = Carbon::create($data->year, 1, 1, 0, 0, 0);
                break;

            case 2:
                $somar[] = Carbon::create($data->year - 1, 2, 1, 0, 0, 0);
                $somar[] = Carbon::create($data->year - 1, 3, 1, 0, 0, 0);
                $diminuir[] = Carbon::create($data->year, 2, 1, 0, 0, 0);
                break;

            case 3:
                $somar[] = Carbon::create($data->year - 1, 3, 1, 0, 0, 0);
                $diminuir[] = Carbon::create($data->year, 3, 1, 0, 0, 0);
                break;

            default:
                $somar[] = Carbon::create($data->year, 1, 1, 0, 0, 0);
                $somar[] = Carbon::create($data->year, 2, 1, 0, 0, 0);
                $somar[] = Carbon::create($data->year, 3, 1, 0, 0, 0);
                break;
        }

        return [
            'somar' => $somar,
            'diminuir' => $diminuir
        ];
    }

    public static function calcularMinimoMaximo(ProdutoVariacao $pv)
    {
        return false;

        // se produto inativo, zera estoque minimo e maximo
        if (!empty($pv->inativo) || !empty($pv->Produto->inativo)) {
            EstoqueLocalProdutoVariacao::where('codprodutovariacao', $pv->codprodutovariacao)->update([
                'estoqueminimo' => 0,
                'estoquemaximo' => 0,
            ]);
            return $pv->update([
                'estoqueminimo' => 0,
                'estoquemaximo' => 0,
            ]);
        }

        // variaveis estaticas
        // sao calculadas uma vez so na primeira chamada
        static $lojas;
        static $deposito;
        static $mesCorrente;
        static $mesesVendas = [];
        static $mesesVendasAulasGeral = [];
        static $mesesVendasAulasFilial = [];
        if (empty($lojas)) {
            $lojas = EstoqueLocalService::lojas();
            $deposito = EstoqueLocalService::deposito();
            $mesCorrente = Carbon::today()->startOfMonth();
            $mesesVendas = static::determinarMesesVendas($mesCorrente);
            $mesesVendasAulasGeral = [
                'somar' => [],
                'diminuir' => []
            ];
            if (in_array($mesCorrente->month, [9, 10, 11]) || ($mesCorrente->month == 12 && $mesCorrente->day < 25)) {
                $mesesVendasAulasGeral = static::determinarMesesVendasAulas($mesCorrente);
            }
            $mesesVendasAulasFilial = [
                'somar' => [],
                'diminuir' => []
            ];
            if (in_array($mesCorrente->month, [1, 2, 3]) || ($mesCorrente->month == 12 && $mesCorrente->day >= 25)) {
                $mesesVendasAulasFilial = static::determinarMesesVendasAulas($mesCorrente);
            }
        }

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
            $venda->quantidade = (float) $venda->quantidade;
        }

        // passa pelas filiais
        $totDeposito = 0;
        $totLojas = 0;
        $recalcular = [];
        foreach ($lojas as $loja) {

            // Filtra somente vendas daquele local
            $vendasLoja = $vendas->where('codestoquelocal', $loja->codestoquelocal);

            // Se mes Corrente vendeu mais que primeiro mês da Série, utiliza corrente
            $vendaMesCorrente = $vendasLoja->firstWhere('mes', $mesCorrente)->quantidade ?? 0;
            $vendaPrimeiroMes = $vendasLoja->firstWhere('mes', $mesesVendas[0])->quantidade ?? 0;
            $qtVenda = max($vendaMesCorrente, $vendaPrimeiroMes);

            // Soma outros dois meses da série
            $qtVenda += $vendasLoja->firstWhere('mes', $mesesVendas[1])->quantidade ?? 0;
            $qtVenda += $vendasLoja->firstWhere('mes', $mesesVendas[2])->quantidade ?? 0;

            // soma Vendas Volta As Aulas pra acumular no estoque da filial
            $qtAulasFilial = 0;
            foreach ($mesesVendasAulasFilial['somar'] as $mes) {
                $qtAulasFilial += $vendasLoja->firstWhere('mes', $mes)->quantidade ?? 0;
            }
            foreach ($mesesVendasAulasFilial['diminuir'] as $mes) {
                $qtAulasFilial -= $vendasLoja->firstWhere('mes', $mes)->quantidade ?? 0;
            }
            $qtVenda = round(max($qtVenda, $qtAulasFilial), 0);
            $qtDeposito = static::determinarQuantidadeEstoqueDeposito($qtVenda);
            $qtMaximo = $qtVenda - $qtDeposito;

            // soma Vendas Volta As Aulas pra acumular no estoque geral
            $qtAulasGeral = 0;
            foreach ($mesesVendasAulasGeral['somar'] as $mes) {
                $qtAulasGeral += $vendasLoja->firstWhere('mes', $mes)->quantidade ?? 0;
            }
            foreach ($mesesVendasAulasGeral['diminuir'] as $mes) {
                $qtAulasGeral -= $vendasLoja->firstWhere('mes', $mes)->quantidade ?? 0;
            }
            $qtAulasGeral = round($qtAulasGeral, 0);
            if ($qtAulasGeral > $qtVenda) {
                $qtDeposito += $qtAulasGeral - $qtVenda;
            }

            //TODO: Gravar Tamanho Lote De Transferencia na ELPV

            if ($qtMaximo > 0) {
                // Grava Minimo / Maximo da Filial
                $elpv = EstoqueLocalProdutoVariacao::firstOrCreate([
                    'codestoquelocal' => $loja->codestoquelocal,
                    'codprodutovariacao' => $pv->codprodutovariacao
                ]);
                $elpv->update([
                    'estoqueminimo' => ceil($qtMaximo / 2),
                    'estoquemaximo' => $qtMaximo
                ]);
            } else {
                $recalcular[] = $loja;
            }

            // Totaliza quantidades
            $totDeposito += $qtDeposito;
            $totLojas += $qtMaximo;
        }

        // Filtra somente do Deposito
        $vendasDeposito = $vendas->where('codestoquelocal', $deposito->codestoquelocal);

        // Se mes Corrente vendeu mais que primeiro mês da Série, utiliza corrente
        $vendaMesCorrente = $vendasDeposito->firstWhere('mes', $mesCorrente)->quantidade ?? 0;
        $vendaPrimeiroMes = $vendasDeposito->firstWhere('mes', $mesesVendas[0])->quantidade ?? 0;
        $qtVenda = max($vendaMesCorrente, $vendaPrimeiroMes);

        // Soma outros dois meses da série
        $qtVenda += $vendasDeposito->firstWhere('mes', $mesesVendas[1])->quantidade ?? 0;
        $qtVenda += $vendasDeposito->firstWhere('mes', $mesesVendas[2])->quantidade ?? 0;

        // Soma vendas meses volta as aulas do deposito
        foreach ($mesesVendasAulasFilial['somar'] as $mes) {
            $qtVenda += $vendasDeposito->firstWhere('mes', $mes)->quantidade ?? 0;
        }
        foreach ($mesesVendasAulasFilial['diminuir'] as $mes) {
            $qtVenda -= $vendasDeposito->firstWhere('mes', $mes)->quantidade ?? 0;
        }
        foreach ($mesesVendasAulasGeral['somar'] as $mes) {
            $qtVenda += $vendasDeposito->firstWhere('mes', $mes)->quantidade ?? 0;
        }
        foreach ($mesesVendasAulasGeral['diminuir'] as $mes) {
            $qtVenda -= $vendasDeposito->firstWhere('mes', $mes)->quantidade ?? 0;
        }
        $qtVenda = round($qtVenda, 0);

        // Totaliza Maximo do Deposito
        $totDeposito += $qtVenda;

        // se tem loja onde o maximo ficou zerado e quantidade do deposito >0
        // recalcula o estoue pra loja com base no que ficou pro deposito
        if ((sizeof($recalcular) > 0) && ($totDeposito > 0)) {

            // pega percentuais padrao de distribuicao do estoque
            $percs = static::determinarPercentualEstoqueLocal($totDeposito);

            // percorre recalculando o maximo das lojas
            $descDeposito = 0;
            foreach ($recalcular as $loja) {

                if (!isset($percs[$loja->codestoquelocal])) {
                    $qtMaximo = 0;
                } else {
                    $qtMaximo = round(($percs[$loja->codestoquelocal] * $totDeposito) / 100, 0);
                }

                // Grava Minimo / Maximo da Filial
                $elpv = EstoqueLocalProdutoVariacao::firstOrCreate([
                    'codestoquelocal' => $loja->codestoquelocal,
                    'codprodutovariacao' => $pv->codprodutovariacao
                ]);
                $elpv->update([
                    'estoqueminimo' => ceil($qtMaximo / 2),
                    'estoquemaximo' => $qtMaximo
                ]);

                $totLojas += $qtMaximo;
                $descDeposito += $qtMaximo;
            }

            // Desconta do depoisto o que foi recalculado pras lojas
            $totDeposito -= $descDeposito;
            if ($totDeposito < 0) {
                $totDeposito = 0;
            }
        }


        // Grava Minimo / Maximo do deposito
        $elpv = EstoqueLocalProdutoVariacao::firstOrCreate([
            'codestoquelocal' => $deposito->codestoquelocal,
            'codprodutovariacao' => $pv->codprodutovariacao
        ]);
        $elpv->update([
            'estoqueminimo' => ceil($totDeposito / 2),
            'estoquemaximo' => $totDeposito
        ]);

        // Grava Minimo / Maximo Total
        $total = $totDeposito + $totLojas;
        $pv->update([
            'estoqueminimo' => ceil($total / 2),
            'estoquemaximo' => $total
        ]);

        return true;
    }

    /**
     * Atualiza todos os dados de estatistica de estoque da Variacao
     */
    public static function atualizarVariacao(ProdutoVariacao $pv)
    {
        static::sumarizarVendaMensal($pv);
        static::atualizarPrimeiraVenda($pv);
        static::atualizarUltimaCompra($pv);
        //static::calcularMinimoMaximo($pv);
        return true;
    }

    /**
     * Atualiza todos os dados de estatistica de estoque do produto
     */
    public static function atualizarProduto(Produto $p)
    {
        foreach ($p->ProdutoVariacaoS()->orderBy('variacao')->get() as $pv) {
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
        $prods = $m->ProdutoS()->orderBy('produto')->get();
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
        $marcas = Marca::orderBy('marca')->get();
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
