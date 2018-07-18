<?php

namespace Mg\NFeTerceiro;

use Mg\NFePHP\NFePHPRepositoryConfig;
use Mg\Filial\Filial;
use Mg\Pessoa\Pessoa;
use Mg\NotaFiscal\NotaFiscal;
use Mg\Estoque\EstoqueLocal;

use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Common\Complements;

use Carbon\Carbon;
use DB;

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
                $pathNFeTerceiro = NFeTerceiroRepositoryPath::pathDFe($filial, $numnsu, true);
                file_put_contents($pathNFeTerceiro, $content);

                if($schema == '"resNFe_v1.01.xsd"'){
                    static::armazenaDadosDFe($res, $filial);
                }

            }
            sleep(2);
        }  // FIM DO LOOP
    }

    public static function downloadNFeTerceiro (Filial $filial, $chave){

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

            if ($xml == null) {
                throw new \Exception('Não foi possível fazer o download');
            }else{
                // CONVERTE O XML EM UM OBJETO
                $st = new Standardize();
                $res = $st->toStd($xml);
                $chave = $res->protNFe->infProt->chNFe;
            }

            // SALVA NA PASTA O ARQUIVO DFE DA CONSULTA
            if(!empty($chave)){
                $pathNFeTerceiro = NFeTerceiroRepositoryPath::pathNFeTerceiro($filial, $chave, true);
                file_put_contents($pathNFeTerceiro, $xml);
            }
        } catch (\Exception $e) {
            echo str_replace("\n", "<br/>", $e->getMessage());
        }


    }

    public static function carregarXml (Filial $filial, $chave)
    {

        // TRAZ O CAMNHO DO XML SE EXISTIR
        $path = NFeTerceiroRepositoryPath::pathNFeTerceiro($filial, $chave, true);

        // VERIFICA SE O AQRQUIVO EXISTE
        if (!file_exists($path)) {
            throw new \Exception('Nota Fiscal TErceiro não encontrada.');
        }else{
            // BUSCA XML NA PASTA  SE JA ESTIVER BAIXADO e CONVERTE EM UM OBJETO
            $xml = file_get_contents($path);
            $st = new Standardize();
            $res = $st->toStd($xml);
        }

        // BUSCA NA BASE DE DADOS O codpessoa, SE NAO TIVER CRIAR UM CADASTRO
        $codpessoa = Pessoa::select('codpessoa')
        ->where( 'ie', $res->NFe->infNFe->emit->IE )->orWhere( 'cnpj', $res->NFe->infNFe->emit->CNPJ )->get();
        // ->where([ [ 'ie', 'like', $res->NFe->infNFe->emit->IE ],
        //           [ 'cnpj', $res->NFe->infNFe->emit->CNPJ ] ])->get();

        // BUSCA NA BASE DE DADOS O coddistribuicaodfe DA DFE CONSULTADA
        $coddistribuicaodfe = NFeTerceiroDistribuicaoDfe::select('coddistribuicaodfe')
        ->where([ ['nfechave', $res->protNFe->infProt->chNFe],
                  ['schema', 'like', 'procNFe' . '%'] ])->get();

        // BUSCA NA BASE DE DADOS O cod estoquelocal
        $codestoquelocal = EstoqueLocal::select('codestoquelocal')->where('codfilial', $filial->codfilial)->get();

        DB::beginTransaction();

        $NF = new NotaFiscal();
        $NF->codnaturezaoperacao = 1;
        $NF->emitida = false; // rever este campo
        $NF->nfechave = $res->protNFe->infProt->chNFe;
        $NF->nfeimpressa = $res->NFe->infNFe->ide->tpImp;
        $NF->serie = $res->NFe->infNFe->ide->serie;
        $NF->numero = $res->NFe->infNFe->ide->nNF;
        $NF->emissao = Carbon::parse($res->NFe->infNFe->ide->dhEmi);
        $NF->saida = Carbon::now(); // rever este campo
        $NF->codfilial = $filial->codfilial;
        $NF->codpessoa = $codpessoa[0]->codpessoa;
        $NF->observacoes = $res->NFe->infNFe->infAdic->infCpl??null;
        $NF->volumes = $res->NFe->infNFe->transp->vol->qVol;
        $NF->codoperacao = $res->NFe->infNFe->ide->tpNF;
        $NF->nfereciboenvio = Carbon::parse($res->protNFe->infProt->dhRecbto);
        $NF->nfedataenvio = Carbon::parse($res->NFe->infNFe->ide->dhEmi);
        $NF->nfeautorizacao = $res->protNFe->infProt->xMotivo;
        $NF->nfedataautorizacao = Carbon::parse($res->protNFe->infProt->dhRecbto);
        $NF->valorfrete = $res->NFe->infNFe->total->ICMSTot->vFrete;
        $NF->valorseguro = $res->NFe->infNFe->total->ICMSTot->vSeg;
        $NF->valordesconto = $res->NFe->infNFe->total->ICMSTot->vDesc;
        $NF->valoroutras = $res->NFe->infNFe->total->ICMSTot->vOutro;
        $NF->nfecancelamento = null;
        $NF->nfedatacancelamento = null;
        $NF->nfeinutilizacao = null;
        $NF->nfedatainutilizacao = null;
        $NF->justificativa = $res->NFe->infNFe->ide->xJust??null;
        $NF->modelo = $res->NFe->infNFe->ide->mod;
        $NF->valorprodutos = $res->NFe->infNFe->total->ICMSTot->vProd;
        $NF->valortotal = $res->NFe->infNFe->total->ICMSTot->vNF;
        $NF->icmsbase = $res->NFe->infNFe->total->ICMSTot->vBC;
        $NF->icmsvalor = $res->NFe->infNFe->total->ICMSTot->vICMS;
        $NF->icmsstbase = $res->NFe->infNFe->total->ICMSTot->vBCST;
        $NF->icmsstvalor = $res->NFe->infNFe->total->ICMSTot->vST;
        $NF->ipibase = $res->NFe->infNFe->total->ICMSTot->vBC;
        $NF->ipivalor = $res->NFe->infNFe->total->ICMSTot->vIPI;
        $NF->frete = $res->NFe->infNFe->transp->modFrete;
        $NF->tpemis = $res->NFe->infNFe->ide->tpEmis;
        $NF->codestoquelocal = $codestoquelocal[0]->codestoquelocal;
        $NF->save();

        // BUSCA NA tblnotafiscal O codnotafiscal
        $codnotafiscal = NotaFiscal::select('codnotafiscal')->where('nfechave', $res->protNFe->infProt->chNFe)->get();

        // SALVA NA tblnotafiscalterceiro OS DADOS DA NOTA
        $NFeTerceiro = NFeTerceiro::firstOrNew([
            'nfechave' => $res->protNFe->infProt->chNFe,
            'numero' => $res->NFe->infNFe->ide->nNF
        ]);
        $NFeTerceiro->coddistribuicaodfe = $coddistribuicaodfe[0]->coddistribuicaodfe;
        $NFeTerceiro->codnotafiscal = $codnotafiscal[0]->codnotafiscal;
        $NFeTerceiro->codnegocio = null; // rever este campo, como gerar um negocio?
        $NFeTerceiro->codfilial = $filial->codfilial;
        $NFeTerceiro->codoperacao = $res->NFe->infNFe->ide->tpNF;
        $NFeTerceiro->codnaturezaoperacao = null; //usuario deve informar a natureza de operacao!
        $NFeTerceiro->codpessoa = $codpessoa[0]->codpessoa;
        $NFeTerceiro->emitente = $res->NFe->infNFe->emit->xNome;
        $NFeTerceiro->cnpj = $res->NFe->infNFe->emit->CNPJ;
        $NFeTerceiro->ie = $res->NFe->infNFe->emit->IE;
        $NFeTerceiro->emissao = Carbon::parse($res->NFe->infNFe->ide->dhEmi);
        $NFeTerceiro->ignorada = false; // onde esta buscar essa informacao?
        $NFeTerceiro->indsituacao = $res->NFe->infNFe->ide->cSitNFe??null;
        $NFeTerceiro->justificativa = $res->protNFe->infProt->xMotivo;
        $NFeTerceiro->indmanifestacao = null; // perguntar para o usuario a manifestacao?
        $NFeTerceiro->nfechave = $res->protNFe->infProt->chNFe;
        $NFeTerceiro->modelo = $res->NFe->infNFe->ide->mod;
        $NFeTerceiro->serie = $res->NFe->infNFe->ide->serie;
        $NFeTerceiro->numero = $res->NFe->infNFe->ide->nNF;
        $NFeTerceiro->entrada = null;  //perguntar para o ususario a data!
        $NFeTerceiro->valortotal = $res->NFe->infNFe->total->ICMSTot->vNF;
        $NFeTerceiro->icmsbase = $res->NFe->infNFe->total->ICMSTot->vBC;
        $NFeTerceiro->icmsvalor = $res->NFe->infNFe->total->ICMSTot->vICMS;
        $NFeTerceiro->icmsstbase = $res->NFe->infNFe->total->ICMSTot->vBCST;
        $NFeTerceiro->icmsstvalor = $res->NFe->infNFe->total->ICMSTot->vST;
        $NFeTerceiro->ipivalor = $res->NFe->infNFe->total->ICMSTot->vIPI;
        $NFeTerceiro->valorprodutos = $res->NFe->infNFe->total->ICMSTot->vProd;
        $NFeTerceiro->valorfrete = $res->NFe->infNFe->total->ICMSTot->vFrete;
        $NFeTerceiro->valorseguro = $res->NFe->infNFe->total->ICMSTot->vSeg;
        $NFeTerceiro->valordesconto = $res->NFe->infNFe->total->ICMSTot->vDesc;
        $NFeTerceiro->valoroutras = $res->NFe->infNFe->total->ICMSTot->vOutro;
        // dd($NFeTerceiro);
        $NFeTerceiro->save();

        // BUSCA NA tblnotafiscalterceiro o codnotafiscalterceiro
        $codnotafiscalterceiro = NFeTerceiro::select('codnotafiscalterceiro')->where('codnotafiscal', $codnotafiscal[0]->codnotafiscal)->get();

        //SALVA NA TABELA GRUPO
        $grupo = NFeTerceiroGrupo::firstOrNew([
            'codnotafiscalterceiro' => $codnotafiscalterceiro[0]->codnotafiscalterceiro
        ]);
        $grupo->codnotafiscalterceiro = $codnotafiscalterceiro[0]->codnotafiscalterceiro;
        $grupo->save();

        // BUSCA NA tblnotafiscalterceirogrupo o codnotafiscalterceirogrupo
        $codGrupo = NFeTerceiroGrupo::select('codnotafiscalterceirogrupo')->where('codnotafiscalterceiro', $codnotafiscalterceiro[0]->codnotafiscalterceiro)->get();

        // PARA CADA PRODUTO DA NOTA FAZ UM INSERT NO BANCO
        foreach ($res->NFe->infNFe->det as $key => $item) {
            $NFeItem = new NFeTerceiroItem();
            $NFeItem->codnotafiscalterceirogrupo = $codGrupo[0]->codnotafiscalterceirogrupo;
            $NFeItem->numero = $item->attributes->nItem;
            $NFeItem->referencia = $item->prod->cProd;
            $NFeItem->produto = $item->prod->xProd;
            $NFeItem->ncm = $item->prod->NCM;
            $NFeItem->cfop = $item->prod->CFOP;

            // VERIFICA SE EXISTE UM CODIGO DE BARRAS
            $barras = null;
            if (!is_string($item->prod->cEAN)){
                $barras = null;
            }else{
                $barras = $item->prod->cEAN;
            }
            $NFeItem->barras = $barras;

            // VERIFICA SE EXISTE UM CODIGO DE BARRAS TRIBUTAVEL
            $barrasTrib = null;
            if (!is_string($item->prod->cEANTrib)){
                $barrasTrib = null;
            }else{
                $barrasTrib = $item->prod->cEANTrib;
            }
            $NFeItem->barrastributavel =  $barrasTrib;

            $NFeItem->unidademedidatributavel = $item->prod->uTrib;
            $NFeItem->quantidadetributavel = $item->prod->qTrib;
            $NFeItem->valorunitariotributavel = $item->prod->vUnTrib;
            $NFeItem->unidademedida = $item->prod->uCom;
            $NFeItem->quantidade = $item->prod->qCom;
            $NFeItem->valorunitario = $item->prod->vUnCom;
            $NFeItem->valorproduto = $item->prod->vProd;
            $NFeItem->valorfrete = $res->NFe->infNFe->total->ICMSTot->vFrete;
            $NFeItem->valorseguro = $res->NFe->infNFe->total->ICMSTot->vSeg;
            $NFeItem->valordesconto = $res->NFe->infNFe->total->ICMSTot->vDesc;
            $NFeItem->valoroutras = $res->NFe->infNFe->total->ICMSTot->vOutro;
            $NFeItem->valortotal = $res->NFe->infNFe->total->ICMSTot->vNF;
            $NFeItem->compoetotal = $item->prod->indTot; // rever este campo
            $NFeItem->csosn = null; // rever este campo
            $NFeItem->origem = $item->imposto->ICMS->ICMS00->orig??null;
            $NFeItem->icmsbasemodalidade = $item->imposto->ICMS->ICMS00->modBC??null;
            $NFeItem->icmsbase = $item->imposto->ICMS->ICMS00->vBC??0;
            $NFeItem->icmspercentual = $item->imposto->ICMS->ICMS00->pICMS??0;
            $NFeItem->icmsvalor = $item->imposto->ICMS->ICMS00->vICMS??0;
            $NFeItem->icmscst = $item->imposto->ICMS->ICMS00->CST??0;
            $NFeItem->icmsstbasemodalidade = $item->imposto->ICMS->ICMS90->modBCST??null;
            $NFeItem->icmsstbase = $item->imposto->ICMS->ICMS90->vBCST??0;
            $NFeItem->icmsstpercentual = $item->imposto->ICMS->ICMS90->pICMSST??0;
            $NFeItem->icmsstvalor = $item->imposto->ICMS->ICMS90->vICMSST??0;
            $NFeItem->ipicst = $item->imposto->IPI->IPITrib->CST??0;
            $NFeItem->ipibase = $item->imposto->IPI->IPITrib->vBC??0;
            $NFeItem->ipipercentual = $item->imposto->IPI->IPITrib->pIPI??0;
            $NFeItem->ipivalor = $item->imposto->IPI->IPITrib->vIPI??0;
            $NFeItem->piscst = $item->imposto->PIS->PISAliq->CST??0;
            $NFeItem->pisbase = $item->imposto->PIS->PISAliq->vBC??0;
            $NFeItem->pispercentual = $item->imposto->PIS->PISAliq->pPIS??0;
            $NFeItem->pisvalor = $item->imposto->PIS->PISAliq->vPIS??0;
            $NFeItem->cofinscst = $item->imposto->COFINS->COFINSAliq->CST??0;
            $NFeItem->cofinsbase = $item->imposto->COFINS->COFINSAliq->vBC??0;
            $NFeItem->cofinspercentual = $item->imposto->COFINS->COFINSAliq->pCOFINS??0;
            $NFeItem->cofinsvalor = $item->imposto->COFINS->COFINSAliq->vCOFINS??0;
            $NFeItem->save();
        }

        if ( isset($res->NFe->infNFe->cobr)){
            foreach ($res->NFe->infNFe->cobr->dup as $key => $duplicata) {
                $NFeDuplicata = NFeTerceiroDuplicata::firstOrNew([
                    'codnotafiscalterceiro' => $codnotafiscalterceiro[0]->codnotafiscalterceiro
                ]);
                $NFeDuplicata->codnotafiscalterceiro = $codnotafiscalterceiro[0]->codnotafiscalterceiro;
                $NFeDuplicata->codtitulo = null; // rever este campo
                $NFeDuplicata->duplicata = $duplicata->nDup;
                $NFeDuplicata->vencimento = Carbon::parse($duplicata->dVenc);
                $NFeDuplicata->valor = $duplicata->vDup;
                $NFeDuplicata->ndup = $duplicata->nDup;
                $NFeDuplicata->dvenc = Carbon::parse($duplicata->dVenc);
                $NFeDuplicata->vdup = $duplicata->vDup;
                $NFeDuplicata->save();

            }
        }

        // $produtobarra = new NFeTerceiroProdutoBarra();
        // $produtobarra->codnotafiscalterceirogrupo = null;
        // $produtobarra->codprodutobarra = null;
        // $produtobarra->margem = null;
        // $produtobarra->complemento = null;
        // $produtobarra->quantidade = null;
        // $produtobarra->valorproduto = null;
        // $produtobarra->codusuariocriacao = null;
        // $produtobarra->codusuarioalteracao = null;

        DB::commit();

    }

    public static function armazenaDadosDFe ($xml, $filail) {

            $dfe = new NFeTerceiroDfe();
            $dfe->nfechave = $xml->chNFe;
            $dfe->cnpj = $xml->CNPJ;
            $dfe->emitente = $xml->xNome;
            $dfe->ie = $xml->IE;
            $dfe->emissao = Carbon::parse($xml->dhEmi);
            $dfe->valortotal = $xml->vNF;
            $dfe->recebimento = Carbon::parse($xml->dhRecbto);
            $dfe->digito = $xml->digVal;
            $dfe->protocolo = $xml->nProt;
            $dfe->tipo = $xml->tpNF;
            $dfe->csitnfe = $xml->cSitNFe;
            $dfe->codusuariocriacao = 2;
            $dfe->codusuarioalteracao = 2;
            $dfe->codfilial = $filial->codfilial;
            $dfe->save();

    }

    public static function listaNFeTerceiro () {

        $qry = NFeTerceiro::select('*')->paginate(100);

        return ($qry);

    }

    public static function listaDFe () {

        $qry = NFeTerceiroDfe::select('*')->orderBy('emissao', 'DESC')->paginate(100);

        return ($qry);

    }

    public static function listaItem ($codgrupo) {

        $qry = NFeTerceiroItem::select('*')->where('codnotafiscalterceirogrupo', $codgrupo)->paginate(100);

        return ($qry);

    }


}
