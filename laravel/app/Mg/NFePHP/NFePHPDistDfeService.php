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
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiroGrupo;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiroItem;
use Mg\NaturezaOperacao\Operacao;
use Mg\Pessoa\PessoaService;
use Mg\Pessoa\Pessoa;
use Mg\Cidade\Cidade;
use Mg\Filial\FilialService;

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

                case 'procNFe_v4.00.xsd':
                    static::processarProcNFe($dd, $gz);
                    break;

                default:
                    dd([
                        $dt->schemaxml,
                        $dd,
                    ]);
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


    public static function processarProcNFe (DistribuicaoDfe $dd, $gz)
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
        $nft->natop = $nft->natop??$dom->getElementsByTagName('natOp')->item(0)->nodeValue??null;
        $nft->modelo = $nft->modelo??$dom->getElementsByTagName('mod')->item(0)->nodeValue??null;
        $nft->serie = $nft->serie??$dom->getElementsByTagName('serie')->item(0)->nodeValue??null;
        $nft->numero = $nft->numero??$dom->getElementsByTagName('nNF')->item(0)->nodeValue??null;
        $nft->emissao = $nft->emissao??Carbon::parse($dom->getElementsByTagName('dhEmi')->item(0)->nodeValue)??null;
        $nft->tipo = $nft->tipo??$dom->getElementsByTagName('tpNF')->item(0)->nodeValue??null;
        $nft->finalidade = $nft->finalidade??$dom->getElementsByTagName('finNFe')->item(0)->nodeValue??null;

        $emit = $dom->getElementsByTagName('emit')->item(0);
        $nft->cnpj = $nft->cnpj??$emit->getElementsByTagName('CNPJ')->item(0)->nodeValue??null;
        $nft->cpf = $nft->cpf??$emit->getElementsByTagName('CPF')->item(0)->nodeValue??null;
        $nft->emitente = $nft->emitente??$emit->getElementsByTagName('xNome')->item(0)->nodeValue??null;
        $nft->ie = $nft->ie??$emit->getElementsByTagName('IE')->item(0)->nodeValue??null;

        // se pessoa nao cadastrada, cria
        if ($pessoa = PessoaService::buscarPorCnpjIe($nft->cnpj??$nft->cpf, $nft->ie)) {
            $nft->codpessoa = $pessoa->codpessoa;
        } else {
            $cidade = Cidade::where('codigooficial', $emit->getElementsByTagName('cMun')->item(0)->nodeValue)->first();
            $pessoa = new Pessoa();
            $pessoa->cnpj = $emit->getElementsByTagName('CNPJ')->item(0)->nodeValue??$emit->getElementsByTagName('CPF')->item(0)->nodeValue;
            $pessoa->fisica = $emit->getElementsByTagName('CPF')->item(0)?true:false;
            $pessoa->pessoa = $emit->getElementsByTagName('xNome')->item(0)->nodeValue;
            $pessoa->fantasia = $emit->getElementsByTagName('xFant')->item(0)->nodeValue??$pessoa->pessoa;

            $pessoa->endereco = $emit->getElementsByTagName('xLgr')->item(0)->nodeValue;
            $pessoa->numero = $emit->getElementsByTagName('nro')->item(0)->nodeValue;
            $pessoa->bairro = $emit->getElementsByTagName('xBairro')->item(0)->nodeValue;
            $pessoa->codcidade = $cidade->codcidade;
            $pessoa->cep = $emit->getElementsByTagName('CEP')->item(0)->nodeValue;

            $pessoa->enderecocobranca = $pessoa->endereco;
            $pessoa->numerocobranca = $pessoa->numero;
            $pessoa->bairrocobranca = $pessoa->bairro;
            $pessoa->codcidadecobranca = $pessoa->codcidade;
            $pessoa->cepcobranca = $pessoa->cep;

            $pessoa->telefone1 = $emit->getElementsByTagName('fone')->item(0)->nodeValue;
            $pessoa->ie = $emit->getElementsByTagName('IE')->item(0)->nodeValue;

            $pessoa->email = 'nfe@mgpapelaria.com.br';

            $pessoa->fornecedor = true;
            $pessoa->notafiscal = 0;
            $pessoa->save();
        }

        // atualiza CRT da pessoa
        $pessoa->crt = $emit->getElementsByTagName('CRT')->item(0)->nodeValue;
        $pessoa->save();
        $nft->save();

        // Filial
        $dest = $dom->getElementsByTagName('dest')->item(0);
        $cnpj = $dest->getElementsByTagName('CNPJ')->item(0)->nodeValue??null;
        $cpf = $dest->getElementsByTagName('CPF')->item(0)->nodeValue??null;
        $ie = $dest->getElementsByTagName('IE')->item(0)->nodeValue??null;
        if ($filial = FilialService::buscarPorCnpjIe($cnpj??$cpf, $ie)) {
            $nft->codfilial = $filial->codfilial;
        }

        // Verifica todos os grupos criados
        $nfts = $nft->NotaFiscalTerceiroGrupoS;
        $codnotafiscalterceirogrupos = $nfts->pluck('codnotafiscalterceirogrupo');

        $dets = $dom->getElementsByTagName('det');
        foreach ($dets as $det) {
            $numero = $det->getAttribute('nItem');
            if ($nfti = NotaFiscalTerceiroItem::whereIn('codnotafiscalterceirogrupo', $codnotafiscalterceirogrupos)->where('numero', $numero)->first()) {
                $nftg = $nfti->NotaFiscalTerceiroGrupo;
            } else {
                $nftg = NotaFiscalTerceiroGrupo::create([
                    'codnotafiscalterceiro' => $nft->codnotafiscalterceiro
                ]);
                $nfti = new NotaFiscalTerceiroItem();
                $nfti->codnotafiscalterceirogrupo = $nftg->codnotafiscalterceirogrupo;
                $nfti->numero = $numero;
            }
            $nfti->referencia = $det->getElementsByTagName('cProd')->item(0)->nodeValue??null;
            $nfti->barras = $det->getElementsByTagName('cEAN')->item(0)->nodeValue??null;
            $nfti->produto = $det->getElementsByTagName('xProd')->item(0)->nodeValue??null;
            $nfti->cfop = $det->getElementsByTagName('CFOP')->item(0)->nodeValue??null;
            $nfti->unidademedida = $det->getElementsByTagName('uCom')->item(0)->nodeValue??null;
            $nfti->quantidade = (double) $det->getElementsByTagName('qCom')->item(0)->nodeValue??null;
            $nfti->valorunitario = (double) $det->getElementsByTagName('vUnCom')->item(0)->nodeValue??null;
            $nfti->valorproduto = (double) $det->getElementsByTagName('vProd')->item(0)->nodeValue??null;
            $nfti->compoetotal = boolval($det->getElementsByTagName('indTot')->item(0)->nodeValue)??null;
            $nfti->save();
            dd($nfti->getAttributes());
            dd($nftg);
            dd($nItem);
            dd($det);
        }

        dd($nft->getAttributes());

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

        // vincula dfe na nota fiscal de terceiro
        $dd->codnotafiscalterceiro = $nft->codnotafiscalterceiro;
        $dd->save();
    }


}
