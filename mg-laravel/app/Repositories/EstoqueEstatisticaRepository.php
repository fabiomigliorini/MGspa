<?php

namespace App\Repositories;

use DB;
use Carbon\Carbon;

use App\Models\Produto;
use App\Models\EstoqueLocal;
/*
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Cidade;
*/

class EstoqueEstatisticaRepository
{

    /*
    public static function standardDeviationPopulation ($a)
    {
      //variable and initializations
      $the_standard_deviation = 0.0;
      $the_variance = 0.0;
      $the_mean = 0.0;
      $the_array_sum = array_sum($a); //sum the elements
      $number_elements = count($a); //count the number of elements

      //calculate the mean
      $the_mean = $the_array_sum / $number_elements;

      //calculate the variance
      for ($i = 0; $i < $number_elements; $i++)
      {
        //sum the array
        $the_variance = $the_variance + ($a[$i] - $the_mean) * ($a[$i] - $the_mean);
      }

      $the_variance = $the_variance / $number_elements;

      //calculate the standard deviation
      $the_standard_deviation = pow( $the_variance, 0.5);

      //return the variance
      return $the_standard_deviation;
    }
    */


    public static function standardDeviationSample ($a)
    {
      //variable and initializations
      $the_standard_deviation = 0.0;
      $the_variance = 0.0;
      $the_mean = 0.0;
      $the_array_sum = array_sum($a); //sum the elements
      $number_elements = count($a); //count the number of elements

      //calculate the mean
      $the_mean = $the_array_sum / $number_elements;

      //calculate the variance
      for ($i = 0; $i < $number_elements; $i++)
      {
        //sum the array
        $the_variance = $the_variance + ($a[$i] - $the_mean) * ($a[$i] - $the_mean);
      }

      $the_variance = $the_variance / ($number_elements - 1.0);

      //calculate the standard deviation
      $the_standard_deviation = pow( $the_variance, 0.5);

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
        if ($probability < 0 ||
          $probability > 1)
        {
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
          $normSInv = -((((($c1 * $q + $c2) * $q + $c3) * $q + $c4) * $q + $c5) * $q + $c6) /(((($d1 * $q + $d2) * $q + $d3) * $q + $d4) * $q + 1);

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
    public static function calculaEstoqueMinimoPeloDesvioPadrao ($vendas, $tempo_minimo = 0.7, $tempo_maximo = 2.5, $nivel_servico = null)
    {

        // se tem mais de 3 registros, ignora mes atual
        $vendas_filtrada = $vendas;
        if ($vendas_filtrada->count() >= 3) {
            $vendas_filtrada = $vendas_filtrada->whereNotIn('mes', [date('Y-m-01')]);
        }

        // ignora de dois anos pra tras
        if ($vendas_filtrada->count() > 24) {
            $vendas_filtrada = $vendas_filtrada->sortByDesc('mes')->take(24);
        }

        // calcula nivel de servico e Z pelo historico das vendas
        $nivel_servico = static::calculaNivelServicoPelasVendas($vendas_filtrada);
        $z_ns = static::NormSInv($nivel_servico);

        // calcula demanda media e desvio padrao
        $demanda_media = $vendas_filtrada->avg('quantidade');
        $serie = $vendas_filtrada->pluck('quantidade')->toArray();
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

    public static function buscaSerieHistoricaVendasProduto ($codproduto, $meses = 49, $codprodutovariacao = null, $codestoquelocal = null)
    {

        $mes_final = Carbon::now()->startOfMonth();
        $mes_inicial = (clone $mes_final)->addMonths(($meses -1) * -1);

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
                select elpvv.mes, sum(quantidade) as quantidade
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
            )
            select meses.mes, coalesce(venda_mes.quantidade, 0) as quantidade
            from meses
            left join venda_mes on (venda_mes.mes = meses.mes)
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
        $vendas_filtrada = $vendas->filter(function ($value, $key) {
            return $value->quantidade > 0;
        });

        // Total de Meses
        $meses_total = $vendas->count();
        $meses_com_venda = $vendas_filtrada->count();

        // se não teve venda em nenhum mes
        if ($meses_total <= 0) {
            return 0.5;
        }

        // indice com percentual de meses com venda
        $indice_mes = $meses_com_venda / $meses_total;

        // Se vendeu em todos os meses
        if ($indice_mes == 1) {

            // se vendeu mais de 50 unidades por mes
            $soma_venda = $vendas_filtrada->sum('quantidade');
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

    public static function buscaEstatisticaProduto (
        $codproduto,
        $meses = 49,
        $codprodutovariacao = null,
        $codestoquelocal = null,
        $tempo_minimo = 0.7,
        $tempo_maximo = 2.5,
        $nivel_servico = 0.95
    )
    {

        // Busca Produto
        $p = Produto::with('Marca')->findOrFail($codproduto);

        // Busca Todas Variações do produto
        $pvs = $p->ProdutoVariacaoS()->select(['codprodutovariacao', 'variacao'])->get();
        $variacoes = array_map(function ($item) {
            $item['variacao'] = $item['variacao']??'{ Sem Variacao }';
            return $item;
        },$pvs->toArray());

        // Busca Variação Selecionada
        $variacao = null;
        if (!empty($codprodutovariacao)) {
            if ($pv = $pvs->where('codprodutovariacao', $codprodutovariacao)->first()) {
                $variacao = $pv->variacao;
            }
        }

        // Busca Todos Locais de Estoque
        $els = EstoqueLocal::ativo()->select(['codestoquelocal', 'estoquelocal', 'sigla'])->get();
        $locais = $els->toArray();

        // Buscal Local Selecionado
        $estoquelocal = null;
        if (!empty($codestoquelocal)) {
            $el = $els->where('codestoquelocal', $codestoquelocal)->first();
            $estoquelocal = $el->estoquelocal;
        }

        // Busca Serie Historica de Vendas
        $vendas = static::buscaSerieHistoricaVendasProduto($codproduto, $meses, $codprodutovariacao, $codestoquelocal);

        // Calcula Minimo pelo Desvio Padrao
        $tempo_minimo = ($p->Marca->estoqueminimodias / 30);
        $tempo_maximo = ($p->Marca->estoquemaximodias / 30);
        $estatistica = EstoqueEstatisticaRepository::calculaEstoqueMinimoPeloDesvioPadrao($vendas, $tempo_minimo, $tempo_maximo);

        // Monta Array de Retorno
        $ret = [
            'codproduto' => $p->codproduto,
            'produto' => $p->produto,
            'codmarca' => $p->codmarca,
            'marca' => $p->Marca->marca,
            'codprodutovariacao' => $codprodutovariacao,
            'variacao' => $variacao,
            'variacoes' => $variacoes,
            'codestoquelocal' => $codestoquelocal,
            'estoquelocal' => $estoquelocal,
            'locais' => $locais,
            'vendas' => $vendas,
            'estatistica' => $estatistica,
        ];

        return $ret;
    }

}
