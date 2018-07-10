<?php

namespace Mg\NFeTerceiro;

use Mg\NFePHP\NFePHPRepositoryConfig;
use Mg\Filial\Filial;

use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Common\Complements;

class NFeTerceiroRepository
{

    public static function consultaDfe (Filial $filial)
    {
        // $tools = new Tools($configJson, Certificate::readPfx($pfxcontent, $password));
        $tools = NFePHPRepositoryConfig::instanciaTools($filial);

        //só funciona para o modelo 55
        $tools->model('55');
        //este serviço somente opera em ambiente de produção
        $tools->setEnvironment(1);

        //este numero deverá vir do banco de dados nas proximas buscas para reduzir
        //a quantidade de documentos, e para não baixar várias vezes as mesmas coisas.
        $ultNSU = 0;
        $maxNSU = $ultNSU;
        $loopLimit = 50;
        $iCount = 0;

        //executa a busca de DFe em loop
        while ($ultNSU <= $maxNSU) {
            $iCount++;
            if ($iCount >= $loopLimit) {
                break;
            }
            try {
                //executa a busca pelos documentos
                $resp = $tools->sefazDistDFe($ultNSU);
                dd($resp);
            } catch (\Exception $e) {
                echo $e->getMessage();
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
            }
            //essas tags irão conter os documentos zipados
            $docs = $lote->getElementsByTagName('docZip');
            foreach ($docs as $doc) {
                $numnsu = $doc->getAttribute('NSU');
                $schema = $doc->getAttribute('schema');
                //descompacta o documento e recupera o XML original
                $content = gzdecode(base64_decode($doc->nodeValue));
                //identifica o tipo de documento
                $tipo = substr($schema, 0, 6);
                //processar o conteudo do NSU, da forma que melhor lhe interessar
                //esse processamento depende do seu aplicativo

            }
            sleep(2);
        }
    }

    // public static function sefazStatus (Filial $filial)
    // {
    //     $tools = NFePHPRepositoryConfig::instanciaTools($filial);
    //     $resp = $tools->sefazStatus();
    //     $st = new Standardize();
    //     $r = $st->toStd($resp);
    //     return $r;
    // }

    public static function listaNfeTerceiro ()
    {
        dd('aqui');

    }

    public static function detalhesNfeTerceiro ()
    {
        dd('aqui');

    }

    // public static function pesquisarSefaz ()
    // {
    //     dd('aqui');
    // }

}
