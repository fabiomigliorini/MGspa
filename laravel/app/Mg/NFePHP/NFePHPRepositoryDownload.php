<?php

namespace Mg\NFePHP;

use Mg\NotaFiscal\NotaFiscal;
use Mg\Filial\Filial;

use NFePHP\NFe\Common\Standardize;

class NFePHPRepositoryDownload
{

    public static function downloadNotaFiscalTerceiro (Filial $filial, $chave){

        // FAZ A CONSULTA NO WEBSERVICE E TRAZ O XML
        try {
            $tools = NFePHPRepositoryConfig::instanciaTools($filial);
            //só funciona para o modelo 55
            $tools->model('55');
            //este serviço somente opera em ambiente de produção
            $tools->setEnvironment(1);
            $response = $tools->sefazDownload($chave);

            $stz = new Standardize($response);
            $std = $stz->toStd();
            if ($std->cStat != 138) {
                throw new \Exception("Documento não retornado. [$std->cStat] $std->xMotivo");
            }

            $zip = $std->loteDistDFeInt->docZip;
            $xml = gzdecode(base64_decode($zip));
            // header('Content-type: text/xml; charset=UTF-8');
            // echo $xml;

            if (!$xml == null) {
                // CONVERTE O XML EM UM OBJETO
                $st = new Standardize();
                $res = $st->toStd($xml);
            }
            return $res;

        } catch (\Exception $e) {
            echo str_replace("\n", "<br/>", $e->getMessage());
            return $e;
        }
    } // FIM DO DOWNLOAD NFeTerceiro

}
