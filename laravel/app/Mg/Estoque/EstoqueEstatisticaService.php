<?php

namespace Mg\Estoque;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use Mg\Produto\Produto;

class EstoqueEstatisticaService
{
    public static function standardDeviationSample($a)
    {
        //variable and initializations
        $the_standard_deviation = 0.0;
        $the_variance = 0.0;
        $the_mean = 0.0;
        $the_array_sum = array_sum($a); //sum the elements
        $number_elements = count($a); //count the number of elements
        if ($number_elements <= 1) {
            return 0;
        }

        //calculate the mean
        $the_mean = $the_array_sum / $number_elements;

        //calculate the variance
        for ($i = 0; $i < $number_elements; $i++) {
            //sum the array
            $the_variance = $the_variance + ($a[$i] - $the_mean) * ($a[$i] - $the_mean);
        }

        $the_variance = $the_variance / ($number_elements - 1.0);

        //calculate the standard deviation
        $the_standard_deviation = pow($the_variance, 0.5);

        //return the variance
        return $the_standard_deviation;
    }

    /**
     * Returns the inverse of the standard normal cumulative distribution.
     * The distribution has a mean of zero and a standard deviation of one.
     * Resources:
     *   http://board.phpbuilder.com/showthread.php?10367349-PHP-NORMSINV
     *   http://www.source-code.biz/snippets/vbasic/9.htm
     * @param  integer $week number of week
     * @return float       sales
     */
    public static function NormSInv($probability)
    {
        $a1 = -39.6968302866538;
        $a2 = 220.946098424521;
        $a3 = -275.928510446969;
        $a4 = 138.357751867269;
        $a5 = -30.6647980661472;
        $a6 = 2.50662827745924;

        $b1 = -54.4760987982241;
        $b2 = 161.585836858041;
        $b3 = -155.698979859887;
        $b4 = 66.8013118877197;
        $b5 = -13.2806815528857;

        $c1 = -7.78489400243029E-03;
        $c2 = -0.322396458041136;
        $c3 = -2.40075827716184;
        $c4 = -2.54973253934373;
        $c5 = 4.37466414146497;
        $c6 = 2.93816398269878;

        $d1 = 7.78469570904146E-03;
        $d2 = 0.32246712907004;
        $d3 = 2.445134137143;
        $d4 =  3.75440866190742;

        $p_low = 0.02425;
        $p_high = 1 - $p_low;
        $q = 0;
        $r = 0;
        $normSInv = 0;
        if (
            $probability < 0 ||
            $probability > 1
        ) {
            throw new \Exception("normSInv: Argument out of range.");
        } else if ($probability < $p_low) {

            $q = sqrt(-2 * log($probability));
            $normSInv = ((((($c1 * $q + $c2) * $q + $c3) * $q + $c4) * $q + $c5) * $q + $c6) / (((($d1 * $q + $d2) * $q + $d3) * $q + $d4) * $q + 1);
        } else if ($probability <= $p_high) {

            $q = $probability - 0.5;
            $r = $q * $q;
            $normSInv = ((((($a1 * $r + $a2) * $r + $a3) * $r + $a4) * $r + $a5) * $r + $a6) * $q / ((((($b1 * $r + $b2) * $r + $b3) * $r + $b4) * $r + $b5) * $r + 1);
        } else {

            $q = sqrt(-2 * log(1 - $probability));
            $normSInv = - ((((($c1 * $q + $c2) * $q + $c3) * $q + $c4) * $q + $c5) * $q + $c6) / (((($d1 * $q + $d2) * $q + $d3) * $q + $d4) * $q + 1);
        }

        return $normSInv;
    }

