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
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiroDuplicata;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiroPagamento;
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
        // dd($nsu);
        // $resp = $tools->sefazDistDFe($nsu);
        // file_put_contents("/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/{$filial->codfilial}-{$nsu}.xml", $resp);

        // em desenvolvimento pega um arquivo de exemplo
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/103-0.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/801-0.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/101-49844.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/101-49894.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/101-50044.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/102-0.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/104-4427.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/102-12075.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/101-49994.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/old/101-49893.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/old/101-50093.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/old/101-49793.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/old/101-50043.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/old/401-4548.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/old/101-50193.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/old/401-4598.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/old/101-49993.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/old/401-4698.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/old/101-50293.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/old/401-4648.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/old/101-49943.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/old/101-49737.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/old/101-49843.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/old/101-50143.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/old/401-0.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/old/101-0.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/old/101-50243.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/102-11975.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/401-4599.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/103-15499.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/101-50094.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/101-50144.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/401-0.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/101-0.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/401-4549.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/104-0.xml');
        // $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/102-12025.xml');
        $resp = file_get_contents('/opt/www/MGspa/laravel/app/Mg/NFePHP/exemplos/101-49944.xml');

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
            file_put_contents($path, $gz);

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
                    throw new \Exception("Impossível determinar acao para Schema '{$dt->schemaxml}'", 1);
                    break;
            }
        }

        return [
            'ultNSU' => $domResp->getElementsByTagName('ultNSU')->item(0)->nodeValue,
            'maxNSU' => $domResp->getElementsByTagName('maxNSU')->item(0)->nodeValue,
            'documentos' => $docs->length,
        ];
    }

    public static function processarResEvento(DistribuicaoDfe $dd, $gz)
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
        if (!empty($dd->coddistribuicaodfeevento)) {
            $dde = $dd->DistribuicaoDfeEvento;
        } else {
            $dde = new DistribuicaoDfeEvento();
        }

        // Associa os dados do XML no registro
        $dde->coddfeevento = $de->coddfeevento;
        $dde->orgao = @$dom->getElementsByTagName('cOrgao')->item(0)->nodeValue;
        $dde->cnpj = @$dom->getElementsByTagName('CNPJ')->item(0)->nodeValue;
        $dde->cpf = @$dom->getElementsByTagName('CPF')->item(0)->nodeValue;
        $dde->sequencia = @$dom->getElementsByTagName('nSeqEvento')->item(0)->nodeValue;
        $dde->recebimento = @Carbon::parse($dom->getElementsByTagName('dhRecbto')->item(0)->nodeValue);
        $dde->protocolo = @$dom->getElementsByTagName('nProt')->item(0)->nodeValue;
        $dde->save();

        // vincula dfe no evento
        $dd->coddistribuicaodfeevento = $dde->coddistribuicaodfeevento;
        $dd->nfechave = @$dom->getElementsByTagName('chNFe')->item(0)->nodeValue;
        $dd->data = @Carbon::parse($dom->getElementsByTagName('dhEvento')->item(0)->nodeValue);
        $dd->save();
    }

    public static function processarResNFe(DistribuicaoDfe $dd, $gz)
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
        $nft->cnpj = $nft->cnpj??@$dom->getElementsByTagName('CNPJ')->item(0)->nodeValue;
        $nft->cpf = $nft->cpf??@$dom->getElementsByTagName('CPF')->item(0)->nodeValue;
        $nft->emitente = $nft->emitente??@$dom->getElementsByTagName('xNome')->item(0)->nodeValue;
        $nft->ie = $nft->ie??@$dom->getElementsByTagName('IE')->item(0)->nodeValue;
        if ($pessoa = PessoaService::buscarPorCnpjIe($nft->cnpj??$nft->cpf, $nft->ie)) {
            $nft->codpessoa = $pessoa->codpessoa;
        }
        $nft->emissao = $nft->emissao??@Carbon::parse($dom->getElementsByTagName('dhEmi')->item(0)->nodeValue);
        $nft->valortotal = $nft->valortotal??@$dom->getElementsByTagName('vNF')->item(0)->nodeValue;
        $nft->recebimento = @Carbon::parse($dom->getElementsByTagName('dhRecbto')->item(0)->nodeValue);
        $nft->protocolo = @$dom->getElementsByTagName('nProt')->item(0)->nodeValue;
        $nft->indsituacao = @$dom->getElementsByTagName('cSitNFe')->item(0)->nodeValue;
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
        $dd->nfechave = $nft->nfechave;
        $dd->data = $nft->recebimento??$nft->emissao;
        $dd->save();
    }

    public static function processarProcEventoNFe(DistribuicaoDfe $dd, $gz)
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
        if (!empty($dd->coddistribuicaodfeevento)) {
            $dde = $dd->DistribuicaoDfeEvento;
        } else {
            $dde = new DistribuicaoDfeEvento();
        }

        // Associa os dados do XML no registro
        $dde->coddfeevento = $de->coddfeevento;
        $dde->orgao = @$dom->getElementsByTagName('cOrgao')->item(0)->nodeValue;
        $dde->cnpj = @$dom->getElementsByTagName('CNPJ')->item(0)->nodeValue;
        $dde->cpf = @$dom->getElementsByTagName('CPF')->item(0)->nodeValue;
        $dde->sequencia = @$dom->getElementsByTagName('nSeqEvento')->item(0)->nodeValue;
        $dde->recebimento = @Carbon::parse($dom->getElementsByTagName('dhRegEvento')->item(0)->nodeValue);
        $dde->protocolo = @$dom->getElementsByTagName('nProt')->item(0)->nodeValue;

        // Carta de Correcao
        $dde->descricao = @$dom->getElementsByTagName('xCorrecao')->item(0)->nodeValue;

        $dde->save();

        // vincula dfe no evento
        $dd->coddistribuicaodfeevento = $dde->coddistribuicaodfeevento;
        $dd->nfechave = @$dom->getElementsByTagName('chNFe')->item(0)->nodeValue;
        $dd->data = @Carbon::parse($dom->getElementsByTagName('dhEvento')->item(0)->nodeValue);
        $dd->save();

    }


    public static function processarProcNFe(DistribuicaoDfe $dd, $gz)
    {
        // Carrega XML
        $dom = new \DOMDocument();
        $dom->loadXML(gzdecode($gz));

        // procura se tem a nota no banco ja
        $nft = NotaFiscalTerceiro::firstOrNew([
            'nfechave' => numeroLimpo($dom->getElementsByTagName('chNFe')->item(0)->nodeValue)
        ]);

        // dados da Nfe
        $nft->codfilial = $nft->codfilial??$dd->codfilial;
        $nft->natop = $nft->natop??@$dom->getElementsByTagName('natOp')->item(0)->nodeValue;
        $nft->modelo = $nft->modelo??@$dom->getElementsByTagName('mod')->item(0)->nodeValue;
        $nft->serie = $nft->serie??@$dom->getElementsByTagName('serie')->item(0)->nodeValue;
        $nft->numero = $nft->numero??@$dom->getElementsByTagName('nNF')->item(0)->nodeValue;
        $nft->emissao = $nft->emissao??@Carbon::parse($dom->getElementsByTagName('dhEmi')->item(0)->nodeValue);
        $nft->tipo = $nft->tipo??@$dom->getElementsByTagName('tpNF')->item(0)->nodeValue;
        $nft->finalidade = $nft->finalidade??@$dom->getElementsByTagName('finNFe')->item(0)->nodeValue;
        switch ((int)$dom->getElementsByTagName('tpNF')->item(0)->nodeValue) {
            case 1:
                $nft->codoperacao = Operacao::ENTRADA;
                break;
            default:
                $nft->codoperacao = Operacao::SAIDA;
                break;
        }
        $nft->indsituacao = $nft->indsituacao??NotaFiscalTerceiro::INDSITUACAO_AUTORIZADA;
        $nft->observacoes = $nft->observacoes??@$dom->getElementsByTagName('infCpl')->item(0)->nodeValue;
        // $nft->indmanifestacao = $nft->indmanifestacao??NotaFiscalTerceiro::INDMANIFESTACAO_SEM;

        // valores
        $icmstot = $dom->getElementsByTagName('ICMSTot')->item(0);
        $nft->icmsbase = @$icmstot->getElementsByTagName('vBC')->item(0)->nodeValue;
        $nft->icmsvalor = @$icmstot->getElementsByTagName('vICMS')->item(0)->nodeValue;
        $nft->icmsstbase = @$icmstot->getElementsByTagName('vBCST')->item(0)->nodeValue;
        $nft->icmsstvalor = @$icmstot->getElementsByTagName('vST')->item(0)->nodeValue;
        $nft->ipivalor = @$icmstot->getElementsByTagName('vIPI')->item(0)->nodeValue;
        $nft->valorprodutos = @$icmstot->getElementsByTagName('vProd')->item(0)->nodeValue;
        $nft->valorfrete = @$icmstot->getElementsByTagName('vFrete')->item(0)->nodeValue;
        $nft->valorseguro = @$icmstot->getElementsByTagName('vSeg')->item(0)->nodeValue;
        $nft->valordesconto = @$icmstot->getElementsByTagName('vDesc')->item(0)->nodeValue;
        $nft->valoroutras = @$icmstot->getElementsByTagName('vOutro')->item(0)->nodeValue;
        $nft->valortotal = @$icmstot->getElementsByTagName('vNF')->item(0)->nodeValue;

        // emitente
        $emit = $dom->getElementsByTagName('emit')->item(0);
        $nft->cnpj = $nft->cnpj??@$emit->getElementsByTagName('CNPJ')->item(0)->nodeValue;
        $nft->cpf = $nft->cpf??@$emit->getElementsByTagName('CPF')->item(0)->nodeValue;
        $nft->emitente = $nft->emitente??@$emit->getElementsByTagName('xNome')->item(0)->nodeValue;
        $nft->ie = $nft->ie??@$emit->getElementsByTagName('IE')->item(0)->nodeValue;

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

        // Filial
        $dest = $dom->getElementsByTagName('dest')->item(0);
        $cnpj = @$dest->getElementsByTagName('CNPJ')->item(0)->nodeValue;
        $cpf = @$dest->getElementsByTagName('CPF')->item(0)->nodeValue;
        $ie = @$dest->getElementsByTagName('IE')->item(0)->nodeValue;
        if ($filial = FilialService::buscarPorCnpjIe($cnpj??$cpf, $ie)) {
            $nft->codfilial = $filial->codfilial;
        }

        // salva NotaFiscalTerceiro
        $nft->save();

        // Verifica todos os grupos de itens criados
        $nfts = $nft->NotaFiscalTerceiroGrupoS;
        $codnotafiscalterceirogrupos = $nfts->pluck('codnotafiscalterceirogrupo');

        // percorre todos os itens (det) da nfe
        $dets = $dom->getElementsByTagName('det');
        foreach ($dets as $det) {

            // verifica se o item ja estava importado
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

            $nfti->referencia = @$det->getElementsByTagName('cProd')->item(0)->nodeValue;
            $nfti->barras = @$det->getElementsByTagName('cEAN')->item(0)->nodeValue;
            $nfti->produto = @$det->getElementsByTagName('xProd')->item(0)->nodeValue;
            $nfti->adicional = @$det->getElementsByTagName('infAdProd')->item(0)->nodeValue;
            $nfti->ncm = @$det->getElementsByTagName('NCM')->item(0)->nodeValue;
            $nfti->cest = @$det->getElementsByTagName('CEST')->item(0)->nodeValue;
            $nfti->cfop = @$det->getElementsByTagName('CFOP')->item(0)->nodeValue;

            $nfti->barras = @$det->getElementsByTagName('cEAN')->item(0)->nodeValue;
            $nfti->unidademedida = @$det->getElementsByTagName('uCom')->item(0)->nodeValue;
            $nfti->quantidade = @$det->getElementsByTagName('qCom')->item(0)->nodeValue;
            $nfti->valorunitario = @$det->getElementsByTagName('vUnCom')->item(0)->nodeValue;

            $nfti->barrastributavel = @$det->getElementsByTagName('cEANTrib')->item(0)->nodeValue;
            $nfti->unidademedidatributavel = @$det->getElementsByTagName('uTrib')->item(0)->nodeValue;
            $nfti->quantidadetributavel = @$det->getElementsByTagName('qTrib')->item(0)->nodeValue;
            $nfti->valorunitariotributavel = @$det->getElementsByTagName('vUnTrib')->item(0)->nodeValue;

            $nfti->valorproduto = @$det->getElementsByTagName('vProd')->item(0)->nodeValue;
            $nfti->valorfrete = @$det->getElementsByTagName('vFrete')->item(0)->nodeValue;
            $nfti->valorseguro = @$det->getElementsByTagName('vSeg')->item(0)->nodeValue;
            $nfti->valordesconto = @$det->getElementsByTagName('vDesc')->item(0)->nodeValue;
            $nfti->valoroutras = @$det->getElementsByTagName('vOutro')->item(0)->nodeValue;

            $nfti->compoetotal = @boolval($det->getElementsByTagName('indTot')->item(0)->nodeValue);

            // ICMS
            foreach ($det->getElementsByTagName('ICMS') as $icms) {

                // CST
                $nfti->csosn = @$icms->getElementsByTagName('CSOSN')->item(0)->nodeValue;
                $nfti->icmscst = @$icms->getElementsByTagName('CST')->item(0)->nodeValue;

                // ICMS Normal
                $nfti->icmsbasemodalidade = @(int) $icms->getElementsByTagName('modBC')->item(0)->nodeValue;
                $nfti->icmsbase = @$icms->getElementsByTagName('vBC')->item(0)->nodeValue;
                $nfti->icmspercentual = @$icms->getElementsByTagName('pICMS')->item(0)->nodeValue;
                $nfti->icmsvalor = @$icms->getElementsByTagName('vICMS')->item(0)->nodeValue;

                // ICMS Diferido
                // $nfti-> = @$icms->getElementsByTagName('pDif')->item(0)->nodeValue;
                // $nfti-> = @$icms->getElementsByTagName('vICMSDif')->item(0)->nodeValue;

                // ICMS ST
                $nfti->icmsstbasemodalidade = @$icms->getElementsByTagName('modBCST')->item(0)->nodeValue;
                $nfti->icmsstbasepercentualreducao = @$icms->getElementsByTagName('pRedBCST')->item(0)->nodeValue;
                $nfti->icmsstbase = @$icms->getElementsByTagName('vBCST')->item(0)->nodeValue;
                $nfti->icmsstmva = @$icms->getElementsByTagName('pMVAST')->item(0)->nodeValue;
                $nfti->icmsstpercentual = @$icms->getElementsByTagName('pICMSST')->item(0)->nodeValue;
                $nfti->icmsstvalor = @$icms->getElementsByTagName('vICMSST')->item(0)->nodeValue;

                // ICMS Desonerado
                // $nfti-> = @$icms->getElementsByTagName('vICMSDeson')->item(0)->nodeValue;
                // $nfti-> = @$icms->getElementsByTagName('motDesICMS')->item(0)->nodeValue;

            }

            // IPI
            foreach ($det->getElementsByTagName('IPITrib') as $ipi) {
                $nfti->ipicst = @$ipi->getElementsByTagName('CST')->item(0)->nodeValue;
                $nfti->ipibase = @$ipi->getElementsByTagName('vBC')->item(0)->nodeValue;
                $nfti->ipipercentual = @$ipi->getElementsByTagName('pIPI')->item(0)->nodeValue;
                $nfti->ipivalor = @$ipi->getElementsByTagName('vIPI')->item(0)->nodeValue;
            }

            // PIS
            foreach ($det->getElementsByTagName('PIS') as $pis) {
                $nfti->piscst = @$pis->getElementsByTagName('CST')->item(0)->nodeValue;
                $nfti->pisbase = @$pis->getElementsByTagName('vBC')->item(0)->nodeValue;
                $nfti->pispercentual = @$pis->getElementsByTagName('pPIS')->item(0)->nodeValue;
                $nfti->pisvalor = @$pis->getElementsByTagName('vPIS')->item(0)->nodeValue;
            }

            // COFINS
            foreach ($det->getElementsByTagName('COFINS') as $pis) {
                $nfti->cofinscst = @$pis->getElementsByTagName('CST')->item(0)->nodeValue;
                $nfti->cofinsbase = @$pis->getElementsByTagName('vBC')->item(0)->nodeValue;
                $nfti->cofinspercentual = @$pis->getElementsByTagName('pCOFINS')->item(0)->nodeValue;
                $nfti->cofinsvalor = @$pis->getElementsByTagName('vCOFINS')->item(0)->nodeValue;
            }

            // salva NotaFiscalTerceiroItem
            $nfti->save();
        }

        // Duplicatas
        $dups = $dom->getElementsByTagName('dup');
        foreach ($dups as $dup) {
            $nfd = NotaFiscalTerceiroDuplicata::firstOrNew([
                'codnotafiscalterceiro' => $nft->codnotafiscalterceiro,
                'numero' => $dup->getElementsByTagName('nDup')->item(0)->nodeValue,
            ]);
            $nfd->valor = $dup->getElementsByTagName('vDup')->item(0)->nodeValue;
            $nfd->vencimento = @Carbon::parse($dup->getElementsByTagName('dVenc')->item(0)->nodeValue);
            $nfd->save();
        }

        // Formas de Pagamento
        $pags = $dom->getElementsByTagName('pag');
        foreach ($pags as $pag) {
            $nftp = NotaFiscalTerceiroPagamento::firstOrNew([
                'codnotafiscalterceiro' => $nft->codnotafiscalterceiro,
                'forma' => $pag->getElementsByTagName('tPag')->item(0)->nodeValue,
            ]);
            $nftp->valor = @$pag->getElementsByTagName('vPag')->item(0)->nodeValue;
            $nftp->cnpj = @$pag->getElementsByTagName('CNPJ')->item(0)->nodeValue;
            $nftp->bandeira = @$pag->getElementsByTagName('tBand')->item(0)->nodeValue;
            $nftp->autorizacao = @$pag->getElementsByTagName('cAut')->item(0)->nodeValue;
            $nftp->save();
        }

        // vincula dfe na nota fiscal de terceiro
        $dd->codnotafiscalterceiro = $nft->codnotafiscalterceiro;
        $dd->nfechave = $nft->nfechave;
        $dd->data = $nft->recebimento??$nft->emissao??Carbon::now();
        $dd->save();
    }
}
