<?php

namespace Mg\NotaFiscal;

use Illuminate\Support\Facades\DB;
use Exception;
use Mg\Tributacao\TributacaoService;
// use Mg\NaturezaOperacao\TributacaoNaturezaOperacao;

use Mg\Filial\Filial;

class NotaFiscalProdutoBarraService
{
    public static function calcularTributacao(NotaFiscalProdutoBarra &$nfpb, $somenteVazios = true)
    {
        if ($somenteVazios) {
            if ((!empty($nfpb->codcfop) && (!empty($nfpb->csosn) || ($nfpb->icmscst != '')))) {
                return true;
            }
        }

        if (empty($nfpb->codprodutobarra)) {
            return false;
        }

        if (empty($nfpb->codnotafiscal)) {
            return false;
        }

        if (empty($nfpb->NotaFiscal->Pessoa)) {
            return false;
        }

        if (empty($nfpb->NotaFiscal->Filial)) {
            return false;
        }

        // busca tributacao no banco
        $sql = '
            SELECT t.* 
            FROM tbltributacaonaturezaoperacao t
            WHERE t.codtributacao = :codtributacao 
            AND t.codtipoproduto = :codtipoproduto 
            AND t.bit = :bit
            AND t.codnaturezaoperacao  = :codnaturezaoperacao 
            AND (:ncm ilike t.ncm || \'%\' or t.ncm is null)
        ';
        $params = [
            'codtributacao' => $nfpb->ProdutoBarra->Produto->codtributacao,
            'codtipoproduto' => $nfpb->ProdutoBarra->Produto->codtipoproduto,
            'bit' => $nfpb->ProdutoBarra->Produto->Ncm->bit,
            'codnaturezaoperacao' => $nfpb->NotaFiscal->codnaturezaoperacao,
            'ncm' => $nfpb->ProdutoBarra->Produto->Ncm->ncm,
        ];

        if ($nfpb->NotaFiscal->Pessoa->Cidade->Estado == $nfpb->NotaFiscal->Filial->Pessoa->Cidade->Estado) {
            $sql .= 'AND t.codestado = :codestado';
            $params['codestado'] = $nfpb->NotaFiscal->Pessoa->Cidade->codestado;
        } else {
            $sql .= 'AND t.codestado is null';
        }
        $sql .= '
            ORDER BY codestado nulls last, char_length(ncm) desc nulls last
        ';
        $ret = DB::select($sql, $params);
        if (sizeof($ret) == 0) {
            throw new Exception('Erro ao calcular tributação. Impossível localizar tributação para o produto informado! ' . json_encode($params), 1);
        }
        $trib = $ret[0];

        // calcula valor encima do qual sera feito rateio/calculos
        $valorTotalFinal =
            $nfpb->valortotal
            - $nfpb->valordesconto
            + $nfpb->valorfrete
            + $nfpb->valorseguro
            + $nfpb->valoroutras;

        //Traz codigos de tributacao
        $nfpb->codcfop = $trib->codcfop;

        if ($nfpb->NotaFiscal->Filial->crt == Filial::CRT_REGIME_NORMAL) {

            //CST's
            $nfpb->icmscst = $trib->icmscst;
            $nfpb->ipicst = $trib->ipicst;
            $nfpb->piscst = $trib->piscst;
            $nfpb->cofinscst = $trib->cofinscst;

            $nfpb->certidaosefazmt = $trib->certidaosefazmt;

            if (!empty($trib->fethabkg)) {
                $nfpb->fethabkg = $trib->fethabkg;
                $nfpb->fethabvalor = $nfpb->fethabkg * $nfpb->quantidade;
            }

            if (!empty($trib->iagrokg)) {
                $nfpb->iagrokg = $trib->iagrokg;
                $nfpb->iagrovalor = $nfpb->iagrokg * $nfpb->quantidade;
            }

            if (!empty($trib->funruralpercentual)) {
                $nfpb->funruralpercentual = $trib->funruralpercentual;
                $nfpb->funruralvalor = ($nfpb->funruralpercentual * $valorTotalFinal) / 100;
            }

            if (!empty($trib->senarpercentual)) {
                $nfpb->senarpercentual = $trib->senarpercentual;
                $nfpb->senarvalor = ($nfpb->senarpercentual * $valorTotalFinal) / 100;
            }

            if (!empty($trib->observacoesnf)) {
                $nfpb->observacoes = $trib->observacoesnf;
            }

            if (!empty($valorTotalFinal) && ($nfpb->NotaFiscal->emitida)) {
                //Calcula ICMS
                if (!empty($trib->icmslpbase)) {
                    $nfpb->icmsbasepercentual = $trib->icmslpbase;
                    $nfpb->icmsbase = round(($nfpb->icmsbasepercentual * $valorTotalFinal) / 100, 2);
                }

                $nfpb->icmspercentual = $trib->icmslppercentual;
                if ($nfpb->icmspercentual == 12 && $nfpb->ProdutoBarra->Produto->importado) {
                    $nfpb->icmspercentual = 4;
                }

                if ((!empty($nfpb->icmsbase)) and (!empty($nfpb->icmspercentual))) {
                    $nfpb->icmsvalor = round(($nfpb->icmsbase * $nfpb->icmspercentual) / 100, 2);
                }

                //Calcula PIS
                if ($trib->pispercentual > 0) {
                    $nfpb->pisbase = $valorTotalFinal;
                    $nfpb->pispercentual = $trib->pispercentual;
                    $nfpb->pisvalor = round(($nfpb->pisbase * $nfpb->pispercentual) / 100, 2);
                }

                //Calcula Cofins
                if ($trib->cofinspercentual > 0) {
                    $nfpb->cofinsbase = $valorTotalFinal;
                    $nfpb->cofinspercentual = $trib->cofinspercentual;
                    $nfpb->cofinsvalor = round(($nfpb->cofinsbase * $nfpb->cofinspercentual) / 100, 2);
                }

                //Calcula CSLL
                if ($trib->csllpercentual > 0) {
                    $nfpb->csllbase = $valorTotalFinal;
                    $nfpb->csllpercentual = $trib->csllpercentual;
                    $nfpb->csllvalor = round(($nfpb->csllbase * $nfpb->csllpercentual) / 100, 2);
                }

                //Calcula IRPJ
                if ($trib->irpjpercentual > 0) {
                    $nfpb->irpjbase = $valorTotalFinal;
                    $nfpb->irpjpercentual = $trib->irpjpercentual;
                    $nfpb->irpjvalor = round(($nfpb->irpjbase * $nfpb->irpjpercentual) / 100, 2);
                }
            }
        } else {
            $nfpb->csosn = $trib->csosn;

            //Calcula ICMSs
            if (!empty($valorTotalFinal) && ($nfpb->NotaFiscal->emitida)) {
                if (!empty($trib->icmsbase)) {
                    $nfpb->icmsbase = round(($trib->icmsbase * $valorTotalFinal) / 100, 2);
                }

                $nfpb->icmspercentual = $trib->icmspercentual;

                if ((!empty($nfpb->icmsbase)) and (!empty($nfpb->icmspercentual))) {
                    $nfpb->icmsvalor = round(($nfpb->icmsbase * $nfpb->icmspercentual) / 100, 2);
                }
            }
        }
    }
}
