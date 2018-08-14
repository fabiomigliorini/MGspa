<?php

namespace Mg\NotaFiscalTerceiro;
use Mg\MgRepository;

use Mg\Filial\Filial;
use Mg\Pessoa\Pessoa;
use Mg\NotaFiscal\NotaFiscal;
use Mg\Estoque\EstoqueLocal;

use Mg\NFePHP\NFePHPRepositoryConfig;
use Mg\NFePHP\NFePHPRepositoryConsultaCad;
use Mg\NFePHP\NFePHPRepositoryConsultaSefaz;
use Mg\NFePHP\NFePHPRepositoryDownload;
use Mg\NFePHP\NFePHPRepositoryManifestacao;

use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Common\Complements;

use Carbon\Carbon;
use DB;

class NotaFiscalTerceiroRepository extends MgRepository
{

    public static function atualizaItem($request) {
        dd('aqui');
        return;

    }

    public static function atualizaNFe($request) {
        dd('aqui');
        return;

    }

    public static function consultaSefaz(Filial $filial){

        // BUSCA NA BASE DE DADOS A ULTIMA NSU CONSULTADA DA FILIAL
        $ultimoNsu = NotaFiscalTerceiroDistribuicaoDfe::select('nsu')->where('codfilial', $filial->codfilial)->get();
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
            $dfe = NotaFiscalTerceiroDistribuicaoDfe::firstOrNew([
                'nsu' => $numnsu,
                'nfechave' => $chave
            ]);
            $dfe->nfechave = $chave;
            $dfe->codfilial = $filial->codfilial;
            $dfe->nsu = $numnsu;
            $dfe->schema = $schema;
            $dfe->save();

            // SALVA NA PASTA O ARQUIVO DFE DA CONSULTA
            $pathNFeTerceiro = NotaFiscalTerceiroRepositoryPath::pathDFe($filial, $numnsu, true);
            file_put_contents($pathNFeTerceiro, $content);

            // SALVA NA BASE OS DADOS DO XML TODO ajustar essa classe
            if($schema == "resNFe_v1.01.xsd"){
                static::armazenaResNFe($filial, $res);
            }

        }

        return true;

    } // FIM CONSULTA SEFAZ

    public static function armazenaDadosEvento ($filial) {

        $qry = NotaFiscalTerceiroDistribuicaoDfe::select('*')->where('schema', 'resEvento_v1.01.xsd')->orderBy('nsu', 'DESC')->get();
        $qry = end($qry);

        foreach ($qry as $key => $file) {

            $path = NotaFiscalTerceiroRepositoryPath::pathDFe($filial, $file->nsu);

            if(file_exists($path)){
                $xmlData = file_get_contents($path);
                $st = new Standardize();
                $xml = $st->toStd($xmlData);

                $coddistribuicaodfe = NotaFiscalTerceiroDistribuicaoDfe::select('coddistribuicaodfe')
                ->where( 'nsu', $file->nsu )->get();

                $resEvento = NotaFiscaleTerceiroEvento::firstOrNew([
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

    public static function carregarXml(Filial $filial, $chave){

        $xml = NFePHPRepositoryDownload::downloadNotaFiscalTerceiro($filial, $chave);

        NotaFiscalTerceiroRepositoryCarregaXml::armazenaDadosNFe($filial, $xml);

        return true;
    }

    // public static function armazenaDadosDFe ($xml, $filial) {
    public static function armazenaDadosConsulta ($res, Filial $filial) {
        // $filial = Filial::findOrFail($filial);

        // $qry = NotaFiscalTerceiroDistribuicaoDfe::where('nsu', '000000000023538')->get();
        $qry = NotaFiscalTerceiroDistribuicaoDfe::select('*')->get();
        $qry = end($qry);

        foreach ($qry as $key => $file) {

            $path = NotaFiscalTerceiroRepositoryPath::pathDFe($filial, $file->nsu);

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
                    NotaFiscalTerceiroRepositoryCarregaXml::armazenaDadosNFe($filial, $xml);
                }
                if($file->schema == 'procNFe_v3.10.xsd'){
                    // echo "<script>console.log( 'Debug Objects:" . $xml->chNFe . "' );</script>";
                    NotaFiscalTerceiroRepositoryCarregaXml::armazenaDadosNFe($filial, $xml);
                }

            }
        }

        return true;

    } // FIM DO armazenaDadosConsulta

    public static function armazenaResNFe($filial, $xml){

        $pessoa = static::novaPessoa($xml, $filial);
        // dd($pessoa);

        $NFe = NotaFiscalTerceiro::firstOrNew([
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
        $qry = NotaFiscalTerceiro::when($filial, function($query, $filial){
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
        $qry = NotaFiscalTerceiro::select('*')
        ->where('nfechave', $chave)
        ->join('tblfilial', 'tblnotafiscalterceiro.codfilial', '=', 'tblfilial.codfilial')
        ->select('tblnotafiscalterceiro.*', 'tblfilial.filial')->get();
        return ($qry);
    }

    // BUSCA NA BASE DE DADOS A ULTIMA NSU CONSULTADA DA FILIAL
    public static function ultimaNSU ($filial) {
        $ultimoNsu = NotaFiscalTerceiroDistribuicaoDfe::select('nsu')->where('codfilial', $filial->codfilial)->get();
        $ultimoNsu = end($ultimoNsu);
        $ultimoNsu = end($ultimoNsu);
        return $ultimoNsu;
    }

    public static function listaItem ($codnotafiscalterceiro) {
        // BUSCA NA tblnotafiscalterceirogrupo o codnotafiscalterceirogrupo
        $codGrupo = NotaFiscalTerceiroGrupo::where('codnotafiscalterceiro', $codnotafiscalterceiro)->first();

        // BUSCA NA tblnotafiscalterceiroitem TODOS OS ITENS VINCULADOS A NOTA
        $itens = NotaFiscalTerceiroItem::where('codnotafiscalterceirogrupo', $codGrupo->codnotafiscalterceirogrupo)->orderBy('numero', 'ASC')->get();

        // dd($itens);
        return $itens;

    }

    public static function manifestacao($request) {

        $res = NFePHPRepositoryManifestacao::manifestacao($request);

        // ATUALIZA MANIFESTACAO NA BASE
        $NF = NotaFiscalTerceiro::findOrFail([
            'nfechave' => $request->nfechave,
            'codnotafiscalterceiro' => $request->codnotafiscalterceiro
        ])->first();
        $NF->indmanifestacao = $res->retEvento->infEvento->tpEvento;
        $NF->save();

    }

} // FIM DA CLASSE
