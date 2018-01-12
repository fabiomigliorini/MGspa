<?php

namespace App\Repositories;

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
    public static function NormSInv($probability) {
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
     * @param  double $tempo_reposicao Tempo de reposicao em meses (21 dias = 0.7 meses)
     * @param  double $tempo_maximo Tempo de estoque maximo em meses (75 dias = 2.5 meses)
     * @param  double $nivel_servico Nivel de Servico (95% = 0.95)
     * @return array previsão
     */
    public static function calculaMinimoPeloDesvioPadrao ($serie, $tempo_reposicao = 0.7, $tempo_maximo = 2.5, $nivel_servico = 0.95)
    {

        $z_ns = static::NormSInv($nivel_servico);
        $demanda_media = array_sum($serie)/count($serie);
        $desvio_padrao = static::standardDeviationSample($serie);
        $estoque_seguranca = $z_ns * sqrt($tempo_reposicao) * $desvio_padrao;
        $ponto_pedido = ($demanda_media * $tempo_reposicao) + $estoque_seguranca;
        $estoque_maximo = ($demanda_media * $tempo_maximo) + $estoque_seguranca;

        $ret = [
            'serie' => $serie,
            'tempo_reposicao' => $tempo_reposicao,
            'tempo_maximo' => $tempo_maximo,
            'nivel_servico' => $nivel_servico,
            'z_ns' => $z_ns,
            'demanda_media' => $demanda_media,
            'desvio_padrao' => $desvio_padrao,
            'estoque_seguranca' => ceil($estoque_seguranca),
            'ponto_pedido' => ceil($ponto_pedido),
            'estoque_maximo' => ceil($estoque_maximo),
        ];

        return $ret;
    }

}