    /**
     * Retorna array com calculo do estoque minimo utilizando o desvio padrão
     * @param  array $serie
     * @param  double $tempo_minimo Tempo de reposicao em meses (21 dias = 0.7 meses)
     * @param  double $tempo_maximo Tempo de estoque maximo em meses (75 dias = 2.5 meses)
     * @param  double $nivel_servico Nivel de Servico (95% = 0.95)
     * @return array previsão
     */
    public static function calculaEstoqueMinimoPeloDesvioPadrao($vendas, $tempo_minimo = 0.7, $tempo_maximo = 2.5, $nivel_servico = null)
    {

        $vendas_filtradas = $vendas;

        // ignora mes atual quando ja tem mais de 3 meses de vendas
        if ($vendas_filtradas->count() >= 3) {
            $vendas_filtradas = $vendas_filtradas->whereNotIn('mes', [date('Y-m-01')]);
        }

        // Ignora Meses que nao teve vendas nem estoque
        $vendas_filtradas = $vendas_filtradas->filter(function ($mes, $key) {
            return (($mes->saldoquantidade > 0) || ($mes->vendaquantidade != 0));
        });

        // Considera somente ultimos 24 meses
        $vendas_filtradas = $vendas_filtradas->sortByDesc('mes')->take(24);

        // calcula nivel de servico e Z pelo historico das vendas
        $nivel_servico = static::calculaNivelServicoPelasVendas($vendas_filtradas);
        $z_ns = static::NormSInv($nivel_servico);

        // calcula demanda media e desvio padrao
        $demanda_media = $vendas_filtradas->avg('vendaquantidade');
        $serie = $vendas_filtradas->pluck('vendaquantidade')->toArray();
        $desvio_padrao = 0;
        if ($demanda_media > 0) {
            $desvio_padrao = static::standardDeviationSample($serie);
        }

        // calcula niveis de estoque
        $estoque_seguranca = $z_ns * sqrt($tempo_minimo) * $desvio_padrao;
        $estoque_minimo = ($demanda_media * $tempo_minimo) + $estoque_seguranca;
        $estoque_maximo = ($demanda_media * $tempo_maximo) + $estoque_seguranca;

        // retorna array com dados
        $ret = [
            'serie' => $serie,
            'tempominimo' => $tempo_minimo,
            'tempomaximo' => $tempo_maximo,
            'nivelservico' => $nivel_servico,
            'zns' => $z_ns,
            'demandamedia' => $demanda_media,
            'desviopadrao' => $desvio_padrao,
            'estoqueseguranca' => ceil($estoque_seguranca),
            'estoqueminimo' => ceil($estoque_minimo),
            'estoquemaximo' => ceil($estoque_maximo),
        ];

        return $ret;
    }

