<?php

namespace Mg\NfeTerceiro;

use DB;

use Mg\NFePHP\NFePHPManifestacaoService;
use Mg\Produto\ProdutoService;

class NfeTerceiroItemService
{
    public static function copiaDadosUltimaOcorrencia(NfeTerceiroItem $destino)
    {
        // Primeiro tenta verifica se ja foi dado entrada em uma nota daquele fornecedor com a mesma referencia
        $sql = '
            select nti.codprodutobarra, nti.margem, nti.complemento, nti.margem, nti.vprod
            from tblnfeterceiro nt
            inner join tblnfeterceiroitem nti on (nti.codnfeterceiro = nt.codnfeterceiro)
            where nt.cnpj = :cnpj
            and nti.cprod = :cprod
            and nti.codprodutobarra is not null
            and nti.codnfeterceiro != :codnfeterceiro
            order by nti.alteracao desc, nti.codnfeterceiroitem desc
            limit 1
        ';
        $origem = DB::select($sql, [
            'codnfeterceiro' => $destino->codnfeterceiro,
            'cnpj' => $destino->NFeTerceiro->cnpj,
            'cprod' => $destino->cprod,
        ]);

        // se encontrou copia amarracao com nosso cadastro de produto, margem e complemento
        if (count($origem) != 0) {
            $origem = (object)$origem[0];
            $destino->codprodutobarra = $origem->codprodutobarra;

            if (!empty($origem->margem)) {
                $destino->margem = $origem->margem;
            }

            if (floatval($origem->complemento) > 0) {
                $complemento = floatval($origem->complemento) / floatval($origem->vprod);
                $destino->complemento = $complemento * $destino->vprod;
            }
            return $destino;
        }

        // se nao encontrou, procura pelo codigo de barras / barras trib
        $pb = ProdutoService::buscaPorBarras($destino->cean);
        if ($pb == false) {
            $pb = ProdutoService::buscaPorBarras($destino->ceantrib);
        }
        if ($pb) {
            $destino->codprodutobarra = $pb->codprodutobarra;
        }

        return $destino;

    }
}
