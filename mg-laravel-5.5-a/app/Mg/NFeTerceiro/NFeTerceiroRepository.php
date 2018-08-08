<?php

namespace Mg\NFeTerceiro;
use Mg\MgRepository;

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

class NFeTerceiroRepository extends MgRepository
{

    public static function manifestacao ($request){

        try {
            $filial = Filial::findOrFail($request->filial);

            $tools = NFePHPRepositoryConfig::instanciaTools($filial);

            //só funciona para o modelo 55
            $tools->model('55');

            //este serviço somente opera em ambiente de produção
            $tools->setEnvironment(1);

            //chave de 44 digitos da nota do fornecedor
            $chNFe = $request->nfechave;

            // 210200 OPERACAO REALIZADA
            // 210210 CIENCIA DA OPERACAO
            // 210220 OPERACAO DESOCNHECIDA
            // 210240 OPERACAO NAO REALIZADA
            $tpEvento =  $request->manifestacao;

            //a ciencia não requer justificativa
            $xJust = $request->justificativa??null;

             //a ciencia em geral será numero inicial de uma sequencia para essa nota e evento
            $nSeqEvento = 1;

            $response = $tools->sefazManifesta($chNFe,$tpEvento,$xJust = '',$nSeqEvento = 1);

            //você pode padronizar os dados de retorno atraves da classe abaixo
            //de forma a facilitar a extração dos dados do XML
            //NOTA: mas lembre-se que esse XML muitas vezes será necessário,
            //quando houver a necessidade de protocolos
            $st = new Standardize($response);

            //nesse caso $std irá conter uma representação em stdClass do XML
            $stdRes = $st->toStd();

            // //nesse caso o $arr irá conter uma representação em array do XML
            // $arr = $st->toArray();
            // //nesse caso o $json irá conter uma representação em JSON do XML
            // $json = $st->toJson();

            // ATUALIZA MANIFESTACAO NA BASE
            $manifest = NFeTerceiroDfe::where('nfechave', $request->nfechave)->first();
            $manifest->indmanifestacao = $stdRes->retEvento->infEvento->tpEvento;
            $manifest->save();

            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    } // FIM MANIFETACAO

    public static function consultaSefaz (Filial $filial){

        $tools = NFePHPRepositoryConfig::instanciaTools($filial);

        //só funciona para o modelo 55
        $tools->model('55');

        //este serviço somente opera em ambiente de produção
        $tools->setEnvironment(1);

        // BUSCA NA BASE DE DADOS A ULTIMA NSU CONSULTADA DA FILIAL
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
                $dfe = NFeTerceiroDistribuicaoDfe::firstOrNew([
                    'nsu' => $numnsu,
                    'nfechave' => $chave
                ]);
                $dfe->nfechave = $chave;
                $dfe->codfilial = $filial->codfilial;
                $dfe->nsu = $numnsu;
                $dfe->schema = $schema;
                $dfe->save();

                // SALVA NA PASTA O ARQUIVO DFE DA CONSULTA
                $pathNFeTerceiro = NFeTerceiroRepositoryPath::pathDFe($filial, $numnsu, true);
                file_put_contents($pathNFeTerceiro, $content);

                // SALVA NA BASE OS DADOS DO XML TODO ajustar essa classe
                if($schema == "resNFe_v1.01.xsd"){
                    static::armazenaDadosConsulta($res, $filial);
                }

            }
            sleep(2);
        }  // FIM DO LOOP
        return true;

    } // FIM CONSULTA SEFAZ

    public static function armazenaDadosEvento ($filial) {

        $qry = NFeTerceiroDistribuicaoDfe::select('*')->where('schema', 'resEvento_v1.01.xsd')->orderBy('nsu', 'DESC')->get();
        $qry = end($qry);

        foreach ($qry as $key => $file) {

            $path = NFeTerceiroRepositoryPath::pathDFe($filial, $file->nsu);

            if(file_exists($path)){
                $xmlData = file_get_contents($path);
                $st = new Standardize();
                $xml = $st->toStd($xmlData);

                $coddistribuicaodfe = NFeTerceiroDistribuicaoDfe::select('coddistribuicaodfe')
                ->where( 'nsu', $file->nsu )->get();

                $resEvento = NFeTerceiroEvento::firstOrNew([
                    'coddistribuicaodfe' => $coddistribuicaodfe[0]->coddistribuicaodfe
                ]);
                $resEvento->coddistribuicaodfe = $coddistribuicaodfe[0]->coddistribuicaodfe;
                $resEvento->nsu = $file->nsu;
                $resEvento->codorgao = $xml->cOrgao;
                $resEvento->cnpj = $xml->CNPJ;
                $resEvento->nfechave = $xml->chNFe;
                $resEvento->dhevento = Carbon::parse($xml->dhEvento);
                $resEvento->tpevento = $xml->tpEvento;
                $resEvento->nseqevento = $xml->nSeqEvento;
                $resEvento->evento = $xml->xEvento;
                $resEvento->dhrecebimento = Carbon::parse($xml->dhRecbto);
                $resEvento->protocolo = $xml->nProt;
                // dd($resEvento);
                $resEvento->save();
            }
        }

        return true;

    } // FIM DO ARMAZENA EVENTO

    public static function downloadNFeTerceiro (Filial $filial, $chave){

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
                $chave = $res->protNFe->infProt->chNFe;
            }
            if(!empty($chave)){
                // SALVA NA BASE AS INFORMAÇOES DA NOTA
                $pathNFeTerceiro = NFeTerceiroRepositoryPath::pathNFeTerceiro($filial, $chave, true);
                // SALVA NA PASTA O ARQUIVO DFE DA CONSULTA
                file_put_contents($pathNFeTerceiro, $xml);
                static::carregarXml($filial, $chave);
                return true;
            }
        } catch (\Exception $e) {
            echo str_replace("\n", "<br/>", $e->getMessage());
            return $e;
        }
    } // FIM DO DOWNLOAD NFeTerceiro

    public static function carregarXml(Filial $filial, $chave){
        // TRAZ O CAMNHO DO XML SE EXISTIR
        $path = NFeTerceiroRepositoryPath::pathNFeTerceiro($filial, $chave, true);
        // VERIFICA SE O AQRQUIVO EXISTE
        if (!file_exists($path)) {
            return 'Nota Fiscal Terceiro não encontrada.';
        }else{
            // BUSCA XML NA PASTA  SE JA ESTIVER BAIXADO e CONVERTE EM UM OBJETO
            $xml = file_get_contents($path);
            $st = new Standardize();
            $res = $st->toStd($xml);

            static::armazenaDadosNFe($filial, $res);

            return true;
        }
    }

    // public static function armazenaDadosDFe ($xml, $filial) {
    public static function armazenaDadosConsulta (Filial $filial) {

        // // BUSCA NA BASE DE DADOS O codpessoa, TODO 'SE NAO TIVER CRIAR UM CADASTRO'
        // $codpessoa = Pessoa::select('codpessoa')
        // ->where( 'ie', $xml->IE )->orWhere( 'cnpj', $xml->CNPJ )->get();
        //
        // $NFe = NFeTerceiro::firstOrNew([
        //     'nfechave' => $xml->chNFe
        // ]);
        // $NFe->codpessoa = $codpessoa[0]->codpessoa??null;
        // $NFe->codfilial = $filial->codfilial;
        // $NFe->emitente = $xml->xNome;
        // $NFe->cnpj = $xml->CNPJ;
        // $NFe->ie = $xml->IE;
        // $NFe->nfechave = $xml->chNFe;
        // $NFe->emissao = Carbon::parse($xml->dhEmi);
        // $NFe->valortotal = $xml->vNF;
        // $NFe->recebimento = Carbon::parse($xml->dhRecbto);
        // $NFe->digito = $xml->digVal;
        // $NFe->protocolo = $xml->nProt;
        // $NFe->tipo = $xml->tpNF;
        // $NFe->csitnfe = $xml->cSitNFe;
        // // dd($NFe);
        // $NFe->save();

        $qry = NFeTerceiroDistribuicaoDfe::select('*')->where('nsu', '000000000021252')->get();
        $qry = end($qry);
        // $qry = NFeTerceiroDistribuicaoDfe::select('*')->get();
        // $qry = end($qry);

        foreach ($qry as $key => $file) {

            $path = NFeTerceiroRepositoryPath::pathDFe($filial, $file->nsu);

            if(file_exists($path)){
                $xmlData = file_get_contents($path);
                $st = new Standardize();
                $xml = $st->toStd($xmlData);


                if($file->schema == 'resNFe_v1.01.xsd'){

                    // BUSCA NA BASE DE DADOS O codpessoa, TODO 'SE NAO TIVER CRIAR UM CADASTRO'
                    $codpessoa = Pessoa::select('codpessoa')
                    ->where( 'ie', $xml->IE )->orWhere( 'cnpj', $xml->CNPJ )->get();

                    $NFe = NFeTerceiro::firstOrNew([
                        'nfechave' => $xml->chNFe,
                        'protocolo' => $xml->nProt
                    ]);
                    $NFe->codfilial = $filial->codfilial;
                    $NFe->codpessoa = $codpessoa[0]->codpessoa??null;
                    $NFe->nfechave = $xml->chNFe;
                    $NFe->cnpj = $xml->CNPJ;
                    $NFe->emitente = $xml->xNome;
                    $NFe->ie = $xml->IE;
                    $NFe->emissao = Carbon::parse($xml->dhEmi);
                    $NFe->valortotal = $xml->vNF;
                    $NFe->recebimento = Carbon::parse($xml->dhRecbto);
                    $NFe->digito = $xml->digVal;
                    $NFe->protocolo = $xml->nProt;
                    $NFe->tipo = $xml->tpNF;
                    $NFe->indmanifestacao = $xml->cSitNFe;
                    // dd($NFe);
                    $NFe->save();

                }

                // SE HOUVER  A NOTA COMPLETA JA ARMAZENA OS DADOS || $file->schema =='procNFe_v3.10.xsd'
                if($file->schema == 'procNFe_v4.00.xsd' ){
                    static::armazenaDadosNFe($filial, $xml);
                }

            }
        }

        return true;

    } // FIM DO ARMAZENA CONSULTA

    public static function armazenaDadosNFe ($filial, $res){

        // BUSCA NA BASE DE DADOS O codpessoa
        $codpessoa = Pessoa::select('codpessoa')
        ->where( 'ie', $res->NFe->infNFe->emit->IE )->orWhere( 'cnpj', $res->NFe->infNFe->emit->CNPJ )->get();

        // SE O FRONECEDOR NAO TIVER CADASTRO CRIAR UM
        if ($codpessoa[0]->codpessoa == null){

            $pessoa = Pessoa::firstOrNew([
                'cnpj' => $res->NFe->infNFe->emit->CNPJ,
                'ie' => $res->NFe->infNFe->emit->IE ]);

            $pessoa->pessoa = $res->NFe->infNFe->emit->xNome;
            $pessoa->fantasia = $res->NFe->infNFe->emit->xFant;
            $pessoa->inativo = null;
            $pessoa->cliente = false;
            $pessoa->fornecedor = true;
            $pessoa->fisica = false;
            $pessoa->codsexo = null;
            $pessoa->cnpj = $res->NFe->infNFe->emit->CNPJ;
            $pessoa->ie = $res->NFe->infNFe->emit->IE;
            $pessoa->consumidor = false;
            $pessoa->contato = null;
            $pessoa->codestadocivil = null;
            $pessoa->conjuge = null;
            $pessoa->endereco = $res->NFe->infNFe->emit->enderEmit->xLgr;
            $pessoa->numero = $res->NFe->infNFe->emit->enderEmit->nro;
            $pessoa->complemento = null;
            $pessoa->codcidade = $res->NFe->infNFe->emit->enderEmit->cMun;
            $pessoa->bairro = $res->NFe->infNFe->emit->enderEmit->xBairro;
            $pessoa->cep = $res->NFe->infNFe->emit->enderEmit->CEP1;
            $pessoa->enderecocobranca = null;
            $pessoa->numerocobranca = null;
            $pessoa->complementocobranca = null;
            $pessoa->codcidadecobranca = null;
            $pessoa->bairrocobranca = null;
            $pessoa->cepcobranca = null;
            $pessoa->telefone1 = $res->NFe->infNFe->emit->enderEmit->fone;
            $pessoa->telefone2 = null;
            $pessoa->telefone3 = null;
            $pessoa->email = null;
            $pessoa->emailnfe = null;
            $pessoa->emailcobranca = null;
            $pessoa->codformapagamento = null;
            $pessoa->credito = null;
            $pessoa->creditobloqueado = null;
            $pessoa->observacoes = null;
            $pessoa->mensagemvenda = null;
            $pessoa->vendedor = false;
            $pessoa->rg = null;
            $pessoa->desconto = null;
            $pessoa->notafiscal = null;
            $pessoa->toleranciaatraso = null;
            $pessoa->codgrupocliente = null;
            // dd($pessoa);
            $pessoa->save();

            // BUSCA NA BASE DE DADOS O codpessoa
            $codpessoa = Pessoa::select('codpessoa')
            ->where( 'ie', $res->NFe->infNFe->emit->IE )->orWhere( 'cnpj', $res->NFe->infNFe->emit->CNPJ )->get();
        } // FIM DO CRIA CADASTRO


        // BUSCA NA BASE DE DADOS O coddistribuicaodfe DA DFE CONSULTADA
        $coddistribuicaodfe = NFeTerceiroDistribuicaoDfe::select('coddistribuicaodfe')
        ->where([ ['nfechave', $res->protNFe->infProt->chNFe],
        ['schema', 'like', 'resNFe' . '%'] ])->get();

        // BUSCA NA BASE DE DADOS O codestoquelocal
        $codestoquelocal = EstoqueLocal::select('codestoquelocal')->where('codfilial', $filial->codfilial)->get();

        DB::beginTransaction();

        // INSERE NA tblnotafiscal
        $NF = NotaFiscal::firstOrNew([
            'nfechave' => $res->protNFe->infProt->chNFe,
            'numero' => $res->NFe->infNFe->ide->nNF
        ]);
        $NF->codnaturezaoperacao = 1; // rever este campo
        $NF->emitida = false; // rever este campo
        $NF->nfechave = $res->protNFe->infProt->chNFe;
        $NF->nfeimpressa = 0; //$res->NFe->infNFe->ide->tpImp; rever este campo
        $NF->serie = $res->NFe->infNFe->ide->serie;
        $NF->numero = $res->NFe->infNFe->ide->nNF;
        $NF->emissao = Carbon::parse($res->NFe->infNFe->ide->dhEmi);
        $NF->saida = Carbon::now(); // rever este campo
        $NF->codfilial = $filial->codfilial;
        $NF->codpessoa = $codpessoa[0]->codpessoa;
        $NF->observacoes = $res->NFe->infNFe->infAdic->infCpl??null;
        $NF->volumes = $res->NFe->infNFe->transp->vol->qVol??0;
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
        // dd($NF);
        $NF->save();// FIM DO CRIA NOTAFISCAL

        // BUSCA NA tblnotafiscal O codnotafiscal
        $codnotafiscal = NotaFiscal::select('codnotafiscal')->where('nfechave', $res->protNFe->infProt->chNFe)->get();

        // SALVA NA tblnotafiscalterceiro OS DADOS DA NOTA
        $NFeTerceiro = NFeTerceiro::firstOrNew([
        'nfechave' => $res->protNFe->infProt->chNFe,
        'coddistribuicaodfe' => $coddistribuicaodfe[0]->coddistribuicaodfe
        ]);
        $NFeTerceiro->coddistribuicaodfe = $coddistribuicaodfe[0]->coddistribuicaodfe;
        $NFeTerceiro->codnotafiscal = $codnotafiscal[0]->codnotafiscal;
        $NFeTerceiro->codnegocio = null; // rever este campo, como gerar um negocio??
        $NFeTerceiro->codfilial = $filial->codfilial;
        $NFeTerceiro->codoperacao = $res->NFe->infNFe->ide->tpNF;
        $NFeTerceiro->codnaturezaoperacao = null; //usuario deve informar a natureza de operacao!
        $NFeTerceiro->codpessoa = $codpessoa[0]->codpessoa;
        $NFeTerceiro->natop = $res->NFe->infNFe->ide->natOp??null;
        $NFeTerceiro->emitente = $res->NFe->infNFe->emit->xNome;
        $NFeTerceiro->cnpj = $res->NFe->infNFe->emit->CNPJ;
        $NFeTerceiro->ie = $res->NFe->infNFe->emit->IE;
        $NFeTerceiro->emissao = Carbon::parse($res->NFe->infNFe->ide->dhEmi);
        $NFeTerceiro->ignorada = false; // onde esta buscar essa informacao??
        $NFeTerceiro->indsituacao = $res->NFe->infNFe->ide->cSitNFe??null;
        $NFeTerceiro->justificativa = $res->protNFe->infProt->xMotivo;
        $NFeTerceiro->indmanifestacao = null; // perguntar para o usuario a manifestacao??
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
        $NFeTerceiro->download = true;
        // dd($NFeTerceiro);
        $NFeTerceiro->save(); // FIM DO CRIA NOTA FISCAL TERCEIRO

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

        // ARMAZENA OS DADOS DOS ITENS DA NOTA
        $loop = 0;
        foreach ($res->NFe->infNFe->det as $key => $item) {

            $loop++;
            // echo "<script>console.log( 'Debug Objects:".$loop." " . $NFeItem . "' );</script>";
            // echo "<script>console.log( 'Debug Objects:".$loop." " . $res->protNFe->infProt->chNFe . "' );</script>";
            // echo "<script>console.log( 'Debug Objects:".$loop." " . $res->NFe->infNFe->det[1] . "' );</script>";

            $NFeItem = NFeTerceiroItem::firstOrNew([
            'referencia' => $item->prod->cProd,
            'codnotafiscalterceirogrupo' => $codGrupo[0]->codnotafiscalterceirogrupo
            ]);
            $NFeItem->codnotafiscalterceirogrupo = $codGrupo[0]->codnotafiscalterceirogrupo;
            $NFeItem->numero = $item->attributes->nItem;
            $NFeItem->referencia = $item->prod->cProd;
            $NFeItem->produto = $item->prod->xProd;
            $NFeItem->ncm = $item->prod->NCM;
            $NFeItem->cfop = $item->prod->CFOP;
            $NFeItem->barras = $item->prod->cEAN??null;

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

            $NFeItem->quantidadetributavel = $item->prod->qTrib??null;
            $NFeItem->valorunitariotributavel = $item->prod->vUnTrib;
            $NFeItem->unidademedida = $item->prod->uCom;
            $NFeItem->quantidade = $item->prod->qCom;
            $NFeItem->valorunitario = $item->prod->vUnCom;
            $NFeItem->valorproduto = $item->prod->vProd;
            $NFeItem->valorfrete = $res->NFe->infNFe->total->ICMSTot->vFrete;
            $NFeItem->valorseguro = $res->NFe->infNFe->total->ICMSTot->vSeg;
            $NFeItem->valordesconto = $res->NFe->infNFe->total->ICMSTot->vDesc;
            $NFeItem->valoroutras = $res->NFe->infNFe->total->ICMSTot->vOutro;
            $NFeItem->valortotal = null; // rever este campo
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

            // SALVA OS DADOS NA tblnotafiscalterceiroprodutobarra
            $produtobarra = NFeTerceiroProdutoBarra::firstOrNew([
            'codnotafiscalterceirogrupo' => $codGrupo[0]->codnotafiscalterceirogrupo
            ]);
            $produtobarra->codnotafiscalterceirogrupo = $codGrupo[0]->codnotafiscalterceirogrupo;;
            $produtobarra->codprodutobarra = null;
            $produtobarra->margem = null; // rever este campo
            $produtobarra->complemento = null; // rever este campo
            $produtobarra->quantidade = $item->prod->qTrib;
            $produtobarra->valorproduto = $item->prod->vProd;
            // dd($produtobarra);
            $produtobarra->save();
        } // FIM DO ARMAZENA ITENS

        //SE HOUVER DUPLICATA ARMAZENA OS DADOS
        if (!$res->NFe->infNFe->cobr == null){

            if(count($res->NFe->infNFe->cobr->dup) > 1){
                foreach ($res->NFe->infNFe->cobr->dup as $key => $duplicata) {
                    $NFeDuplicata = NFeTerceiroDuplicata::firstOrNew([
                        'codnotafiscalterceiro' => $codnotafiscalterceiro[0]->codnotafiscalterceiro,
                        'duplicata' => $duplicata->nDup??$duplicata
                    ]);
                    $NFeDuplicata->codnotafiscalterceiro = $codnotafiscalterceiro[0]->codnotafiscalterceiro;
                    $NFeDuplicata->codtitulo = null; // rever este campo
                    $NFeDuplicata->duplicata = $duplicata->nDup;
                    $NFeDuplicata->vencimento = Carbon::parse($duplicata->dVenc);
                    $NFeDuplicata->valor = $duplicata->vDup;
                    $NFeDuplicata->ndup = $duplicata->nDup??1;
                    $NFeDuplicata->dvenc = Carbon::parse($duplicata->dVenc);
                    $NFeDuplicata->vdup = $duplicata->vDup;
                    $NFeDuplicata->save();
                }
            }
            else{
                $NFeDuplicata = NFeTerceiroDuplicata::firstOrNew([
                    'codnotafiscalterceiro' => $codnotafiscalterceiro[0]->codnotafiscalterceiro,
                    'duplicata' => $res->NFe->infNFe->cobr->dup->nDup
                ]);
                $NFeDuplicata->codnotafiscalterceiro = $codnotafiscalterceiro[0]->codnotafiscalterceiro;
                $NFeDuplicata->codtitulo = null; // rever este campo
                $NFeDuplicata->duplicata = $res->NFe->infNFe->cobr->dup->nDup;
                $NFeDuplicata->vencimento = Carbon::parse($res->NFe->infNFe->cobr->dup->dVenc);
                $NFeDuplicata->valor = $res->NFe->infNFe->cobr->dup->vDup;
                $NFeDuplicata->ndup = $res->NFe->infNFe->cobr->dup->nDup;
                $NFeDuplicata->dvenc = Carbon::parse($res->NFe->infNFe->cobr->dup->dVenc);
                $NFeDuplicata->vdup = $res->NFe->infNFe->cobr->dup->vDup;
                $NFeDuplicata->save();
            }
        }
        DB::commit();
    } // FIM DO armazenaDadosNFe

    // TRAZ DA BASE TODAS AS DFEs
    public static function listaDFe ($request) {

        // PREPARA OS PARAMETROS PARA A CONDITIONAL QUERY
        $filial = $request->filial;
        $codpessoa = $request->pessoa;
        $chave = $request->chave;
        $datainicial = $request->datainicial;
        $datafinal = $request->datafinal;
        $manifestacao = $request->manifestacao;
        $situacao = $request->situacao;

        // EXECUTA A CONDITIONAL QUERY CONFORME FILTRO REQUISITADO
        $qry = NFeTerceiroDfe::when($filial, function($query, $filial){
            $query->where('tblnotafiscalterceiro.codfilial', $filial);
        })
        ->when($chave, function($query, $chave){
            $query->where('nfechave', $chave);
        })
        ->when($manifestacao, function($query, $manifestacao){
            $query->where('indmanifestacao', $manifestacao);
        })
        ->when($situacao, function($query, $situacao){
            $query->where('csitnfe', $situacao);
        })
        ->when($datainicial, function($query, $datainicial){
            $query->where('emissao', '>=', $datainicial);
        })
        ->when($datafinal, function($query, $datafinal){
            $query->where('emissao', '<=', $datafinal);
        })
        ->when($codpessoa, function($query, $codpessoa){
            $query->where('tblnotafiscalterceiro.codpessoa', $codpessoa );
        })
        ->join('tblfilial', 'tblnotafiscalterceiro.codfilial', '=', 'tblfilial.codfilial')
        ->select('tblnotafiscalterceiro.*', 'tblfilial.filial')
        ->orderBy('emissao', 'DESC')
        ->paginate(100);

        return $qry;
    }

    // BUSCA NA BASE A NFETERCEIRO REQUISITADA
    public static function buscaNFeTerceiro ($chave) {
        $qry = NFeTerceiro::select('*')
        ->where('nfechave', $chave)
        ->join('tblfilial', 'tblnotafiscalterceiro.codfilial', '=', 'tblfilial.codfilial')
        ->select('tblnotafiscalterceiro.*', 'tblfilial.filial')->get();
        return ($qry);
    }

    // BUSCA NA BASE DE DADOS A ULTIMA NSU CONSULTADA DA FILIAL
    public static function ultimaNSU ($filial) {
        $ultimoNsu = NFeTerceiroDistribuicaoDfe::select('nsu')->where('codfilial', $filial->codfilial)->get();
        $ultimoNsu = end($ultimoNsu);
        $ultimoNsu = end($ultimoNsu);
        return $ultimoNsu;
    }

    public static function listaItem ($codnotafiscalterceiro) {
        // BUSCA NA tblnotafiscalterceirogrupo o codnotafiscalterceirogrupo
        $codGrupo = NFeTerceiroGrupo::select('codnotafiscalterceirogrupo')->where('codnotafiscalterceiro', $codnotafiscalterceiro)->get();

        // BUSCA NA tblnotafiscalterceiroitem TODOS OS ITENS VINCULADOS A NOTA
        $itens = NFeTerceiroItem::select('*')->where('codnotafiscalterceirogrupo', $codGrupo[0]->codnotafiscalterceirogrupo)->get();

        // dd($itens);
        return ($itens);

    }

    public static function atualizaItem ($request) {
        dd('aqui');
        return;

    }

    public static function atualizaNFe ($request) {
        dd('aqui');
        return;

    }

} // FIM DA CLASSE
