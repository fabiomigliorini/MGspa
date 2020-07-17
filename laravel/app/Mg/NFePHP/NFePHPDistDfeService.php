<?php

namespace Mg\NFePHP;

use Carbon\Carbon;

use NFePHP\NFe\Common\Standardize;

use Mg\Filial\Filial;
use Mg\Dfe\DfeEvento;
use Mg\Dfe\DistribuicaoDfe;
use Mg\Dfe\DistribuicaoDfeEvento;
use Mg\Dfe\DfeTipo;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiro;
use Mg\NaturezaOperacao\Operacao;
use Mg\Pessoa\PessoaService;

class NFePHPDistDfeService
{
    public static function consultar(Filial $filial, int $nsu = null)
    {
        $tools = NFePHPConfigService::instanciaTools($filial);

        // só funciona para o modelo 55
        $tools->model('55');

        // mesmo em teste utilizar na producao
        $tools->setEnvironment(1);

        //este numero deverá vir do banco de dados nas proximas buscas para reduzir
        //a quantidade de documentos, e para não baixar várias vezes as mesmas coisas.
        $nsu = $nsu??DistribuicaoDfe::where('codfilial', $filial->codfilial)->max('nsu')??0;
        //$resp = $tools->sefazDistDFe($nsu);
        // em desenvolvimento pega um arquivo de exemplo
        $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/distDfe.xml');

        $domResp = new \DOMDocument();
        $domResp->loadXML($resp);
        $docs = $domResp->getElementsByTagName('docZip');
        foreach ($docs as $doc) {

            $domDoc = new \DOMDocument();
            $domDoc->loadXML(gzdecode(base64_decode($doc->nodeValue)));

            // Determina Tipo do DFE pelo SchemaXML
            $dt = DfeTipo::firstOrNew([
                'schemaxml' => $doc->getAttribute('schema'),
            ]);
            if (empty($dt->dfetipo)) {
                $dt->dfetipo = $dt->schemaxml;
                $dt->save();
            }

            // Cria Registro de Distribuicao
            $dd = DistribuicaoDfe::firstOrNew([
                'codfilial' => $filial->codfilial,
                'nsu' => (int) $doc->getAttribute('NSU'),
                'coddfetipo' => (int) $dt->coddfetipo,
            ]);
            $dd->save();

            //salva arquivo com Dfe compactada
            $path = NFePHPPathService::pathDfeGz($dd, true);
            $gz = base64_decode($doc->nodeValue);
            file_put_contents ($path, $gz);

            switch ($dt->schemaxml) {
                case 'resEvento_v1.01.xsd':
                    static::processarResEvento($dd, $gz);
                    break;

                case 'resNFe_v1.01.xsd':
                    static::processarResNFe($dd, $gz);
                    break;

                case 'procEventoNFe_v1.00.xsd':
                    static::processarProcEventoNFe($dd, $gz);
                    break;

                // case 'procNFe_v4.00.xsd':
                    //static::processarNFe($dd, $gz);
                    break;

                default:
                    // dd([
                    //     $dt->schemaxml,
                    //     $dd,
                    // ]);
                    // code...
                    break;
            }
        }

        return [
            'ultNSU' => $domResp->getElementsByTagName('ultNSU')->item(0)->nodeValue,
            'maxNSU' => $domResp->getElementsByTagName('maxNSU')->item(0)->nodeValue,
            'documentos' => $docs->length,
        ];
    }

    public static function processarResEvento (DistribuicaoDfe $dd, $gz)
    {
        // Carrega XML
        $dom = new \DOMDocument();
        $dom->loadXML(gzdecode($gz));

        // Busca/Cria Registro de Evento
        $de = DfeEvento::firstOrCreate([
            'tpevento' => $dom->getElementsByTagName('tpEvento')->item(0)->nodeValue??null,
            'dfeevento' => $dom->getElementsByTagName('xEvento')->item(0)->nodeValue??null
        ]);

        // Busca Registro no Banco de Dados
        $dde = DistribuicaoDfeEvento::firstOrNew([
            'coddistribuicaodfe' => $dd->coddistribuicaodfe
        ]);

        // Associa os dados do XML no registro
        $dde->coddfeevento = $de->coddfeevento;
        $dde->orgao = $dom->getElementsByTagName('cOrgao')->item(0)->nodeValue??null;
        $dde->cnpj = $dom->getElementsByTagName('CNPJ')->item(0)->nodeValue??null;
        $dde->cpf = $dom->getElementsByTagName('CPF')->item(0)->nodeValue??null;
        $dde->nfechave = $dom->getElementsByTagName('chNFe')->item(0)->nodeValue??null;
        $dde->data = Carbon::parse($dom->getElementsByTagName('dhEvento')->item(0)->nodeValue)??null;
        $dde->sequencia = $dom->getElementsByTagName('nSeqEvento')->item(0)->nodeValue??null;
        $dde->recebimento = Carbon::parse($dom->getElementsByTagName('dhRecbto')->item(0)->nodeValue)??null;
        $dde->protocolo = $dom->getElementsByTagName('nProt')->item(0)->nodeValue??null;
        $dde->save();
    }

