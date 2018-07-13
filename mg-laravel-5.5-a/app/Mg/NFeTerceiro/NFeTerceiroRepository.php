<?php

namespace Mg\NFeTerceiro;

use Mg\NFePHP\NFePHPRepositoryConfig;
use Mg\Filial\Filial;

use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Common\Complements;
use Carbon\Carbon;

class NFeTerceiroRepository
{

    public static function consultaDfe (Filial $filial){

        $tools = NFePHPRepositoryConfig::instanciaTools($filial);
        //só funciona para o modelo 55
        $tools->model('55');
        //este serviço somente opera em ambiente de produção
        $tools->setEnvironment(1);

        // BUSCA NA BASE DE DADOS A ULTIMA NSU CONSULTADA
        $ultimoNsu = NFeTerceiroDistribuicaoDfe::select('nsu')->where('codfilial', $filial->codfilial)->get();
        $ultimoNsu = end($ultimoNsu);
        $ultimoNsu = end($ultimoNsu);

        //este numero deverá vir do banco de dados nas proximas buscas para reduzir
        //a quantidade de documentos, e para não baixar várias vezes as mesmas coisas.
        // $ultNSU = 0;
        $ultNSU = $ultimoNsu->nsu??0;
        $maxNSU = $ultNSU;
        $loopLimit = 50;
        $iCount = 0;

        // executa a busca de DFe em loop
        while ($ultNSU <= $maxNSU) {
            $iCount++;
            if ($iCount >= $loopLimit) {
                break;
            }

            try {
                //executa a busca pelos documentos
                $resp = $tools->sefazDistDFe($ultNSU);
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

                // CONVERTE O XML EM UM OBJETO
                $st = new Standardize();
                $res = $st->toStd($content);
                $chave = $res->chNFe??$res->protNFe->infProt->chNFe??$res->retEvento->infEvento->chNFe;

                // SALVA NA BASE DE DADOS O RESULTADO DA CONSULTA DFE
                $dfe = new NFeTerceiroDistribuicaoDfe();
                $dfe->nfechave = $chave??null;
                $dfe->codfilial = $filial->codfilial;
                $dfe->nsu = $numnsu;
                $dfe->schema = $schema;
                $dfe->save();

                // SALVA NA PASTA O ARQUIVO DFE DA CONSULTA
                $pathNFeTerceiro = NFeTerceiroRepositoryPath::pathDFe($filial, $chave, true);
                file_put_contents($pathNFeTerceiro, $content);

            }
            sleep(2);
        }  // FIM DO LOOP
    }

