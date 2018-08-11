<?php

namespace Mg\NFeTerceiro;
use Mg\MgRepository;

use Mg\Filial\Filial;
use Mg\Pessoa\Pessoa;
use Mg\NotaFiscal\NotaFiscal;
use Mg\Estoque\EstoqueLocal;

use Mg\NFePHP\NFePHPRepositoryConfig;
use Mg\NFePHP\NFePHPRepositoryConsultaCad;
use Mg\NFePHP\NFePHPRepositoryConsultaSefaz;
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

        // BUSCA NA BASE DE DADOS A ULTIMA NSU CONSULTADA DA FILIAL
        $ultimoNsu = NFeTerceiroDistribuicaoDfe::select('nsu')->where('codfilial', $filial->codfilial)->get();
        $ultimoNsu = end($ultimoNsu);
        $ultimoNsu = end($ultimoNsu);

        $resConsulta = NFePHPRepositoryConsultaSefaz::consultaSefaz($filial, $ultimoNsu);

        //essas tags irão conter os documentos zipados
        $docs = $resConsulta->getElementsByTagName('docZip');
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
                static::armazenaResNFe($filial, $res);
            }

        }

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
    public static function armazenaDadosConsulta ($res, Filial $filial) {
        // $filial = Filial::findOrFail($filial);

        // $qry = NFeTerceiroDistribuicaoDfe::where('nsu', '000000000023538')->get();
        $qry = NFeTerceiroDistribuicaoDfe::select('*')->get();
        $qry = end($qry);

        foreach ($qry as $key => $file) {

            $path = NFeTerceiroRepositoryPath::pathDFe($filial, $file->nsu);

            if(file_exists($path)){
                $xmlData = file_get_contents($path);
                $st = new Standardize();
                $xml = $st->toStd($xmlData);


                if($file->schema == 'resNFe_v1.01.xsd'){
                    // echo "<script>console.log( 'Debug Objects:" . $xml->chNFe . "' );</script>";
                    static::armazenaResNFe($filial, $xml);
                }
                // SE HOUVER  A NOTA COMPLETA JA ARMAZENA OS DADOS
                if($file->schema == 'procNFe_v4.00.xsd'){
                    // echo "<script>console.log( 'Debug Objects:" . $xml->chNFe . "' );</script>";
                    static::armazenaDadosNFe($filial, $xml);
                }
                if($file->schema == 'procNFe_v3.10.xsd'){
                    // echo "<script>console.log( 'Debug Objects:" . $xml->chNFe . "' );</script>";
                    static::armazenaDadosNFe($filial, $xml);
                }

            }
        }

        return true;

    } // FIM DO armazenaDadosConsulta

    public static function armazenaResNFe($filial, $xml){

        $pessoa = static::novaPessoa($xml, $filial);
        // dd($pessoa);

        $NFe = NFeTerceiro::firstOrNew([
            'nfechave' => $xml->chNFe,
            'protocolo' => $xml->nProt
        ]);
        $NFe->codfilial = $filial->codfilial;
        $NFe->codpessoa = $pessoa->codpessoa??null;
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

    // SE O FRONECEDOR NAO TIVER CADASTRO CRIA UM
    public static function novaPessoa($xml, $filial){

      // BUSCA NA BASE DE DADOS O codpessoa
     return $pessoa = Pessoa::where([['ie', $xml->IE],['cnpj', $xml->CNPJ]])->first();

      if($pessoa == null){

          $cnpj = null;
          $cpf = null;
          $uf = null;
          $iest = null;
          if (strlen($xml->CNPJ) == 14){
            $cnpj = $xml->CNPJ;
          }
          if (strlen($xml->CNPJ) == 11){
            $cpf = $xml->CNPJ;
          }
          $uf = substr($xml->chNFe, 0,2);
          $iest = $xml->IE;

          $novaPessoa = NFePHPRepositoryConsultaCad::consultaCadastro($uf, $cnpj, $iest, $cpf, $filial);
      }

      // $pessoa = Pessoa::firstOrNew([
      //   'cnpj' => $res->NFe->infNFe->emit->CNPJ,
      //   'ie' => $res->NFe->infNFe->emit->IE
      // ]);
      // $pessoa->pessoa = $res->NFe->infNFe->emit->xNome;
      // $pessoa->fantasia = $res->NFe->infNFe->emit->xFant;
      // $pessoa->inativo = null;
      // $pessoa->cliente = false;
      // $pessoa->fornecedor = true;
      // $pessoa->fisica = false;
      // $pessoa->codsexo = null;
      // $pessoa->cnpj = $res->NFe->infNFe->emit->CNPJ;
      // $pessoa->ie = $res->NFe->infNFe->emit->IE;
      // $pessoa->consumidor = false;
      // $pessoa->contato = null;
      // $pessoa->codestadocivil = null;
      // $pessoa->conjuge = null;
      // $pessoa->endereco = $res->NFe->infNFe->emit->enderEmit->xLgr;
      // $pessoa->numero = $res->NFe->infNFe->emit->enderEmit->nro;
      // $pessoa->complemento = null;
      // $pessoa->codcidade = $res->NFe->infNFe->emit->enderEmit->cMun;
      // $pessoa->bairro = $res->NFe->infNFe->emit->enderEmit->xBairro;
      // $pessoa->cep = $res->NFe->infNFe->emit->enderEmit->CEP1;
      // $pessoa->enderecocobranca = null;
      // $pessoa->numerocobranca = null;
      // $pessoa->complementocobranca = null;
      // $pessoa->codcidadecobranca = null;
      // $pessoa->bairrocobranca = null;
      // $pessoa->cepcobranca = null;
      // $pessoa->telefone1 = $res->NFe->infNFe->emit->enderEmit->fone;
      // $pessoa->telefone2 = null;
      // $pessoa->telefone3 = null;
      // $pessoa->email = null;
      // $pessoa->emailnfe = null;
      // $pessoa->emailcobranca = null;
      // $pessoa->codformapagamento = null;
      // $pessoa->credito = null;
      // $pessoa->creditobloqueado = null;
      // $pessoa->observacoes = null;
      // $pessoa->mensagemvenda = null;
      // $pessoa->vendedor = false;
      // $pessoa->rg = null;
      // $pessoa->desconto = null;
      // $pessoa->notafiscal = null;
      // $pessoa->toleranciaatraso = null;
      // $pessoa->codgrupocliente = null;
      // // dd($pessoa);
      // $pessoa->save();

    }// FIM DO novaPessoa

    public static function armazenaDadosNFe ($filial, $res){

      // BUSCA NA BASE DE DADOS O codpessoa
      $codpessoa = Pessoa::where([['ie', $res->NFe->infNFe->emit->IE],['cnpj', $res->NFe->infNFe->emit->CNPJ]])->first();

      // BUSCA NA BASE DE DADOS O coddistribuicaodfe DA DFE CONSULTADA
      $coddistribuicaodfe = NFeTerceiroDistribuicaoDfe::
      where([['nfechave', $res->protNFe->infProt->chNFe],['schema', 'like', 'procNFe' . '%']])
      ->orWhere([['nfechave', $res->protNFe->infProt->chNFe],['schema', 'like', 'resNFe' . '%']])->first();

      // BUSCA NA BASE DE DADOS O codestoquelocal
      $codestoquelocal = EstoqueLocal::where('codfilial', $filial->codfilial)->first();

      DB::beginTransaction();
      // echo "<script>console.log( 'Debug Objects:" . $res->protNFe->infProt->chNFe . "' );</script>";

      // // INSERE NA tblnotafiscal
      // $NF = NotaFiscal::firstOrNew([
      //     'nfechave' => $res->protNFe->infProt->chNFe,
      //     'numero' => $res->NFe->infNFe->ide->nNF
      // ]);
      // $NF->codnaturezaoperacao = 1; // rever este campo
      // $NF->emitida = false; // rever este campo
      // $NF->nfechave = $res->protNFe->infProt->chNFe;
      // $NF->nfeimpressa = 0; //$res->NFe->infNFe->ide->tpImp; rever este campo
      // $NF->serie = $res->NFe->infNFe->ide->serie;
      // $NF->numero = $res->NFe->infNFe->ide->nNF;
      // $NF->emissao = Carbon::parse($res->NFe->infNFe->ide->dhEmi);
      // $NF->saida = Carbon::now(); // rever este campo
      // $NF->codfilial = $filial->codfilial;
      // $NF->codpessoa = $codpessoa->codpessoa;
      //
      // // houve uma nota em que as observaçoes, o comprimento da string é maior que 1500, na tabaela o limite
      // // é 1500 esta validacao resolve este problema, mas rever este trecho
      // //35180659400853000759550010000028241524108507
      // $NFeObservacoes = null;
      // if(isset($res->NFe->infNFe->infAdic->infCpl) && strlen($res->NFe->infNFe->infAdic->infCpl) > 1500){
      //     $NFeObservacoes = substr($res->NFe->infNFe->infAdic->infCpl,0,1500);
      // }else{
      //     if(isset($res->NFe->infNFe->infAdic->infCpl)){
      //         $NFeObservacoes = $res->NFe->infNFe->infAdic->infCpl;
      //     }
      // }
      // $NF->observacoes = $NFeObservacoes;
      // $NF->volumes = $res->NFe->infNFe->transp->vol->qVol??0;
      //
      // // houve uma nota em que o codoperacao é 0'saida', mas atualmente na base é 1'entrada'  ou 2'saida'
      // //rever este trecho
      // // 51180605372531000129550010010254021111025063
      // $NFeCodOperacao = $res->NFe->infNFe->ide->tpNF;
      // if($NFeCodOperacao == 0){
      //     $NFeCodOperacao = 2;
      // }
      // $NF->codoperacao = $NFeCodOperacao;
      // $NF->nfereciboenvio = Carbon::parse($res->protNFe->infProt->dhRecbto);
      // $NF->nfedataenvio = Carbon::parse($res->NFe->infNFe->ide->dhEmi);
      // $NF->nfeautorizacao = $res->protNFe->infProt->xMotivo;
      // $NF->nfedataautorizacao = Carbon::parse($res->protNFe->infProt->dhRecbto);
      // $NF->valorfrete = $res->NFe->infNFe->total->ICMSTot->vFrete;
      // $NF->valorseguro = $res->NFe->infNFe->total->ICMSTot->vSeg;
      // $NF->valordesconto = $res->NFe->infNFe->total->ICMSTot->vDesc;
      // $NF->valoroutras = $res->NFe->infNFe->total->ICMSTot->vOutro;
      // $NF->nfecancelamento = null;
      // $NF->nfedatacancelamento = null;
      // $NF->nfeinutilizacao = null;
      // $NF->nfedatainutilizacao = null;
      // $NF->justificativa = $res->NFe->infNFe->ide->xJust??null;
      // $NF->modelo = $res->NFe->infNFe->ide->mod;
      // $NF->valorprodutos = $res->NFe->infNFe->total->ICMSTot->vProd;
      // $NF->valortotal = $res->NFe->infNFe->total->ICMSTot->vNF;
      // $NF->icmsbase = $res->NFe->infNFe->total->ICMSTot->vBC;
      // $NF->icmsvalor = $res->NFe->infNFe->total->ICMSTot->vICMS;
      // $NF->icmsstbase = $res->NFe->infNFe->total->ICMSTot->vBCST;
      // $NF->icmsstvalor = $res->NFe->infNFe->total->ICMSTot->vST;
      // $NF->ipibase = $res->NFe->infNFe->total->ICMSTot->vBC;
      // $NF->ipivalor = $res->NFe->infNFe->total->ICMSTot->vIPI;
      // $NF->frete = $res->NFe->infNFe->transp->modFrete;
      // $NF->tpemis = $res->NFe->infNFe->ide->tpEmis;
      // $NF->codestoquelocal = $codestoquelocal->codestoquelocal;
      // // dd($NF);
      // $NF->save();// FIM DO CRIA NOTAFISCAL
      //
      // // BUSCA NA tblnotafiscal O codnotafiscal
      // $codnotafiscal = NotaFiscal::select('codnotafiscal')->where('nfechave', $res->protNFe->infProt->chNFe)->get();


      // SALVA NA tblnotafiscalterceiro OS DADOS DA NOTA
      $NFeTerceiro = NFeTerceiro::firstOrNew([
      'nfechave' => $res->protNFe->infProt->chNFe,
      'numero' => $res->NFe->infNFe->ide->nNF
      ]);
      $NFeTerceiro->coddistribuicaodfe = $coddistribuicaodfe->coddistribuicaodfe;
      $NFeTerceiro->codnotafiscal = $codnotafiscal[0]->codnotafiscal??null;
      $NFeTerceiro->codnegocio = null; // rever este campo, como gerar um negocio??
      $NFeTerceiro->codfilial = $filial->codfilial;
      $NFeTerceiro->codoperacao = $res->NFe->infNFe->ide->tpNF;
      $NFeTerceiro->codnaturezaoperacao = null;
      $NFeTerceiro->codpessoa = $codpessoa->codpessoa??null;
      $NFeTerceiro->natop = $res->NFe->infNFe->ide->natOp??null;
      $NFeTerceiro->emitente = $res->NFe->infNFe->emit->xNome;
      $NFeTerceiro->cnpj = $res->NFe->infNFe->emit->CNPJ;
      $NFeTerceiro->ie = $res->NFe->infNFe->emit->IE;
      $NFeTerceiro->emissao = Carbon::parse($res->NFe->infNFe->ide->dhEmi);
      $NFeTerceiro->ignorada = false; // rever este campo
      $NFeTerceiro->indsituacao = $res->cSitNFe??$res->protNFe->infProt->cStat??3;
      $NFeTerceiro->justificativa = $res->protNFe->infProt->xMotivo;
      $NFeTerceiro->indmanifestacao = null;
      $NFeTerceiro->nfechave = $res->protNFe->infProt->chNFe;
      $NFeTerceiro->modelo = $res->NFe->infNFe->ide->mod;
      $NFeTerceiro->serie = $res->NFe->infNFe->ide->serie;
      $NFeTerceiro->numero = $res->NFe->infNFe->ide->nNF;
      $NFeTerceiro->entrada = null;
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
      $codnotafiscalterceiro = NFeTerceiro::where('coddistribuicaodfe', $coddistribuicaodfe->coddistribuicaodfe)->first();

      //SALVA NA TABELA GRUPO
      $grupo = NFeTerceiroGrupo::firstOrNew([
      'codnotafiscalterceiro' => $codnotafiscalterceiro->codnotafiscalterceiro
      ]);
      $grupo->codnotafiscalterceiro = $codnotafiscalterceiro->codnotafiscalterceiro;
      $grupo->save();

      // BUSCA NA tblnotafiscalterceirogrupo o codnotafiscalterceirogrupo
      $codGrupo = NFeTerceiroGrupo::where('codnotafiscalterceiro', $codnotafiscalterceiro->codnotafiscalterceiro)->first();

      // ARMAZENA OS DADOS DOS ITENS DA NOTA
      // $loop = 0;
      foreach ($res->NFe->infNFe->det as $key => $item) {
          // $loop++;
          // echo "<script>console.log( 'Debug Objects:".$loop." " . $res->protNFe->infProt->chNFe . "' );</script>";
          // echo "<script>console.log( 'Debug Objects:".$loop." " . $res->NFe->infNFe->det[1] . "' );</script>";

          $NFeItem = NFeTerceiroItem::firstOrNew([
          'referencia' => $item->prod->cProd??$res->NFe->infNFe->det->prod->cProd,
          'codnotafiscalterceirogrupo' => $codGrupo->codnotafiscalterceirogrupo
          ]);
          $NFeItem->codnotafiscalterceirogrupo = $codGrupo->codnotafiscalterceirogrupo;
          $NFeItem->numero = $item->attributes->nItem??$res->NFe->infNFe->det->attributes->nItem;
          $NFeItem->referencia = $item->prod->cProd??$res->NFe->infNFe->det->prod->cProd;
          $NFeItem->produto = $item->prod->xProd??$res->NFe->infNFe->det->prod->xProd;
          $NFeItem->ncm = $item->prod->NCM??$res->NFe->infNFe->det->prod->NCM;
          $NFeItem->cfop = $item->prod->CFOP??$res->NFe->infNFe->det->prod->CFOP;

          // VERIFICA SE EXISTE UM CODIGO DE BARRAS
          $barras = null;
          if (isset($item->prod->cEAN) && is_string($item->prod->cEAN)){
              $barras = $item->prod->cEAN;
          }else{
              if(isset($res->NFe->infNFe->det->prod->cEAN) && is_string($res->NFe->infNFe->det->prod->cEAN)){
                  $barras = $res->NFe->infNFe->det->prod->cEAN;
              }
          }
          $NFeItem->barras = $barras;

          // VERIFICA SE EXISTE UM CODIGO DE BARRAS TRIBUTAVEL
          $barrasTrib = null;
          if (isset($item->prod->cEANTrib) && is_string($item->prod->cEANTrib)){
              $barrasTrib = $item->prod->cEANTrib;
          }else{
              if(isset($res->NFe->infNFe->det->prod->cEANTrib) && is_string($res->NFe->infNFe->det->prod->cEANTrib)){
                  $barrasTrib = $res->NFe->infNFe->det->prod->cEANTrib;
              }
          }
          $NFeItem->barrastributavel =  $barrasTrib;

          $NFeItem->quantidadetributavel = $item->prod->qTrib??$res->NFe->infNFe->det->prod->qTrib??null;
          $NFeItem->valorunitariotributavel = $item->prod->vUnTrib??$res->NFe->infNFe->det->prod->vUnTrib;
          $NFeItem->unidademedida = $item->prod->uCom??$res->NFe->infNFe->det->prod->uCom;
          $NFeItem->quantidade = $item->prod->qCom??$res->NFe->infNFe->det->prod->qCom;
          $NFeItem->valorunitario = $item->prod->vUnCom??$res->NFe->infNFe->det->prod->vUnCom;
          $NFeItem->valorproduto = $item->prod->vProd??$res->NFe->infNFe->det->prod->vProd;
          $NFeItem->valorfrete = $res->NFe->infNFe->total->ICMSTot->vFrete;
          $NFeItem->valorseguro = $res->NFe->infNFe->total->ICMSTot->vSeg;
          $NFeItem->valordesconto = $res->NFe->infNFe->total->ICMSTot->vDesc;
          $NFeItem->valoroutras = $res->NFe->infNFe->total->ICMSTot->vOutro;
          $NFeItem->valortotal = $res->NFe->infNFe->total->ICMSTot->vNF;
          $NFeItem->compoetotal = $item->prod->indTot??$res->NFe->infNFe->det->prod->indTot;
          $NFeItem->csosn = $item->imposto->ICMS->ICMSSN102->CSOSN??$res->NFe->infNFe->det->imposto->ICMS->ICMSSN102->CSOSN??null; // rever este campo

          // VALIDA QUAL O TIPO DE ICMS QUE ESTA NA NOTA ICMS00, ICMS10, ICMSSN102
          $icmsOrigem = null;
          if(isset($item->imposto->ICMS->ICMS00->orig)){
              $icmsOrigem = $item->imposto->ICMS->ICMS00->orig;
          }else{
              if(isset($res->NFe->infNFe->det->ICMS->ICMS00->orig)){
                  $icmsOrigem = $res->NFe->infNFe->det->imposto->ICMS->ICMS00->orig;
              }
          }

          if(isset($item->imposto->ICMS->ICMSSN102->orig)){
              $icmsOrigem = $item->imposto->ICMS->ICMSSN102->orig;
          }else{
              if(isset($res->NFe->infNFe->det->imposto->ICMS->ICMSSN102->orig)){
                  $icmsOrigem = $res->NFe->infNFe->det->imposto->ICMS->ICMSSN102->orig;
              }
          }

          if(isset($item->imposto->ICMS->ICMS10->orig)){
              $icmsOrigem = $item->imposto->ICMS->ICMS10->orig;
          }else{
              if(isset($res->NFe->infNFe->det->ICMS->ICMS10->orig)){
                  $icmsOrigem = $res->NFe->infNFe->det->imposto->ICMS->ICMS10->orig;
              }
          }

          $NFeItem->origem = $icmsOrigem;
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
          // dd($NFeItem);
          $NFeItem->save();

          // SALVA OS DADOS NA tblnotafiscalterceiroprodutobarra
          $produtobarra = NFeTerceiroProdutoBarra::firstOrNew([
          'codnotafiscalterceirogrupo' => $codGrupo->codnotafiscalterceirogrupo
          ]);
          $produtobarra->codnotafiscalterceirogrupo = $codGrupo->codnotafiscalterceirogrupo;;
          $produtobarra->codprodutobarra = null;
          $produtobarra->margem = null; // rever este campo
          $produtobarra->complemento = null; // rever este campo
          $produtobarra->quantidade = $item->prod->qTrib??$res->NFe->infNFe->det->prod->qTrib;
          $produtobarra->valorproduto = $item->prod->vProd??$res->NFe->infNFe->det->prod->vProd;
          // dd($produtobarra);
          $produtobarra->save();
      } // FIM DO ARMAZENA ITENS

      //SE HOUVER DUPLICATA ARMAZENA OS DADOS
      if (isset($res->NFe->infNFe->cobr)){

          if(isset($res->NFe->infNFe->cobr->dup) && count($res->NFe->infNFe->cobr->dup) > 1){
              foreach ($res->NFe->infNFe->cobr->dup as $key => $duplicata) {
                  $NFeDuplicata = NFeTerceiroDuplicata::firstOrNew([
                      'codnotafiscalterceiro' => $codnotafiscalterceiro->codnotafiscalterceiro,
                      'duplicata' => $duplicata->nDup??1
                  ]);
                  $NFeDuplicata->codnotafiscalterceiro = $codnotafiscalterceiro->codnotafiscalterceiro;
                  $NFeDuplicata->codtitulo = null; // rever este campo
                  $NFeDuplicata->duplicata = $duplicata->nDup??1;
                  $NFeDuplicata->vencimento = Carbon::parse($duplicata->dVenc);
                  $NFeDuplicata->valor = $duplicata->vDup;
                  $NFeDuplicata->ndup = $duplicata->nDup??1;
                  $NFeDuplicata->dvenc = Carbon::parse($duplicata->dVenc);
                  $NFeDuplicata->vdup = $duplicata->vDup;
                  $NFeDuplicata->save();
              }
          }
          else{
              if(isset($res->NFe->infNFe->cobr->dup)){
                  $NFeDuplicata = NFeTerceiroDuplicata::firstOrNew([
                      'codnotafiscalterceiro' => $codnotafiscalterceiro->codnotafiscalterceiro,
                      'duplicata' => $res->NFe->infNFe->cobr->dup->nDup??1
                  ]);
                  $NFeDuplicata->codnotafiscalterceiro = $codnotafiscalterceiro->codnotafiscalterceiro;
                  $NFeDuplicata->codtitulo = null; // rever este campo
                  $NFeDuplicata->duplicata = $res->NFe->infNFe->cobr->dup->nDup??1;
                  $NFeDuplicata->vencimento = Carbon::parse($res->NFe->infNFe->cobr->dup->dVenc);
                  $NFeDuplicata->valor = $res->NFe->infNFe->cobr->dup->vDup;
                  $NFeDuplicata->ndup = $res->NFe->infNFe->cobr->dup->nDup??1;
                  $NFeDuplicata->dvenc = Carbon::parse($res->NFe->infNFe->cobr->dup->dVenc);
                  $NFeDuplicata->vdup = $res->NFe->infNFe->cobr->dup->vDup;
                  $NFeDuplicata->save();
              }
          }
      }
      DB::commit();
    } // FIM DO armazenaDadosNFe

    // TRAZ DA BASE TODAS AS DFEs
    public static function listaNotas ($request) {

        // PREPARA OS PARAMETROS PARA A CONDITIONAL QUERY
        $filial = $request->filial;
        $codpessoa = $request->pessoa;
        $chave = $request->chave;
        $datainicial = $request->datainicial;
        $datafinal = $request->datafinal;
        $manifestacao = $request->manifestacao;
        $situacao = $request->situacao;

        // EXECUTA A CONDITIONAL QUERY CONFORME FILTRO REQUISITADO
        $qry = NFeTerceiro::when($filial, function($query, $filial){
            $query->where('tblnotafiscalterceiro.codfilial', $filial);
        })
        ->when($chave, function($query, $chave){
            $query->where('nfechave', $chave);
        })
        ->when($manifestacao, function($query, $manifestacao){
            $query->where('indmanifestacao', $manifestacao);
        })
        ->when($situacao, function($query, $situacao){
            $query->where('indsituacao', $situacao);
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
        $codGrupo = NFeTerceiroGrupo::where('codnotafiscalterceiro', $codnotafiscalterceiro)->first();

        // BUSCA NA tblnotafiscalterceiroitem TODOS OS ITENS VINCULADOS A NOTA
        $itens = NFeTerceiroItem::where('codnotafiscalterceirogrupo', $codGrupo->codnotafiscalterceirogrupo)->get();

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
