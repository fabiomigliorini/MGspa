<?php

namespace Mg\Produto;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use Mg\Estoque\EstoqueMesService;
use Mg\Estoque\EstoqueMovimento;
use Mg\Estoque\EstoqueSaldoConferencia;

class ProdutoVariacaoService
{

    public static function unificaVariacoes($codprodutovariacaoorigem, $codprodutovariacaodestino)
    {
        $pv_origem = ProdutoVariacao::findOrFail($codprodutovariacaoorigem);
        $pv_destino = ProdutoVariacao::findOrFail($codprodutovariacaodestino);

        if ($pv_origem->codproduto != $pv_destino->codproduto) {
            throw new \Exception('Variações não são do mesmo produto');
        }

        // Busca todos os meses que houve movimento de estoque
        $sql = '
            select
              elpv.codprodutovariacao as codprodutovariacaoorigem,
              elpv.codestoquelocal,
              es.codestoquesaldo as codestoquesaldoorigem,
              es.fiscal,
              mes.codestoquemes as codestoquemesorigem,
              mes.mes
            from tblestoquelocalprodutovariacao elpv
            inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
            left join tblestoquemes mes on (mes.codestoquesaldo = es.codestoquesaldo)
            where elpv.codprodutovariacao = :codprodutovariacao
        ';
        $binds = [
            'codprodutovariacao' => $codprodutovariacaoorigem
        ];
        $meses = collect(DB::select(DB::raw($sql), $binds));

        // Percorre todos meses, associando ao novo mes
        foreach ($meses as $mes) {

            // se tem mes, unifica a movimentacao do mes
            if (!empty($mes->codestoquemesorigem)) {

                // calcula novo estoquemes
                $mes->mes = Carbon::parse($mes->mes);
                $novo_mes = EstoqueMesService::buscaOuCria(
                    $mes->codestoquelocal,
                    $codprodutovariacaodestino,
                    $mes->fiscal,
                    $mes->mes
                );

                // Transfere movimento de estoque
                $mes->codestoquemesdestino = $novo_mes->codestoquemes;
                $mes->movimentos = EstoqueMovimento::where('codestoquemes', $mes->codestoquemesorigem)->update([
                    'codestoquemes' => $mes->codestoquemesdestino
                ]);

                if ($mes->movimentos) {
                    // recalcula Custo Médio para origem
                    $url = env('MGLARA_URL') . "estoque/calcula-custo-medio/{$mes->codestoquemesorigem}";
                    $res = json_decode(file_get_contents($url));
                    if ($res->response != "Agendado") {
                        throw new \Exception("Rode Manualmente... erro ao calcular custo medio do mes {$mes->codestoquemesorigem}.. $url");
                    }
                    // recalcula Custo Médio para destino
                    $url = env('MGLARA_URL') . "estoque/calcula-custo-medio/{$mes->codestoquemesdestino}";
                    $res = json_decode(file_get_contents($url));
                    if ($res->response != "Agendado") {
                        throw new \Exception("Rode Manualmente... erro ao calcular custo medio do mes {$mes->codestoquemesdestino}.. $url");
                    }
                }
                $mes->codestoquesaldodestino = $novo_mes->codestoquesaldo;
            } else {
                $novo_saldo = EstoqueSaldoRepository::buscaOuCria(
                    $mes->codestoquelocal,
                    $codprodutovariacaodestino,
                    $mes->fiscal
                );
                $mes->codestoquesaldodestino = $novo_saldo->codestoquesaldo;
            }

            // transfere conferencias de saldo
            $mes->conferencias = EstoqueSaldoConferencia::where('codestoquesaldo', $mes->codestoquesaldoorigem)->update([
                'codestoquesaldo' => $mes->codestoquesaldodestino
            ]);
        }

        // transfere codigos de barra
        foreach ($pv_origem->ProdutoBarraS as $pb) {
            $pb->variacao = $pb->variacao ?? $pv_origem->variacao;
            $pb->referencia = $pb->referencia ?? $pv_origem->referencia;
            $pb->codmarca = $pb->codmarca ?? $pv_origem->codmarca;
            $pb->codprodutovariacao = $codprodutovariacaodestino;
            $pb->save();
        }

        // Apaga a variacao de origem
        $pv_origem->delete();

        return $pv_destino;
    }
}