    public static function downloadNFeTerceiro (Filial $filial, $chave){

        // VERIFICA SE TEM UM XML JA BAIXADO
        $path = NFeTerceiroRepositoryPath::pathNFeTerceiro($filial, $chave, true);

        // BUSCA XML NA PASTA  SE JA ESTIVER BAIXADO e CONVERTE EM UM OBJETO
        if (file_exists($path)) {

            $xml = file_get_contents($path);
            $st = new Standardize();
            $res = $st->toStd($xml);

        }else{
            // FAZ A CONSULTA NO WEBSERVICE E TRAZ O XML
            try {
                $tools = NFePHPRepositoryConfig::instanciaTools($filial);
                //só funciona para o modelo 55
                $tools->model('55');
                //este serviço somente opera em ambiente de produção
                $tools->setEnvironment(1);
                $key = str_replace(' ', '', $chave);
                $response = $tools->sefazDownload($key);

                $stz = new Standardize($response);
                $std = $stz->toStd();
                if ($std->cStat != 138) {
                    echo "Documento não retornado. [$std->cStat] $std->xMotivo";
                    die;
                }
                $zip = $std->loteDistDFeInt->docZip;
                $xml = gzdecode(base64_decode($zip));
                // header('Content-type: text/xml; charset=UTF-8');
                // echo $xml;

            } catch (\Exception $e) {
                echo str_replace("\n", "<br/>", $e->getMessage());
            }
            // CONVERTE O XML EM UM OBJETO
            $st = new Standardize();
            $res = $st->toStd($xml);
            $chave = $res->protNFe->infProt->chNFe;
            dd($res);
            // SALVA NA PASTA O ARQUIVO DFE DA CONSULTA
            if(!empty($chave)){
                $pathNFeTerceiro = NFeTerceiroRepositoryPath::pathNFeTerceiro($filial, $chave, true);
                file_put_contents($pathNFeTerceiro, $xml);
            }
        }

        // // SALVA NA BASE DE DADOS O RESULTADO DA CONSULTA NFeTerceiro
        // $NFe = new NFeTerceiro();
        // $NFe->coddidtribuicaodfe = null;
        // $NFe->codnotafiscal = $res->NFe->infNFe->ide->cNF;
        // $NFe->codnegocio = null;
        // $NFe->codfilial = $filial->codfilial;
        // $NFe->codoperacao = $res->NFe->infNFe->ide->tpNF;
        // $NFe->codnaturezaoperacao = null; //id tblnaturezaoperacao?
        // $NFe->codpessoa = null; //id do fornecedor na tblpessoa?
        // $NFe->emitente = $res->NFe->infNFe->emit->xNome;
        // $NFe->cnpj = $res->NFe->infNFe->emit->CNPJ;
        // $NFe->ie = $res->NFe->infNFe->emit->IE;
        // $NFe->emissao = Carbon::parse($res->NFe->infNFe->ide->dhEmi);
        // $NFe->ignorada = false;
        // $NFe->indsituacao = null;
        // $NFe->justificativa = $res->protNFe->infProt->xMotivo;
        // $NFe->indmanifestacao = null;
        // $NFe->nfechave = $res->protNFe->infProt->chNFe;
        // $NFe->modelo = $res->NFe->infNFe->ide->mod;
        // $NFe->serie = $res->NFe->infNFe->ide->serie;
        // $NFe->numero = $res->NFe->infNFe->ide->nNF;
        // $NFe->entrada = null;  //perguntar para o ususario a data?
        // $NFe->valortotal = $res->NFe->infNFe->total->ICMSTot->vNF;
        // $NFe->icmsbase = $res->NFe->infNFe->total->ICMSTot->vBC;
        // $NFe->icmsvalor = $res->NFe->infNFe->total->ICMSTot->vICMS;
        // $NFe->icmsstbase = $res->NFe->infNFe->total->ICMSTot->vBCST;
        // $NFe->icmsstvalor = $res->NFe->infNFe->total->ICMSTot->vST;
        // $NFe->ipivalor = $res->NFe->infNFe->total->ICMSTot->vProd;
        // $NFe->valorprodutos = $res->NFe->infNFe->total->ICMSTot->vProd;
        // $NFe->valorfrete = $res->NFe->infNFe->total->ICMSTot->vFrete;
        // $NFe->valorseguro = $res->NFe->infNFe->total->ICMSTot->vSeg;
        // $NFe->valordesconto = $res->NFe->infNFe->total->ICMSTot->vDesc;
        // $NFe->valoroutras = $res->NFe->infNFe->total->ICMSTot->vOutro;
        // // dd($NFe);
        // printf(json_encode($NFe));
        // $NFe->save();
        //
        // //SALVA NA TABELA GRUPO O CODIGO DA NOTA E GERA UM CODIGO DO GRUPO
        // $grupo = new NFeTerceiroGrupo();
        // $grupo->codnotafiscalterceiro = $res->NFe->infNFe->ide->cNF;
        // $grupo->save();
        //
        // // BUSCA NA BASE DE DADOS A codnotafiscalterceirogrupo DA DFE CONSULTADA
        // $codGrupo = NFeTerceiroGrupo::select('codnotafiscalterceirogrupo')->where('nfechave', $res->NFe->infNFe->ide->cNF)->get();
        // $codGrupo = end($codGrupo);
        // $codGrupo = end($codGrupo);
        // dd($codGrupo);
        // // PARA CADA PRODUTO DA NOTA FAZ UM INSERT NO BANCO
        // foreach ($res->NFe->infNFe->det as $key => $item) {
        //
        //     // dd($item->imposto->ICMS->ICMS00->modBC);
        //     $NFeItem = new NFeTerceiroItem();
        //     $NFeItem->codnotafiscalterceirogrupo = null;
        //     $NFeItem->numero = $item->attributes->nItem;
        //     $NFeItem->referencia = $item->prod->cProd;
        //     $NFeItem->produto = $item->prod->xProd;
        //     $NFeItem->ncm = $item->prod->NCM;
        //     $NFeItem->cfop = $item->prod->CFOP;
        //     $NFeItem->barrastributavel = $item->prod->cEANTrib;
        //     $NFeItem->unidademedidatributavel = $item->prod->uTrib;
        //     $NFeItem->quantidadetributavel = $item->prod->qTrib;
        //     $NFeItem->valorunitariotributavel = $item->prod->vUnTrib;
        //     $NFeItem->barras = $item->prod->cEAN;
        //     $NFeItem->unidademedida = $item->prod->uCom;
        //     $NFeItem->quantidade = $item->prod->qCom;
        //     $NFeItem->valorunitario = $item->prod->vUnCom;
        //     $NFeItem->valorproduto = $item->prod->vProd;
        //     $NFeItem->valorfrete = $res->NFe->infNFe->total->ICMSTot->vFrete;
        //     $NFeItem->valorseguro = $res->NFe->infNFe->total->ICMSTot->vSeg;
        //     $NFeItem->valordesconto = $res->NFe->infNFe->total->ICMSTot->vDesc;
        //     $NFeItem->valoroutras = $res->NFe->infNFe->total->ICMSTot->vOutro;
        //     $NFeItem->valortotal = $res->NFe->infNFe->total->ICMSTot->vNF;
        //     $NFeItem->compoetotal = $item->prod->indTot;
        //     $NFeItem->csosn = null;
        //     $NFeItem->origem = $item->imposto->ICMS->ICMS00->orig??null;
        //     $NFeItem->icmsbasemodalidade = $item->imposto->ICMS->ICMS00->modBC??null;
        //     $NFeItem->icmsbase = $item->imposto->ICMS->ICMS00->vBC??0;
        //     $NFeItem->icmspercentual = $item->imposto->ICMS->ICMS00->pICMS??0;
        //     $NFeItem->icmsvalor = $item->imposto->ICMS->ICMS00->vICMS??0;
        //     $NFeItem->icmsst = $item->imposto->ICMS->ICMS00->CST??0;
        //     $NFeItem->icmsstbasemodalidade = $item->imposto->ICMS->ICMS90->modBCST??null;
        //     $NFeItem->icmsstbase = $item->imposto->ICMS->ICMS90->vBCST??0;
        //     $NFeItem->icmsstpercentual = $item->imposto->ICMS->ICMS90->pICMSST??0;
        //     $NFeItem->icmsstvalor = $item->imposto->ICMS->ICMS90->vICMSST??0;
        //     $NFeItem->ipicst = $item->imposto->IPI->IPITrib->CST??0;
        //     $NFeItem->ipibase = $item->imposto->IPI->IPITrib->vBC??0;
        //     $NFeItem->ipipercentual = $item->imposto->IPI->IPITrib->pIPI??0;
        //     $NFeItem->ipivalor = $item->imposto->IPI->IPITrib->vIPI??0;
        //     $NFeItem->piscst = $item->imposto->PIS->PISAliq->CST??0;
        //     $NFeItem->pisbase = $item->imposto->PIS->PISAliq->vBC??0;
        //     $NFeItem->pispercentual = $item->imposto->PIS->PISAliq->pPIS??0;
        //     $NFeItem->pisvalor = $item->imposto->PIS->PISAliq->vPIS??0;
        //     $NFeItem->cofinscst = $item->imposto->COFINS->COFINSAliq->CST??0;
        //     $NFeItem->cofinsbase = $item->imposto->COFINS->COFINSAliq->vBC??0;
        //     $NFeItem->cofinspercentual = $item->imposto->COFINS->COFINSAliq->pCOFINS??0;
        //     $NFeItem->cofinsvalor = $item->imposto->COFINS->COFINSAliq->vCOFINS??0;
        //     printf(json_encode($NFeItem));
        //     $NFeItem->save();
        //     // dd($item);
        // }

    }

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