    public static function processarResNFe (DistribuicaoDfe $dd, $gz)
    {
        // Carrega XML
        $dom = new \DOMDocument();
        $dom->loadXML(gzdecode($gz));

        // procura se tem a nota no banco ja
        $nft = NotaFiscalTerceiro::firstOrNew([
            'nfechave' => $dom->getElementsByTagName('chNFe')->item(0)->nodeValue
        ]);

        // associa valores recebidos pelo xml
        $nft->codfilial = $nft->codfilial??$dd->codfilial;
        $nft->cnpj = $nft->cnpj??$dom->getElementsByTagName('CNPJ')->item(0)->nodeValue??null;
        $nft->cpf = $nft->cpf??$dom->getElementsByTagName('CPF')->item(0)->nodeValue??null;
        $nft->emitente = $nft->emitente??$dom->getElementsByTagName('xNome')->item(0)->nodeValue??null;
        $nft->ie = $nft->ie??$dom->getElementsByTagName('IE')->item(0)->nodeValue??null;
        if ($pessoa = PessoaService::buscarPorCnpjIe($nft->cnpj??$nft->cpf, $nft->ie)) {
            $nft->codpessoa = $pessoa->codpessoa;
        }
        $nft->emissao = $nft->emissao??Carbon::parse($dom->getElementsByTagName('dhEmi')->item(0)->nodeValue)??null;
        $nft->valortotal = $nft->valortotal??$dom->getElementsByTagName('vNF')->item(0)->nodeValue??null;
        $nft->recebimento = Carbon::parse($dom->getElementsByTagName('dhRecbto')->item(0)->nodeValue)??null;
        $nft->protocolo = $dom->getElementsByTagName('nProt')->item(0)->nodeValue??null;
        $nft->indsituacao = $dom->getElementsByTagName('cSitNFe')->item(0)->nodeValue??null;
        if (!empty($nft->codoperacao)) {
            switch ((int)$dom->getElementsByTagName('tpNF')->item(0)->nodeValue) {
                case 1:
                $nft->codoperacao = Operacao::ENTRADA;
                break;
                default:
                $nft->codoperacao = Operacao::SAIDA;
                break;
            }
        }
        $nft->save();

        // vincula dfe na nota fiscal de terceiro
        $dd->codnotafiscalterceiro = $nft->codnotafiscalterceiro;
        $dd->save();
    }

    public static function processarProcEventoNFe (DistribuicaoDfe $dd, $gz)
    {
        // Carrega XML
        $dom = new \DOMDocument();
        $dom->loadXML(gzdecode($gz));

        // Busca/Cria Registro de Evento
        $de = DfeEvento::firstOrCreate([
            'tpevento' => $dom->getElementsByTagName('tpEvento')->item(0)->nodeValue??null,
            'dfeevento' => $dom->getElementsByTagName('descEvento')->item(0)->nodeValue??null
        ]);

        // Busca Registro no Banco de Dados
        $dde = DistribuicaoDfeEvento::firstOrNew([
            'coddistribuicaodfe' => $dd->coddistribuicaodfe
        ]);

        // Associa os dados do XML no registro
        $dde->coddfeevento = $de->coddfeevento;
        $dde->orgao = $dom->getElementsByTagName('cOrgao')->item(0)->nodeValue??null;
        $dde->cnpj = $dom->getElementsByTagName('CNPJ')->item(0)->nodeValue??null;
        $dde->cpf = $dom->getElementsByTagName('CPF')->item(0)->nodeValue??null;
        $dde->nfechave = $dom->getElementsByTagName('chNFe')->item(0)->nodeValue??null;
        $dde->data = Carbon::parse($dom->getElementsByTagName('dhEvento')->item(0)->nodeValue)??null;
        $dde->sequencia = $dom->getElementsByTagName('nSeqEvento')->item(0)->nodeValue??null;
        $dde->recebimento = Carbon::parse($dom->getElementsByTagName('dhRegEvento')->item(0)->nodeValue)??null;
        $dde->protocolo = $dom->getElementsByTagName('nProt')->item(0)->nodeValue??null;

        // Carta de Correcao
        $dde->descricao = $dom->getElementsByTagName('xCorrecao')->item(0)->nodeValue??null;

        $dde->save();

    }


    public static function novoFornecedor($emit)
    {
        $codcidade = Cidade::where('codigooficial', $emit->enderEmit->cMun)->first();

        $fornecedor = new Pessoa();
        $fornecedor->pessoa = $emit->xNome;
        $fornecedor->fantasia = $emit->xFant??$emit->xNome;
        $fornecedor->cliente = false;
        $fornecedor->fornecedor = true;
        $fornecedor->fisica = false;
        $fornecedor->cnpj = $emit->CNPJ;
        $fornecedor->ie = $emit->IE;
        $fornecedor->endereco = $emit->enderEmit->xLgr;
        $fornecedor->numero = $emit->enderEmit->nro;
        $fornecedor->complemento = $emit->enderEmit->xCpl??null;
        $fornecedor->codcidade = $codcidade->codcidade;
        $fornecedor->bairro = $emit->enderEmit->xBairro;
        $fornecedor->cep = $emit->enderEmit->CEP;
        $fornecedor->telefone1 = $emit->enderEmit->fone??null;
        $fornecedor->emailnfe = 'nfe@mgpapelaria.com.br';
        $fornecedor->notafiscal = 0;
        // dd($pessoa);
        $fornecedor->save();

        $novoForncedor = Pessoa::orderBy('criacao', 'DESC')->first();
        return $novoForncedor;
    }


}
