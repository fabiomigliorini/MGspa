<?php

namespace Mg\NotaFiscalTerceiro;
use Mg\MgService;

use Mg\Filial\Filial;
use Mg\Pessoa\Pessoa;

use Mg\NFePHP\NFePHPConsultaSefazService;
use Mg\NFePHP\NFePHPDownloadService;
use Mg\NFePHP\NFePHPManifestacaoService;

use NFePHP\NFe\Common\Standardize;

use Carbon\Carbon;
use DB;

class NotaFiscalTerceiroService extends MgService
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
        $ultimoNsu = NotaFiscalTerceiroDistribuicaoDfe::where('codfilial', $filial->codfilial)
        ->orderBy('nsu', 'DESC')->first();

        $resConsulta = NFePHPConsultaSefazService::consultaSefaz($filial, $ultimoNsu->nsu);

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

            // BUSCA O coddistribuicaodfe  DA ULTIMA DFE ARMAZENADA
            $distribuicaodfe = NotaFiscalTerceiroDistribuicaoDfe::where([['nsu',$numnsu],['nfechave',$chave]])->first();

            // SALVA NA PASTA O ARQUIVO DFE DA CONSULTA
            $pathNFeTerceiro = NotaFiscalTerceiroPathService::pathDFe($filial, $numnsu, true);
            file_put_contents($pathNFeTerceiro, $content);

            // ARMAZENA PARCIALMENTE OS DADOS DA NOTA
            if($schema == 'resNFe_v1.01.xsd'){
                // echo "<script>console.log( 'Debug Objects:" . $xml->chNFe . "' );</script>";
                static::armazenaResNFe($filial, $res, $distribuicaodfe->coddistribuicaodfe);
            }
            // SE HOUVER  A NOTA COMPLETA JA ARMAZENA OS DADOS
            if($schema == 'procNFe_v4.00.xsd' || $schema == 'procNFe_v3.10.xsd'){
                // echo "<script>console.log( 'Debug Objects:" . $xml->chNFe . "' );</script>";
                NotaFiscalTerceiroCarregarXmlService::armazenaDadosNFe($filial, $xml);
            }

        }

        return true;

    } // FIM consultaSefaz


        public static function armazenaResNFe($filial, $xml, $coddistribuicaodfe){

            $pessoa = Pessoa::where([['ie', $xml->IE],['cnpj', $xml->CNPJ]])->first();

            $NFe = NotaFiscalTerceiro::firstOrNew([
                'nfechave' => $xml->chNFe,
                'protocolo' => $xml->nProt
            ]);
            $NFe->coddistribuicaodfe = $coddistribuicaodfe;
            $NFe->codfilial = $filial->codfilial;
            $NFe->codpessoa = $pessoa->codpessoa??null;
            $NFe->nfechave = $xml->chNFe;
            $NFe->cnpj = $xml->CNPJ;
            $NFe->emitente = $xml->xNome;
            $NFe->ie = $xml->IE;
            $NFe->emissao = Carbon::parse($xml->dhEmi);
            $NFe->valortotal = $xml->vNF;
            $NFe->digito = $xml->digVal;
            $NFe->protocolo = $xml->nProt;
            $NFe->tipo = $xml->tpNF;
            $NFe->indsituacao = $xml->cSitNFe;
            $NFe->recebimento = $xml->dhRecbto;
            // dd($NFe);
            $NFe->save();
        }

    public static function armazenaDadosConsulta ($filial) {
    // public static function armazenaDadosConsulta ($filial, $res) {
        // $filial = Filial::findOrFail($filial);

        // 000000000023238
        // $qry = NotaFiscalTerceiroDistribuicaoDfe::where('nsu', '000000000023238')->get();
        $qry = NotaFiscalTerceiroDistribuicaoDfe::where('codfilial', $filial->codfilial)->get();
        $qry = end($qry);

        $loop = 1;
        foreach ($qry as $key => $file) {
            $path = NotaFiscalTerceiroPathService::pathDFe($filial, $file->nsu);

            if(file_exists($path)){
                $xmlData = file_get_contents($path);
                $st = new Standardize();
                $xml = $st->toStd($xmlData);

                // ARMAZENA PARCIALMENTE OS DADOS DA NOTA
                if($file->schema == 'resNFe_v1.01.xsd'){
                    // echo "<script>console.log( 'Debug Objects:" . $xml->chNFe . "' );</script>";
                    static::armazenaResNFe($filial, $xml, $file->coddistribuicaodfe);
                }

                // SE HOUVER  A NOTA COMPLETA JA ARMAZENA OS DADOS
                if($file->schema == 'procNFe_v3.10.xsd' || $file->schema == 'procNFe_v4.00.xsd'){
                    echo "<script>console.log( 'Debug:".$loop."====" . $file->nfechave . "' );</script>";
                    NotaFiscalTerceiroCarregarXmlService::armazenaDadosNFe($filial, $xml);
                }

                $loop++;
            }
        }

        return true;

    } // FIM DO armazenaDadosConsulta

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

        // VALIDA QUAL O VALOR DA SITUACAO A SER FILTRADA
        $filtroSituacao = array();
        switch ($situacao) {
            case 1:
                $filtroSituacao[0] = 1; // resNFe autorizada
                $filtroSituacao[1] = 100; // procNFe autorizada
                $filtroSituacao[2] = 150; // procNFe autorizada fora de prazo
                break;

            case 2:
                $filtroSituacao[0] = 2; // resNFe denegada
                $filtroSituacao[1] = 110; // procNFe denegada
                $filtroSituacao[2] = null; // adicionado para o array conter 3 posicoes e nao dar erro
                break;

            case 3:
                $filtroSituacao[0] = 3; // resNFe cancelada
                $filtroSituacao[1] = 101; // procNFe cancelada
                $filtroSituacao[2] = 151; // procNFe cancelada fora de prazo
                break;

            default:
                $filtroSituacao = null;
                break;
        }

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
        ->when($filtroSituacao, function($query, $filtroSituacao){
            $query->whereIn('indsituacao', [$filtroSituacao[0],$filtroSituacao[1],$filtroSituacao[2]]);
        })
        ->when($datainicial, function($query, $datainicial){
            $query->whereDate('emissao', '>=', $datainicial);
        })
        ->when($datafinal, function($query, $datafinal){
            $query->whereDate('emissao', '<=', $datafinal);
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
        $qry = NotaFiscalTerceiro::where('nfechave', $chave)
        ->join('tblfilial', 'tblnotafiscalterceiro.codfilial', '=', 'tblfilial.codfilial')
        ->select('tblnotafiscalterceiro.*', 'tblfilial.filial')->get();
        return ($qry);
    }

    // BUSCA NA BASE DE DADOS A ULTIMA NSU CONSULTADA DA FILIAL
    public static function ultimaNSU ($filial) {

        $ultimoNsu = NotaFiscalTerceiroDistribuicaoDfe::where('codfilial', $filial->codfilial)
        ->orderBy('nsu', 'DESC')->first();

        return $ultimoNsu->nsu;
    }

    public static function listaItem ($codnotafiscalterceiro) {
        // BUSCA NA tblnotafiscalterceirogrupo o codnotafiscalterceirogrupo
        $codGrupo = NotaFiscalTerceiroGrupo::where('codnotafiscalterceiro', $codnotafiscalterceiro)->first();

        // BUSCA NA tblnotafiscalterceiroitem TODOS OS ITENS VINCULADOS A NOTA
        $itens = NotaFiscalTerceiroItem::where('codnotafiscalterceirogrupo', $codGrupo->codnotafiscalterceirogrupo)
        ->orderBy('numero', 'ASC')->get();

        // dd($itens);
        return $itens;

    }

    // FAZ O ENVIO DA MANIFESTACAO PARA O SEFAZ E ARMAZENA NA BASE O indmanifestacao
    public static function manifestacao($request) {

        $res = NFePHPManifestacaoService::manifestacao($request);

        $NF = NotaFiscalTerceiro::where([
            'nfechave' => $request->nfechave,
            'codnotafiscalterceiro' => $request->codnotafiscalterceiro
        ])->first();
        $NF->indmanifestacao = $res->retEvento->infEvento->tpEvento;
        $NF->save();

        return true;

    }

    // FAZ O DOWNLOAD NA NFe COMPLETA BUSCANDO NO SERVIDOR DA SEFAZ
    public static function downloadNotaFiscalTerceiro(Filial $filial, $chave){

        $xml = NFePHPDownloadService::downloadNotaFiscalTerceiro($filial, $chave);

        NotaFiscalTerceiroCarregarXmlService::armazenaDadosNFe($filial, $xml);

        return true;
    }

} // FIM DA CLASSE
