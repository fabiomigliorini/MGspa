<?php

namespace Mg\NFePHP;

use Mg\NotaFiscal\NotaFiscal;
use Mg\Filial\Filial;

class NFePHPRepositoryConsultaSefaz
{

    public static function consultaSefaz($filial, $ultimoNsu){

        $tools = NFePHPRepositoryConfig::instanciaTools($filial);
        //só funciona para o modelo 55
        $tools->model('55');
        //este serviço somente opera em ambiente de produção
        $tools->setEnvironment(1);

        //este numero deverá vir do banco de dados nas proximas buscas para reduzir
        //a quantidade de documentos, e para não baixar várias vezes as mesmas coisas.
        // $ultNSU = 0;
        $ultNSU = $ultimoNsu->nsu??0;
        $maxNSU = $ultNSU;
        $loopLimit = 50;
        $iCount = 0;

        // executa a busca de DFe em loop
        $consulta = 1;
        // while ($iCount <= $consulta) {
        while ($ultNSU <= $maxNSU) {
            $iCount++;
            if ($iCount >= $loopLimit) {
                break;
            }

            try {
                //executa a busca pelos documentos
                $resp = $tools->sefazDistDFe($ultNSU);
            } catch (\Exception $e) {
                return $e->getMessage();
                //tratar o erro
            }

            //extrair e salvar os retornos
            $dom = new \DOMDocument();
            $dom->loadXML($resp);
            $node = $dom->getElementsByTagName('retDistDFeInt')->item(0);
            $tpAmb = $node->getElementsByTagName('tpAmb')->item(0)->nodeValue;
            $verAplic = $node->getElementsByTagName('verAplic')->item(0)->nodeValue;
            $cStat = $node->getElementsByTagName('cStat')->item(0)->nodeValue;
            $xMotivo = $node->getElementsByTagName('xMotivo')->item(0)->nodeValue;
            $dhResp = $node->getElementsByTagName('dhResp')->item(0)->nodeValue;
            $ultNSU = $node->getElementsByTagName('ultNSU')->item(0)->nodeValue;
            $maxNSU = $node->getElementsByTagName('maxNSU')->item(0)->nodeValue;
            $lote = $node->getElementsByTagName('loteDistDFeInt')->item(0);
            if (empty($lote)) {
                //lote vazio
                continue;
            }else{
                return $lote;
            }


            sleep(2);
        }

    }

}