    public static function buscaSerieHistoricaVendasProduto($codproduto, $meses = 49, $codprodutovariacao = null, $codestoquelocal = null)
    {

        $mes_final = Carbon::now()->startOfMonth();
        $mes_inicial = (clone $mes_final)->addMonths(($meses - 1) * -1);

        $binds = [
            'codproduto' => $codproduto,
            'mes_inicial' => $mes_inicial,
            'mes_final' => $mes_final,
        ];

        $sql = '
            with meses as (
                SELECT date_trunc(\'month\', dd):: date as mes
                FROM generate_series
                       ( :mes_inicial
                       , :mes_final
                       , \'1 month\'::interval) dd
            ),
            venda_mes as (
                select elpvv.mes, sum(quantidade) as vendaquantidade, sum(valor) as vendavalor
                from tblprodutovariacao pv
                inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao)
                inner join tblestoquelocalprodutovariacaovenda elpvv on (elpvv.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
                where elpvv.mes >= :mes_inicial
                and pv.codproduto = :codproduto
        ';

        if (!empty($codprodutovariacao)) {
            $sql .= ' and pv.codprodutovariacao = :codprodutovariacao ';
            $binds['codprodutovariacao'] = $codprodutovariacao;
        }

        if (!empty($codestoquelocal)) {
            $sql .= ' and elpv.codestoquelocal = :codestoquelocal ';
            $binds['codestoquelocal'] = $codestoquelocal;
        }

        $sql .= '
                group by elpvv.mes
            ),
            saldos_mes as (
                select
                  	m.mes
                  	, sum(em.saldoquantidade) as saldoquantidade
                  	, sum(em.saldovalor) as saldovalor
                from meses m, tblestoquelocal el
                inner join tblprodutovariacao pv on (pv.codproduto = :codproduto)
                left join tblestoquelocalprodutovariacao elpv on (elpv.codestoquelocal = el.codestoquelocal and elpv.codprodutovariacao = pv.codprodutovariacao)
                left join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
                left join lateral (
                  	select iq.*
                  	from tblestoquemes iq
                  	where iq.codestoquesaldo = es.codestoquesaldo
                  	and iq.mes <= m.mes
                  	order by iq.mes desc
                  	limit 1
                    ) em on (em.codestoquesaldo = es.codestoquesaldo)
                where es.fiscal = false
        ';

        if (!empty($codprodutovariacao)) {
            $sql .= ' and pv.codprodutovariacao = :codprodutovariacao ';
        }

        if (!empty($codestoquelocal)) {
            $sql .= ' and el.codestoquelocal = :codestoquelocal ';
        }

        $sql .= '
                group by m.mes
            )
            select
              meses.mes,
              coalesce(venda_mes.vendaquantidade, 0) as vendaquantidade,
              coalesce(venda_mes.vendavalor, 0) as vendavalor,
              coalesce(saldos_mes.saldoquantidade, 0) as saldoquantidade,
              coalesce(saldos_mes.saldovalor, 0) as saldovalor
            from meses
            left join venda_mes on (venda_mes.mes = meses.mes)
            left join saldos_mes on (saldos_mes.mes = meses.mes)
            order by meses.mes asc
        ';

        $regs = collect(DB::select(DB::raw($sql), $binds));
        return $regs;
    }

    /**
     * Calcula nivel de servico para um produto com base no historico de Vendas
     * 99% -> Vende mais de 50 unidades por mes
     * 95% -> Vende Todo Mese
     * 51 a 90% -> Percentual de meses com venda, por exemplo 75% pra um produto que vendeu em 9 meses de um ano (9/12 = 0.75)
     * 50% -> Vende menos de 50% dos meses
     * @param  collection $vendas
     * @return double nivel de servico
     */
    public static function calculaNivelServicoPelasVendas($vendas)
    {

        // filtra meses com venda
        $vendas_filtradas = $vendas->filter(function ($value, $key) {
            return $value->vendaquantidade > 0;
        });

        // Total de Meses
        $meses_total = $vendas->count();
        $meses_com_venda = $vendas_filtradas->count();

        // se não teve venda em nenhum mes
        if ($meses_total <= 0) {
            return 0.5;
        }

        // indice com percentual de meses com venda
        $indice_mes = $meses_com_venda / $meses_total;

        // Se vendeu em todos os meses
        if ($indice_mes == 1) {

            // se vendeu mais de 50 unidades por mes
            $soma_venda = $vendas_filtradas->sum('vendaquantidade');
            if ($soma_venda > (50 * $meses_total)) {
                return 0.99;
            }

            return 0.95;
        }

        // se vendeu em menos de metade dos meses
        if ($indice_mes <= 0.5) {
            return 0.5;
        }

        return $indice_mes;
    }

    public static function sumarizaVendasVoltaAsAulas($vendas)
    {
        // inicializa retorno
        $ret = collect();

        // Percorre vendas
        foreach ($vendas as $venda) {

            // se posterior à março, ignora
            $d = Carbon::createFromFormat('Y-m-d', $venda->mes);
            if ($d->month > 3) {
                continue;
            }

            // se não existe acumulador pro ano ainda, cria
            $ano = $d->year;
            if (!isset($ret[$ano])) {
                $ret[$ano] = (object) [
                    'ano' => $ano,
                    'vendaquantidade' => 0,
                    'vendavalor' => 0,
                ];
            }

            // acumula valores
            $ret[$ano]->vendaquantidade += $venda->vendaquantidade;
            $ret[$ano]->vendavalor += $venda->vendavalor;
        }

        // retorna registros acumulados
        return $ret;
    }

    public static function buscaSaldoEstoqueComparadoVendaAnualPorLocal($codproduto, $codprodutovariacao = null)
    {

        $binds = [
            'codproduto' => $codproduto,
        ];

        $sql = '
            with vendas as (
            	select elpv.codestoquelocal, sum(quantidade) as vendaquantidade, sum(valor) as vendavalor
            	from tblprodutovariacao pv
            	inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao)
            	inner join tblestoquelocalprodutovariacaovenda elpvv on (elpvv.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
            	where pv.codproduto = :codproduto
            	and elpvv.mes >= date_trunc(\'month\', now() - \'11 months\'::interval)
        ';

        if (!empty($codprodutovariacao)) {
            $sql .= ' and elpv.codprodutovariacao = :codprodutovariacao ';
            $binds['codprodutovariacao'] = $codprodutovariacao;
        }

        $sql .= '
            	group by elpv.codestoquelocal
            ),
            estoque as (
            	select elpv.codestoquelocal, sum(es.saldoquantidade) as saldoquantidade, sum(saldovalor) as saldovalor
            	from tblprodutovariacao pv
            	inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao)
            	inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
            	where pv.codproduto = :codproduto
        ';

        if (!empty($codprodutovariacao)) {
            $sql .= ' and elpv.codprodutovariacao = :codprodutovariacao ';
        }

        $sql .= '
            	group by elpv.codestoquelocal
            )
            select
              el.codestoquelocal,
              el.estoquelocal,
              el.sigla,
              el.inativo,
              e.saldoquantidade,
              e.saldovalor,
              v.vendaquantidade,
              v.vendavalor
            from tblestoquelocal el
            left join vendas v on (v.codestoquelocal = el.codestoquelocal)
            left join estoque e on (e.codestoquelocal = el.codestoquelocal)
            where el.inativo is null
            and el.controlaestoque = true
            --or e.saldoquantidade is not null
            --or v.vendaquantidade is not null
            order by codestoquelocal
        ';

        $regs = collect(DB::select(DB::raw($sql), $binds));
        return $regs;
    }

    public static function buscaSaldoEstoqueComparadoVendaAnualPorVariacao($codproduto, $codestoquelocal = null)
    {

        $binds = [
            'codproduto' => $codproduto,
        ];

        $sql = '
            with vendas as (
            	select elpv.codprodutovariacao, sum(elpvv.quantidade) as vendaquantidade, sum(elpvv.valor) as vendavalor
            	from tblprodutovariacao pv
            	inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao)
            	inner join tblestoquelocalprodutovariacaovenda elpvv on (elpvv.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
            	where pv.codproduto = :codproduto
            	and elpvv.mes >= date_trunc(\'month\', now() - \'11 months\'::interval)
        ';

        if (!empty($codestoquelocal)) {
            $sql .= ' and elpv.codestoquelocal = :codestoquelocal ';
            $binds['codestoquelocal'] = $codestoquelocal;
        }

        $sql .= '
            	group by elpv.codprodutovariacao
            ),
            estoque as (
            	select elpv.codprodutovariacao, sum(es.saldoquantidade) as saldoquantidade, sum(es.saldovalor) as saldovalor
            	from tblprodutovariacao pv
            	inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao)
            	inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
            	where pv.codproduto = :codproduto
        ';

        if (!empty($codestoquelocal)) {
            $sql .= ' and elpv.codestoquelocal = :codestoquelocal ';
        }

        $sql .= '
            	group by elpv.codprodutovariacao
            )
            select
              pv.codprodutovariacao,
              coalesce(pv.variacao, \'{ Sem Variação }\') as variacao,
              pv.descontinuado,
              pv.inativo,
              pv.vendainicio,
              e.saldoquantidade,
              e.saldovalor,
              v.vendaquantidade,
              v.vendavalor
            from tblprodutovariacao pv
            left join vendas v on (v.codprodutovariacao = pv.codprodutovariacao)
            left join estoque e on (e.codprodutovariacao = pv.codprodutovariacao)
            where pv.codproduto = :codproduto
            and (
            	pv.inativo is null
            	or e.saldoquantidade is not null
            	or v.vendaquantidade is not null
            	)
            order by variacao
        ';

        $regs = collect(DB::select(DB::raw($sql), $binds));
        return $regs;
    }

    public static function buscaEstatisticaProduto(
        $codproduto,
        $meses = null,
        $codprodutovariacao = null,
        $codestoquelocal = null
    ) {

        // Busca Produto
        $p = Produto::with('Marca')->findOrFail($codproduto);

        // Busca Todas Variações do produto
        $variacoes = static::buscaSaldoEstoqueComparadoVendaAnualPorVariacao($codproduto, $codestoquelocal);
        $vendainicio = $variacoes->min('vendainicio');
        if (empty($vendainicio)) {
            $vendainicio = date('Y-m-d');
        }
        $vendainicio = Carbon::createFromFormat('Y-m-d', $vendainicio);

        // Busca Variação Selecionada
        $vendaquantidade = null;
        $saldoquantidade = null;
        $variacao = null;
        if (!empty($codprodutovariacao)) {
            if ($pv = $variacoes->where('codprodutovariacao', $codprodutovariacao)->first()) {
                $variacao = $pv->variacao;
                if (!empty($pv->vendainicio)) {
                    $vendainicio = Carbon::createFromFormat('Y-m-d', $pv->vendainicio);
                }
                $vendaquantidade = $pv->vendaquantidade;
                $saldoquantidade = $pv->saldoquantidade;
            }
        }

        // Calcula quantidade de meses de venda para analisar
        $meses_venda = $vendainicio->startOfMonth()->diffInMonths() + 1;
        if (!empty($meses) && $meses < $meses_venda) {
            $meses_venda = $meses;
        }

        // Busca Todos Locais de Estoque
        $locais = static::buscaSaldoEstoqueComparadoVendaAnualPorLocal($codproduto, $codprodutovariacao);

        // TOtaliza venda do ano e saldo do estoque
        if (empty($codestoquelocal)) {
            $vendaquantidade = $locais->sum('vendaquantidade');
            $saldoquantidade = $locais->sum('saldoquantidade');
        } elseif (empty($codprodutovariacao)) {
            $vendaquantidade = $variacoes->sum('vendaquantidade');
            $saldoquantidade = $variacoes->sum('saldoquantidade');
        }

        // Buscal Local Selecionado
        $estoquelocal = null;
        if (!empty($codestoquelocal)) {
            $el = $locais->where('codestoquelocal', $codestoquelocal)->first();
            $estoquelocal = $el->estoquelocal;
        }

        // Busca Serie Historica de Vendas
        $vendas = static::buscaSerieHistoricaVendasProduto($codproduto, $meses_venda, $codprodutovariacao, $codestoquelocal);

        // Agrupa Vendas de Volta As Aulas
        $vendas_volta_aulas = static::sumarizaVendasVoltaAsAulas($vendas);

        // Calcula Minimo pelo Desvio Padrao
        // $tempo_minimo = ($p->Marca->estoqueminimodias / 30);
        // $tempo_maximo = ($p->Marca->estoquemaximodias / 30);

        // Minimo e maximo
        $sql = '
            select
            	sum(elpv.estoqueminimo) as estoqueminimo,
            	sum(elpv.estoquemaximo) as estoquemaximo
            from tblestoquelocalprodutovariacao elpv
            inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao)
            where pv.codproduto = :codproduto
        ';
        $params = [
            'codproduto' => $codproduto,
        ];
        if (!empty($codprodutovariacao)) {
            $sql .= ' and elpv.codprodutovariacao = :codprodutovariacao ';
            $params['codprodutovariacao'] = $codprodutovariacao;
        }
        if (!empty($codestoquelocal)) {
            $sql .= ' and elpv.codestoquelocal = :codestoquelocal ';
            $params['codestoquelocal'] = $codestoquelocal;
        }
        $minmax = DB::select($sql, $params);

        // Monta Array de Retorno
        $ret = [
            'codproduto' => $p->codproduto,
            'produto' => $p->produto,
            'saldoquantidade' => $saldoquantidade,
            'vendaquantidade' => $vendaquantidade,
            'codmarca' => $p->codmarca,
            'marca' => $p->Marca->marca,
            'codprodutovariacao' => $codprodutovariacao,
            'variacao' => $variacao,
            'variacoes' => $variacoes,
            'codestoquelocal' => $codestoquelocal,
            'estoquelocal' => $estoquelocal,
            'locais' => $locais,
            'vendas' => $vendas,
            'vendas_volta_aulas' => $vendas_volta_aulas,
            'estoqueminimo' => floatval($minmax[0]->estoqueminimo),
            'estoquemaximo' => floatval($minmax[0]->estoquemaximo),
            // 'estatistica' => $estatistica,
            // 'estatistica' => [],
        ];

        return $ret;
    }
}
