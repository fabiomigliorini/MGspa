<?php

    /*
     * To change this license header, choose License Headers in Project Properties.
     * To change this template file, choose Tools | Templates
     * and open the template in the editor.
     *
     *
     *
     */

//include_once '../../bootstrap.php';

Yii::import('application.vendors.nfephp.*');
Yii::import('application.vendors.PHPMailer.*');
require_once('bootstrap.php');

use NFePHP\NFe\MakeNFe;
use NFePHP\NFe\MailNFe;
use NFePHP\NFe\ToolsNFe;
use NFePHP\Extras\Danfe;
use NFePHP\Extras\Danfce;
use NFePHP\Common\Files\FilesFolders;

class NFePHPNovoController extends Controller {

    var $conf;
    var $arquivoXMLProtocoloCancelamento;
    var $arquivoXMLProtocoloRecibo;
    var $arquivoXMLProtocoloSituacao;
    var $arquivoXMLAprovada;
    var $arquivoXMLDenegada;
    var $arquivoXMLValidada;
    var $arquivoXMLRecebida;
    var $arquivoPDF;
    public $layout = '//layouts/column2';
    public $defaultAction = 'view';

    /**
     *
     * @param integer $id
     * @return NotaFiscal
     * @throws CHttpException
     */
    public function loadModelNotaFiscal($id) {
        $model = NotaFiscal::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    /**
     *
     * @param integer $id
     * @return Filial
     * @throws CHttpException
     */
    public function loadModelFilial($id) {
        $model = Filial::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    /**
     *
     * @param integer $id
     * @return NfeTerceiro
     * @throws CHttpException
     */
    public function loadModelNfeTerceiro($id) {
        $model = NfeTerceiro::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    /**
     *
     * @param NotaFiscal $nf
     * @return boolean
     */
    public function confereNumero($nf) {

        If (!empty($nf->numero))
            return true;

        $sequence = "tblnotafiscal_numero_{$nf->codfilial}_{$nf->serie}_{$nf->modelo}_seq";
        $numero = Yii::app()->db->createCommand("SELECT NEXTVAL('$sequence')")->queryScalar();

        if (empty($numero))
            return false;

        $nf->numero = $numero;
        $nf->emissao = date('d/m/Y H:i:s');
        $nf->saida = date('d/m/Y H:i:s');
        $nf->update();

        return true;
    }

    public function montarConfiguracao($codfilial, $modelo, $codnotafiscal = false, $codnfeterceiro = false) {
        $filial = $this->loadModelFilial($codfilial);

        $conf['atualizacao'] = date('Y-m-d H:i:s');
        $conf['tpAmb'] = $filial->nfeambiente;
        //$conf['pathXmlUrlFileNFe'] =  "nfe_ws3_mod$modelo.xml";
        $conf['pathXmlUrlFileNFe'] = "nfe_ws3_mod$modelo.xml";
        $conf['pathXmlUrlFileCTe'] = 'cte_ws2.xml';
        $conf['pathXmlUrlFileMDFe'] = 'mdf2_ws1.xml';
        $conf['pathXmlUrlFileCLe'] = '';
        $conf['pathXmlUrlFileNFSe'] = '';
        $conf['pathNFeFiles'] = "/var/www/NFePHP/Arquivos/NFe/$codfilial";
        $conf['pathCTeFiles'] = "/var/www/NFePHP/Arquivos/CTe/$codfilial";
        $conf['pathMDFeFiles'] = "/var/www/NFePHP/Arquivos/MDFe/$codfilial";
        $conf['pathCLeFiles'] = "/var/www/NFePHP/Arquivos/CLe/$codfilial";
        $conf['pathNFSeFiles'] = "/var/www/NFePHP/Arquivos/NFSe/$codfilial";
        $conf['pathCertsFiles'] = '/var/www/MGsis/protected/vendors/nfephp/certs/';
        $conf['siteUrl'] = 'http://localhost/MGsis/protected/vendors/nfephp/install/';
        //$conf['schemesNFe'] =  'PL_008f';
        $conf['schemesNFe'] = 'PL_008i2';
        $conf['schemesCTe'] = 'PL_CTe_200';
        $conf['schemesMDFe'] = 'PL_MDFe_100';
        $conf['schemesCLe'] = '';
        $conf['schemesNFSe'] = '';
        $conf['razaosocial'] = $filial->Pessoa->pessoa;
        $conf['siglaUF'] = $filial->Pessoa->Cidade->Estado->sigla;
        $conf['cnpj'] = str_pad($filial->Pessoa->cnpj, 14, '0', STR_PAD_LEFT);
        $conf['tokenIBPT'] = $filial->tokenibpt;
        $conf['tokenNFCe'] = $filial->nfcetoken;
        $conf['tokenNFCeId'] = $filial->nfcetokenid;
        $conf['certPfxName'] = "$codfilial.pfx";
        $conf['certPassword'] = $filial->senhacertificado;
        $conf['certPhrase'] = '';
        $conf['aDocFormat']['format'] = 'P';
        $conf['aDocFormat']['paper'] = 'A4';
        $conf['aDocFormat']['southpaw'] = '1';
        $conf['aDocFormat']['pathLogoFile'] = '/var/www/MGsis/images/MGPapelariaColorido.png';
        $conf['aDocFormat']['logoPosition'] = 'L';
        $conf['aDocFormat']['font'] = 'Times';
        $conf['aDocFormat']['printer'] = '';
        $conf['aMailConf']['mailAuth'] = '1';
        $conf['aMailConf']['mailFrom'] = false;
        $conf['aMailConf']['mailSmtp'] = 'smtp.gmail.com';
        $conf['aMailConf']['mailUser'] = 'nfe@mgpapelaria.com.br';
        $conf['aMailConf']['mailPass'] = '701flamboyants';
        $conf['aMailConf']['mailProtocol'] = '';
        $conf['aMailConf']['mailPort'] = '587';
        $conf['aMailConf']['mailFromMail'] = null;
        $conf['aMailConf']['mailFromName'] = 'MG Papelaria - Sistema de NFe';
        $conf['aMailConf']['mailReplayToMail'] = null;
        $conf['aMailConf']['mailReplayToName'] = null;
        $conf['aMailConf']['mailImapHost'] = null;
        $conf['aMailConf']['mailImapPort'] = null;
        $conf['aMailConf']['mailImapSecurity'] = null;
        $conf['aMailConf']['mailImapNocerts'] = null;
        $conf['aMailConf']['mailImapBox'] = null;
        $conf['aProxyConf']['proxyIp'] = "";
        $conf['aProxyConf']['proxyPort'] = "";
        $conf['aProxyConf']['proxyUser'] = "";
        $conf['aProxyConf']['proxyPass'] = "";

        $this->conf = $conf;

        if ($codnotafiscal != false) {
            $nf = $this->loadModelNotaFiscal($codnotafiscal);

            $diretorioBase = $this->conf['pathNFeFiles']
                . (($nf->Filial->nfeambiente == Filial::NFEAMBIENTE_PRODUCAO) ? '/producao/' : '/homologacao/');

            $this->arquivoXMLAprovada = $diretorioBase
                . 'enviadas/aprovadas/' . substr($nf->emissao, 6, 4) . substr($nf->emissao, 3, 2) . '/';
            if (!is_dir($this->arquivoXMLAprovada))
                mkdir($this->arquivoXMLAprovada, 0755, true);
            $this->arquivoXMLAprovada .= $nf->nfechave . '-protNFe.xml';

            $this->arquivoXMLDenegada = $diretorioBase
                . 'enviadas/denegadas/' . substr($nf->emissao, 6, 4) . substr($nf->emissao, 3, 2) . '/';
            if (!is_dir($this->arquivoXMLDenegada))
                mkdir($this->arquivoXMLDenegada, 0755, true);
            $this->arquivoXMLDenegada .= $nf->nfechave . '-protNFe.xml';

            $this->arquivoXMLProtocoloCancelamento = $diretorioBase
                . 'canceladas/' . substr($nf->emissao, 6, 4) . substr($nf->emissao, 3, 2) . '/';
            if (!is_dir($this->arquivoXMLProtocoloCancelamento))
                mkdir($this->arquivoXMLProtocoloCancelamento, 0755, true);
            $this->arquivoXMLProtocoloCancelamento .= $nf->nfechave . '-CancNFe-procEvento.xml';

            $this->arquivoXMLValidada = $diretorioBase
                . 'validadas/' . substr($nf->emissao, 6, 4) . substr($nf->emissao, 3, 2) . '/';
            if (!is_dir($this->arquivoXMLValidada))
                mkdir($this->arquivoXMLValidada, 0755, true);
            $this->arquivoXMLValidada .= $nf->nfechave . '-NFe.xml';

            $hoje = date('d/m/Y');

            $this->arquivoXMLProtocoloRecibo = $diretorioBase
                . 'temporarias/' . substr($hoje, 6, 4) . substr($hoje, 3, 2) . '/';
            if (!is_dir($this->arquivoXMLProtocoloRecibo))
                mkdir($this->arquivoXMLProtocoloRecibo, 0755, true);
            $this->arquivoXMLProtocoloRecibo .= $nf->nfereciboenvio . '-retConsReciNFe.xml';

            $this->arquivoXMLProtocoloSituacao = $diretorioBase
                . 'temporarias/' . substr($hoje, 6, 4) . substr($hoje, 3, 2) . '/';
            $this->arquivoXMLProtocoloSituacao .= $nf->nfechave . '-retConsSitNFe.xml';

            $this->arquivoPDF = $diretorioBase
                . 'pdf/' . substr($nf->emissao, 6, 4) . substr($nf->emissao, 3, 2) . '/';
            if (!is_dir($this->arquivoPDF))
                mkdir($this->arquivoPDF, 0755, true);
            $this->arquivoPDF .= $nf->nfechave . '-NFe.pdf';
        }

        if ($codnfeterceiro != false) {

            $nft = $this->loadModelNfeTerceiro($codnfeterceiro);

            $diretorioBase = $this->conf['pathNFeFiles']
                . (($nft->Filial->nfeambiente == Filial::NFEAMBIENTE_PRODUCAO) ? '/producao/' : '/homologacao/');

            $this->arquivoXMLRecebida = $diretorioBase
                . 'recebidas/' . substr($nft->emissao, 6, 4) . substr($nft->emissao, 3, 2) . '/';
            if (!is_dir($this->arquivoXMLRecebida))
                mkdir($this->arquivoXMLRecebida, 0755, true);

            $this->arquivoXMLRecebida .= $nft->nfechave . '-nfeProc.xml';
        }


        $json = json_encode($conf);
        return $json;
    }

    /**
     *
     * @param integer $codnotafiscal
     * @var NotaFiscal $nf
     */
    public function actionCriaXml($codnotafiscal) {
        /**
         * @var NotaFiscal $nf
         */
        $nf = $this->loadModelNotaFiscal($codnotafiscal);

        $aRetorno['retorno'] = false;
        $aRetorno['ex'] = null;
        $aRetorno['tpEmis'] = $nf->tpemis;

        try {
            if (!$nf->emitida)
                throw new Exception('Nossa fical não é de nossa emissão!');

            switch ($nf->codstatus) {
                case NotaFiscal::CODSTATUS_NOVA:
                case NotaFiscal::CODSTATUS_DIGITACAO:
                case NotaFiscal::CODSTATUS_NOSSA_EMISSAO:
                case NotaFiscal::CODSTATUS_NAOAUTORIZADA:
                    break;

                case NotaFiscal::CODSTATUS_AUTORIZADA:
                case NotaFiscal::CODSTATUS_LANCADA:
                case NotaFiscal::CODSTATUS_INUTILIZADA:
                case NotaFiscal::CODSTATUS_CANCELADA:
                    throw new Exception('Status da Nota Fiscal não permite esta ação!');
                    break;
            }

            if (!$this->confereNumero($nf))
                throw new Exception('Erro ao determinar numero da Nota Fiscal!');

            $make = new MakeNFe();

            //Dados da NFe (ide)
            $cUF = $nf->Filial->Pessoa->Cidade->Estado->codigooficial;
            $cNF = str_pad($nf->numero, 8, '0', STR_PAD_LEFT); //numero aleatório da NF
            $natOp = utf8_encode($nf->NaturezaOperacao->naturezaoperacao); //natureza da operação
            $indPag = (sizeof($nf->NotaFiscalDuplicatass) > 0) ? 1 : 0; //0=Pagamento à vista; 1=Pagamento a prazo; 2=Outros
            $mod = $nf->modelo; //modelo da NFe 55 ou 65 essa última NFCe
            $serie = $nf->serie; //serie da NFe
            $nNF = $nf->numero; // numero da NFe
            $dh = DateTime::createFromFormat('d/m/Y H:i:s', $nf->emissao); //para versão 3.10 '2014-02-03T13:22:42-3.00' não informar para NFCe
            $dhEmi = $dh->format('Y-m-d\TH:i:sP');
            $dh = DateTime::createFromFormat('d/m/Y H:i:s', $nf->saida);
            $dhSaiEnt = $dh->format('Y-m-d\TH:i:sP'); //versão 2.00, 3.00 e 3.10
            $tpNF = ($nf->codoperacao == Operacao::ENTRADA) ? 0 : 1; //0=Entrada; 1=Saída
            $idDest = '1'; //1=Operação interna; 2=Operação interestadual; 3=Operação com exterior.
            if ($nf->Pessoa->Cidade->codestado <> $nf->Filial->Pessoa->Cidade->codestado)
                $idDest = '2';
            $cMunFG = $nf->Filial->Pessoa->Cidade->codigooficial;
            $tpImp = '1'; //0=Sem geração de DANFE; 1=DANFE normal, Retrato; 2=DANFE normal, Paisagem;
            //3=DANFE Simplificado; 4=DANFE NFC-e; 5=DANFE NFC-e em mensagem eletrônica
            //(o envio de mensagem eletrônica pode ser feita de forma simultânea com a impressão do DANFE;
            //usar o tpImp=5 quando esta for a única forma de disponibilização do DANFE).
            $tpEmis = '1'; //1=Emissão normal (não em contingência);
            //2=Contingência FS-IA, com impressão do DANFE em formulário de segurança;
            //3=Contingência SCAN (Sistema de Contingência do Ambiente Nacional);
            //4=Contingência DPEC (Declaração Prévia da Emissão em Contingência);
            //5=Contingência FS-DA, com impressão do DANFE em formulário de segurança;
            //6=Contingência SVC-AN (SEFAZ Virtual de Contingência do AN);
            //7=Contingência SVC-RS (SEFAZ Virtual de Contingência do RS);
            //9=Contingência off-line da NFC-e (as demais opções de contingência são válidas também para a NFC-e);
            //Nota: Para a NFC-e somente estão disponíveis e são válidas as opções de contingência 5 e 9.

            $tpAmb = $nf->Filial->nfeambiente; //1=Produção; 2=Homologação
            $finNFe = $nf->NaturezaOperacao->finnfe; //1=NF-e normal; 2=NF-e complementar; 3=NF-e de ajuste; 4=Devolução/Retorno.
            $indFinal = ($nf->Pessoa->consumidor) ? '1' : '0'; //0=Não; 1=Consumidor final;
            $indPres = '1'; //0=Não se aplica (por exemplo, Nota Fiscal complementar ou de ajuste);
            //1=Operação presencial;
            //2=Operação não presencial, pela Internet;
            //3=Operação não presencial, Teleatendimento;
            //4=NFC-e em operação com entrega a domicílio;
            //9=Operação não presencial, outros.
            $procEmi = '0'; //0=Emissão de NF-e com aplicativo do contribuinte;
            //1=Emissão de NF-e avulsa pelo Fisco;
            //2=Emissão de NF-e avulsa, pelo contribuinte com seu certificado digital, através do site do Fisco;
            //3=Emissão NF-e pelo contribuinte com aplicativo fornecido pelo Fisco.
            $verProc = '1'; //versão do aplicativo emissor
            $dhCont = ''; //entrada em contingência AAAA-MM-DDThh:mm:ssTZD
            $xJust = ''; //Justificativa da entrada em contingência

            if ($nf->modelo == NotaFiscal::MODELO_NFCE) {
                $tpImp = '4'; //DANFE NFC-e
                $tpEmis = $nf->Filial->Empresa->modoemissaonfce;

                if ($tpEmis == Empresa::MODOEMISSAONFCE_OFFLINE) {
                    $nf->tpemis = NotaFiscal::TPEMIS_OFFLINE;
                    $nf->save();
                    $aRetorno['tpEmis'] = $nf->tpemis;

                    $dh = DateTime::createFromFormat('d/m/Y H:i:s', $nf->Filial->Empresa->contingenciadata);
                    $dhCont = $dh->format('Y-m-d\TH:i:sP'); //entrada em contingência AAAA-MM-DDThh:mm:ssTZD
                    $xJust = $nf->Filial->Empresa->contingenciajustificativa; //Justificativa da entrada em contingência

                    if ($finNFe != 1)
                        throw new Exception("Finalidade de emissão ($finNFe) da Natureza de Operação não permite emissão OFFLINE!");

                    if ($indFinal != 1)
                        throw new Exception("Não é permitida emissão OFFLINE para Pessoas que não sejam Consumidor Final!");

                    if ($idDest != 1)
                        throw new Exception("Não é permitida emissão OFFLINE para Pessoas de fora do Estado de {$nf->Filial->Pessoa->Cidade->Estado->estado}!");
                }
            }

            //Numero e versão da NFe (infNFe)
            //$chave = '35140258716523000119550000000280051760377394';
            $tempData = explode("-", $dhEmi);
            $ano = $tempData[0] - 2000;
            $mes = $tempData[1];
            $cnpj = str_pad($nf->Filial->Pessoa->cnpj, 14, '0', STR_PAD_LEFT); //'58716523000119';
            $chave = $make->montaChave($cUF, $ano, $mes, $cnpj, $mod, $serie, $nNF, $tpEmis, $cNF);
            $nf->nfechave = $chave;
            $nf->save();
            $versao = '3.10';
            $resp = $make->taginfNFe($chave, $versao);


            $cDV = substr($chave, -1); //digito verificador
            //tag IDE
            $resp = $make->tagide($cUF, $cNF, $natOp, $indPag, $mod, $serie, $nNF, $dhEmi, $dhSaiEnt, $tpNF, $idDest, $cMunFG, $tpImp, $tpEmis, $cDV, $tpAmb, $finNFe, $indFinal, $indPres, $procEmi, $verProc, $dhCont, $xJust);

            /* ### */

            //refNFe NFe referenciada
            foreach ($nf->NotaFiscalReferenciadas as $nfr) {
                $refNFe = $nfr->nfechave;
                $resp = $make->tagrefNFe($refNFe);
            }

            //refNF Nota Fiscal 1A referenciada
            //$cUF = '35';
            //$AAMM = '1312';
            //$cnpj = '12345678901234';
            //$mod = '1A';
            //$serie = '0';
            //$nNF = '1234';
            //$resp = $make->tagrefNF($cUF, $AAMM, $cnpj, $mod, $serie, $nNF);
            //NFPref Nota Fiscal Produtor Rural referenciada
            //$cUF = '35';
            //$AAMM = '1312';
            //$cnpj = '12345678901234';
            //$cpf = '123456789';
            //$IE = '123456';
            //$mod = '1';
            //$serie = '0';
            //$nNF = '1234';
            //$resp = $make->tagrefNFP($cUF, $AAMM, $cnpj, $cpf, $IE, $mod, $serie, $nNF);
            //CTeref CTe referenciada
            //$refCTe = '12345678901234567890123456789012345678901234';
            //$resp = $make->tagrefCTe($refCTe);
            //ECFref ECF referenciada
            //$mod = '90';
            //$nECF = '12243';
            //$nCOO = '111';
            //$resp = $make->tagrefECF($mod, $nECF, $nCOO);
            //Dados do emitente
            //$cnpj = '58716523000119';
            $cpf = '';
            $xNome = utf8_encode($nf->Filial->Pessoa->pessoa);
            $xFant = utf8_encode($nf->Filial->Pessoa->fantasia);
            $IE = Yii::app()->format->NumeroLimpo($nf->Filial->Pessoa->ie);
            $IEST = '';
            $IM = ''; //'95095870';
            $CNAE = ''; //'0131380';
            $CRT = $nf->Filial->crt;
            $resp = $make->tagemit($cnpj, $cpf, $xNome, $xFant, $IE, $IEST, $IM, $CNAE, $CRT);

            //endereço do emitente
            $xLgr = utf8_encode($nf->Filial->Pessoa->endereco);
            $nro = utf8_encode($nf->Filial->Pessoa->numero);
            $xCpl = utf8_encode($nf->Filial->Pessoa->complemento);
            if (empty($xCpl))
                $xCpl = '-';
            $xBairro = utf8_encode($nf->Filial->Pessoa->bairro);
            $cMun = $nf->Filial->Pessoa->Cidade->codigooficial;
            $xMun = utf8_encode($nf->Filial->Pessoa->Cidade->cidade);
            $UF = utf8_encode($nf->Filial->Pessoa->Cidade->Estado->sigla);
            $CEP = $nf->Filial->Pessoa->cep;
            $cPais = $nf->Filial->Pessoa->Cidade->Estado->Pais->codigooficial;
            $xPais = utf8_encode($nf->Filial->Pessoa->Cidade->Estado->Pais->pais);
            $fone = Yii::app()->format->NumeroLimpo($nf->Filial->Pessoa->telefone1);
            if (empty($fone))
                $fone = Yii::app()->format->NumeroLimpo($nf->Filial->Pessoa->telefone2);
            if (empty($fone))
                $fone = Yii::app()->format->NumeroLimpo($nf->Filial->Pessoa->telefone3);
            $fone = utf8_encode($fone);
            $resp = $make->tagenderEmit($xLgr, $nro, $xCpl, $xBairro, $cMun, $xMun, $UF, $CEP, $cPais, $xPais, $fone);

            //destinatário
            if ($nf->codpessoa != Pessoa::CONSUMIDOR) {
                if ($nf->Pessoa->fisica) {
                    $cnpj = '';
                    $cpf = str_pad($nf->Pessoa->cnpj, 11, '0', STR_PAD_LEFT); //'58716523000119';
                } else {
                    $cnpj = str_pad($nf->Pessoa->cnpj, 14, '0', STR_PAD_LEFT); //'58716523000119';
                    $cpf = '';
                }
                $idEstrangeiro = '';
                $xNome = substr(utf8_encode($nf->Pessoa->pessoa), 0, 60);
                $IE = Yii::app()->format->NumeroLimpo($nf->Pessoa->ie);
                if ($nf->Pessoa->Cidade->Estado->sigla != 'MT') {
                    $indIEDest = (empty($IE)) ? '9' : '1';
                } else {
                    $indIEDest = (empty($IE)) ? '2' : '1';
                }
                $ISUF = '';
                $IM = '';
                $email = $nf->Pessoa->emailnfe;
                if (empty($email))
                    $email = $nf->Pessoa->email;
                if (empty($email))
                    $email = $nf->Pessoa->emailcobranca;
                $email = utf8_encode($email);
                $resp = $make->tagdest($cnpj, $cpf, $idEstrangeiro, $xNome, $indIEDest, $IE, $ISUF, $IM, $email);

                //Endereço do destinatário
                $xLgr = utf8_encode($nf->Pessoa->endereco);
                $nro = utf8_encode($nf->Pessoa->numero);
                $xCpl = utf8_encode($nf->Pessoa->complemento);
                if (empty($xCpl))
                    $xCpl = '-';
                $xBairro = utf8_encode($nf->Pessoa->bairro);
                $cMun = $nf->Pessoa->Cidade->codigooficial;
                $xMun = utf8_encode($nf->Pessoa->Cidade->cidade);
                $UF = utf8_encode($nf->Pessoa->Cidade->Estado->sigla);
                $CEP = $nf->Pessoa->cep;
                $cPais = $nf->Pessoa->Cidade->Estado->Pais->codigooficial;
                $xPais = utf8_encode($nf->Pessoa->Cidade->Estado->Pais->pais);
                $fone = Yii::app()->format->NumeroLimpo($nf->Pessoa->telefone1);
                if (empty($fone))
                    $fone = Yii::app()->format->NumeroLimpo($nf->Pessoa->telefone2);
                if (empty($fone))
                    $fone = Yii::app()->format->NumeroLimpo($nf->Pessoa->telefone3);
                $fone = utf8_encode($fone);
                $resp = $make->tagenderDest($xLgr, $nro, $xCpl, $xBairro, $cMun, $xMun, $UF, $CEP, $cPais, $xPais, $fone);
            }

            //Identificação do local de retirada (se diferente do emitente)
            //$cnpj = '12345678901234';
            //$cpf = '';
            //$xLgr = 'Rua Vanish';
            //$nro = '000';
            //$xCpl = 'Ghost';
            //$xBairro = 'Assombrado';
            //$cMun = '3509502';
            //$xMun = 'Campinas';
            //$UF = 'SP';
            //$resp = $make->tagretirada($cnpj, $cpf, $xLgr, $nro, $xCpl, $xBairro, $cMun, $xMun, $UF);
            //Identificação do local de Entrega (se diferente do destinatário)
            //$cnpj = '12345678901234';
            //$cpf = '';
            //$xLgr = 'Viela Mixuruca';
            //$nro = '2';
            //$xCpl = 'Quabrada do malandro';
            //$xBairro = 'Favela Mau Olhado';
            //$cMun = '3509502';
            //$xMun = 'Campinas';
            //$UF = 'SP';
            //$resp = $make->tagentrega($cnpj, $cpf, $xLgr, $nro, $xCpl, $xBairro, $cMun, $xMun, $UF);
            //Identificação dos autorizados para fazer o download da NFe (somente versão 3.1)
            //$aAut = array('11111111111111','2222222','33333333333333');
            //foreach ($aAut as $aut) {
            //    if (strlen($aut) == 14) {
            //        $resp = $make->tagautXML($aut);
            //    } else {
            //        $resp = $make->tagautXML('', $aut);
            //    }
            //}

            $nItem = 0;
            $totalPis = 0;
            $totalCofins = 0;
            $totalTrib = 0;
            $totalTribFederal = 0;
            $totalTribEstadual = 0;
            $totalTribMunicipal = 0;
            $ibptFonte = '';

            foreach ($nf->NotaFiscalProdutoBarras as $nfpb) {
                $nItem++;

                // TAG PRODUTO
                $cProd = Yii::app()->format->formataPorMascara($nfpb->ProdutoBarra->codproduto, "######");
                //concatena o '-Quantidade' da embalagem
                if (isset($nfpb->ProdutoBarra->ProdutoEmbalagem))
                    $cProd .= '-' . str_replace('C/', '', $nfpb->ProdutoBarra->ProdutoEmbalagem->descricao);
                $cProd = utf8_encode($cProd);
                $cEAN = utf8_encode($nfpb->ProdutoBarra->barrasValido() ? $nfpb->ProdutoBarra->barras : "");
                $xProd = utf8_encode((empty($nfpb->descricaoalternativa)) ? $nfpb->ProdutoBarra->descricao : $nfpb->descricaoalternativa);
                $NCM = utf8_encode(Yii::app()->format->formataPorMascara($nfpb->ProdutoBarra->Produto->Ncm->ncm, "########"));
                $EXTIPI = '';
                $CFOP = $nfpb->codcfop;
                $uCom = utf8_encode($nfpb->ProdutoBarra->UnidadeMedida->sigla);
                $qCom = number_format($nfpb->quantidade, 3, '.', '');
                $vUnCom = number_format($nfpb->valorunitario, 10, '.', '');
                $vProd = number_format($nfpb->valortotal, 2, '.', '');
                $cEANTrib = utf8_encode($nfpb->ProdutoBarra->barrasValido() ? $nfpb->ProdutoBarra->barras : "");
                $uTrib = utf8_encode($nfpb->ProdutoBarra->UnidadeMedida->sigla); //number_format($nfpb->valorunitario, 3, '.', '');
                $qTrib = number_format($nfpb->quantidade, 3, '.', '');
                $vUnTrib = number_format($nfpb->valorunitario, 10, '.', '');

                $vFrete = round(($nf->valorfrete / $nf->valorprodutos) * $nfpb->valortotal, 2);
                if ($vFrete > 0)
                    $vFrete = number_format($vFrete, 2, '.', '');
                else
                    $vFrete = '';

                $vSeg = round(($nf->valorseguro / $nf->valorprodutos) * $nfpb->valortotal, 2);
                if ($vSeg > 0)
                    $vSeg = number_format($vSeg, 2, '.', '');
                else
                    $vSeg = '';

                $vDesc = round(($nf->valordesconto / $nf->valorprodutos) * $nfpb->valortotal, 2);
                if ($vDesc > 0)
                    $vDesc = number_format($vDesc, 2, '.', '');
                else
                    $vDesc = '';

                $vOutro = round(($nf->valoroutras / $nf->valorprodutos) * $nfpb->valortotal, 2);
                if ($vOutro > 0)
                    $vOutro = number_format($vOutro, 2, '.', '');
                else
                    $vOutro = '';

                $indTot = '1';
                $xPed = '';
                $nItemPed = '';
                $nFCI = '';
                $resp = $make->tagprod($nItem, $cProd, $cEAN, $xProd, $NCM, $EXTIPI, $CFOP, $uCom, $qCom, $vUnCom, $vProd, $cEANTrib, $uTrib, $qTrib, $vUnTrib, $vFrete, $vSeg, $vDesc, $vOutro, $indTot, $xPed, $nItemPed, $nFCI);

                if (isset($nfpb->ProdutoBarra->Produto->Cest))
                    $cest = $make->tagCEST($nItem, $nfpb->ProdutoBarra->Produto->Cest->cest);

                // TAG IMPOSTO
                $vTotTrib = 0;
                $aliqFederal = 0;
                $aliqEstadual = 0;
                $aliqMunicipal = 0;

                if ($nf->NaturezaOperacao->ibpt) {
                    foreach ($nfpb->ProdutoBarra->Produto->Ncm->Ibptaxs as $tax) {
                        $aliqFederal = ($nfpb->ProdutoBarra->Produto->importado) ? $tax->importadosfederal : $tax->nacionalfederal;
                        $aliqEstadual = $tax->estadual;
                        $aliqMunicipal = $tax->municipal;
                        $ibptFonte = "{$tax->fonte} {$tax->chave} {$tax->versao}";
                        break;
                    }

                    $vTotTribFederal = ($nfpb->valortotal * $aliqFederal) / 100;
                    $vTotTribEstadual = ($nfpb->valortotal * $aliqEstadual) / 100;
                    $vTotTribMunicipal = ($nfpb->valortotal * $aliqMunicipal) / 100;
                    $vTotTrib = round($vTotTribFederal + $vTotTribEstadual + $vTotTribMunicipal, 2);
                    $totalTrib += $vTotTrib;
                    $totalTribFederal += $vTotTribFederal;
                    $totalTribEstadual += $vTotTribEstadual;
                    $totalTribMunicipal += $vTotTribMunicipal;
                }

                $vTotTrib = number_format($vTotTrib, 2, '.', '');
                $resp = $make->tagimposto($nItem, $vTotTrib);

                // TAG ICMS
                if ($nfpb->ProdutoBarra->Produto->importado)
                    $orig = 2; // Estrangeira - Adquirida no mercado interno
                else
                    $orig = 0; // Nacional, exceto as indicadas nos códigos 3, 4, 5 e 8

                $modBC = '';
                $pRedBC = '';
                $vICMSDeson = '';
                $motDesICMS = '';
                //$modBCST = '1'; // '1';
                $modBCST = '';
                $pMVAST = ''; // '50.00';
                $pRedBCST = '';
                $pDif = '';
                $vICMSDif = '';
                $vICMSOp = '';
                $vBCSTRet = '';
                $vICMSSTRet = '';
                $csosn = $nfpb->csosn;

                switch ($nf->Filial->crt) {

                    // Lucro Presumido
                    case Filial::CRT_REGIME_NORMAL:

                        //ICMS
                        $cst = MGFormatter::formataPorMascara($nfpb->icmscst, '##');

                        $vBC = number_format($nfpb->icmsbase, 2, '.', '');
                        $pICMS = number_format($nfpb->icmspercentual, 2, '.', '');
                        $vICMS = number_format($nfpb->icmsvalor, 2, '.', '');

                        if (!empty($nfpb->icmsbase) && ($nfpb->icmsbase < $nfpb->valortotal) && $cst == 20) {
                            $pRedBC = number_format((1 - round($nfpb->icmsbase / $nfpb->valortotal, 2)) * 100, 2, '.', '');
                        }

                        $modBC = '3';

                        $vBCST = number_format($nfpb->icmsstbase, 2, '.', '');
                        $pICMSST = number_format($nfpb->icmsstpercentual, 2, '.', '');
                        $vICMSST = number_format($nfpb->icmsstvalor, 2, '.', '');

                        $modBCST = '4';

                        $resp = $make->tagICMS($nItem, $orig, $cst, $modBC, $pRedBC, $vBC, $pICMS, $vICMS, $vICMSDeson, $motDesICMS, $modBCST, $pMVAST, $pRedBCST, $vBCST, $pICMSST, $vICMSST, $pDif, $vICMSDif, $vICMSOp, $vBCSTRet, $vICMSSTRet);

                        if ($nf->Filial->Pessoa->Cidade->codestado != $nf->Pessoa->Cidade->codestado) {

                            $vBCUFDest = $vBC;
                            $pFCPUFDest = 0;
                            $pICMSUFDest = 0;
                            $pICMSInter = number_format(($nfpb->ProdutoBarra->Produto->importado) ? 4 : 12, 2, '.', '');
                            switch (substr($nf->emissao, 6, 4)) {
                                case '2016':
                                    $pICMSInterPart = number_format(40, 2, '.', '');
                                    break;
                                case '2017':
                                    $pICMSInterPart = number_format(60, 2, '.', '');
                                    break;
                                case '2018':
                                    $pICMSInterPart = number_format(80, 2, '.', '');
                                    break;
                                default:
                                    $pICMSInterPart = number_format(100, 2, '.', '');
                                    break;
                            }
                            $vFCPUFDest = 0;
                            $vICMSUFDest = 0;
                            $vICMSUFRemet = 0;

                            $resp = $make->tagICMSUFDest($nItem, $vBCUFDest, $pFCPUFDest, $pICMSUFDest, $pICMSInter, $pICMSInterPart, $vFCPUFDest, $vICMSUFDest, $vICMSUFRemet);
                        }

                        //IPI
                        if (!empty($nfpb->ipivalor)) {
                            //
                            //$cst = $nfpb->ipicst;

                            $cst = str_pad($nfpb->ipicst, 2, '0', STR_PAD_LEFT);
                            $clEnq = '';
                            $cnpjProd = '';
                            $cSelo = '';
                            $qSelo = '';
                            // TODO: Jogar logica cEnq para modelagem do banco de dados
                            if ($nfpb->ipicst == 4 || $nfpb->ipicst == 54)
                                $cEnq = '001'; // Livros, jornais, periódicos e o papel destinado à sua impressão – Art. 18 Inciso I do Decreto 7.212/2010
                            else
                                $cEnq = '999'; // Tributação normal IPI; Outros;
                            $vBC = number_format($nfpb->ipibase, 2, '.', '');
                            $pIPI = number_format($nfpb->ipipercentual, 2, '.', '');
                            $qUnid = '';
                            $vUnid = '';
                            $vIPI = number_format($nfpb->ipivalor, 2, '.', '');

                            $resp = $make->tagIPI($nItem, $cst, $clEnq, $cnpjProd, $cSelo, $qSelo, $cEnq, $vBC, $pIPI, $qUnid, $vUnid, $vIPI);
                        }

                        //PIS
                        $cst = MGFormatter::formataPorMascara($nfpb->piscst, '##');
                        $vBC = number_format($nfpb->pisbase, 2, '.', '');
                        $pPIS = number_format($nfpb->pispercentual, 2, '.', '');
                        $vPIS = number_format($nfpb->pisvalor, 2, '.', '');
                        $qBCProd = number_format(0, 2, '.', '');
                        $vAliqProd = number_format(0, 2, '.', '');
                        switch ($cst) {
                            case '49':
                            case '99':
                            case '70':
                                $resp = $make->tagPIS($nItem, $cst, $vBC, $pPIS, $vPIS);
                                break;
                            default:
                                $resp = $make->tagPIS($nItem, $cst, $vBC, $pPIS, $vPIS, $qBCProd, $vAliqProd);
                                break;
                        }

                        //PISST
                        //$resp = $make->tagPISST($nItem, $vBC, $pPIS, $qBCProd, $vAliqProd, $vPIS);
                        //COFINS
                        $cst = MGFormatter::formataPorMascara($nfpb->cofinscst, '##');
                        $vBC = number_format($nfpb->cofinsbase, 2, '.', '');
                        $pCOFINS = number_format($nfpb->cofinspercentual, 2, '.', '');
                        $vCOFINS = number_format($nfpb->cofinsvalor, 2, '.', '');
                        $qBCProd = number_format(0, 2, '.', '');
                        $vAliqProd = number_format(0, 2, '.', '');
                        switch ($cst) {
                            case '49':
                            case '99':
                            case '70':
                                $resp = $make->tagCOFINS($nItem, $cst, $vBC, $pCOFINS, $vCOFINS);
                                break;
                            default:
                                $resp = $make->tagCOFINS($nItem, $cst, $vBC, $pCOFINS, $vCOFINS, $qBCProd, $vAliqProd);
                                break;
                        }

                        //COFINSST
                        //$resp = $make->tagCOFINSST($nItem, $vBC, $pCOFINS, $qBCProd, $vAliqProd, $vCOFINS);
                        break;

                    default: // SIMPLES
                        $cst = '';
                        $vBCST = ''; // '150.00'; //150.00
                        $pICMSST = ''; // '7.00';
                        $vICMSST = ''; // '15.00';
                        $vBC = '';
                        $pICMS = '';
                        $vICMS = '';
                        $pCredSN = '';
                        $vCredICMSSN = '';
                        //$pCredSN = number_format($nfpb->icmspercentual, 2, '.', '');
                        //$vCredICMSSN = number_format($nfpb->icmsvalor, 2, '.', '');
                        $resp = $make->tagICMSSN($nItem, $orig, $csosn, $modBC, $vBC, $pRedBC, $pICMS, $vICMS, $pCredSN, $vCredICMSSN, $modBCST, $pMVAST, $pRedBCST, $vBCST, $pICMSST, $vICMSST, $vBCSTRet, $vICMSSTRet);

                        //PIS
                        $cst = '01';
                        $vBC = 0;
                        $pPIS = 0;
                        $vPIS = 0;
                        $qBCProd = '';
                        $vAliqProd = '';
                        $resp = $make->tagPIS($nItem, $cst, $vBC, $pPIS, $vPIS, $qBCProd, $vAliqProd);

                        //COFINS
                        $cst = '01';
                        $vBC = 0;
                        $pCOFINS = 0;
                        $vCOFINS = 0;
                        $qBCProd = '';
                        $vAliqProd = '';
                        $resp = $make->tagCOFINS($nItem, $cst, $vBC, $pCOFINS, $vCOFINS, $qBCProd, $vAliqProd);

                        break;
                }

                $totalPis += $nfpb->pisvalor;
                $totalCofins += $nfpb->cofinsvalor;
            }


            $vBC = number_format($nf->icmsbase, 2, '.', '');
            $vICMS = number_format($nf->icmsvalor, 2, '.', '');
            $vICMSDeson = number_format(0, 2, '.', '');
            $vBCST = number_format($nf->icmsstbase, 2, '.', '');
            $vST = number_format($nf->icmsstvalor, 2, '.', '');
            $vProd = number_format($nf->valorprodutos, 2, '.', '');
            $vFrete = number_format($nf->valorfrete, 2, '.', '');
            $vSeg = number_format($nf->valorseguro, 2, '.', '');
            $vDesc = number_format($nf->valordesconto, 2, '.', '');
            $vII = number_format(0, 2, '.', '');
            $vIPI = number_format($nf->ipivalor, 2, '.', '');
            $vPIS = number_format($totalPis, 2, '.', '');
            $vCOFINS = number_format($totalCofins, 2, '.', '');
            $vOutro = number_format($nf->valoroutras, 2, '.', '');
            $vNF = number_format($nf->valortotal, 2, '.', '');
            $vTotTrib = number_format($totalTrib, 2, '.', '');
            if ($nf->Filial->crt != Filial::CRT_REGIME_NORMAL) {
                $vBC = '0.00';
                $vICMS = '0.00';
            }
            //ICMSTot
            $resp = $make->tagICMSTot($vBC, $vICMS, $vICMSDeson, $vBCST, $vST, $vProd, $vFrete, $vSeg, $vDesc, $vII, $vIPI, $vPIS, $vCOFINS, $vOutro, $vNF, $vTotTrib);

            //frete
            $modFrete = $nf->frete; //0=Por conta do emitente; 1=Por conta do destinatário/remetente; 2=Por conta de terceiros;
            $resp = $make->tagtransp($modFrete);

            //transportadora
            //$cnpj = '';
            //$cpf = '12345678901';
            //$xNome = 'Ze da Carroca';
            //$IE = '';
            //$xEnder = 'Beco Escuro';
            //$xMun = 'Campinas';
            //$UF = 'SP';
            //$resp = $make->tagtransporta($cnpj, $cpf, $xNome, $IE, $xEnder, $xMun, $UF);
            //valores retidos para transporte
            //$vServ = '258,69'; //Valor do Serviço
            //$vBCRet = '258,69'; //BC da Retenção do ICMS
            //$pICMSRet = '10,00'; //Alíquota da Retenção
            //$vICMSRet = '25,87'; //Valor do ICMS Retido
            //$CFOP = '5352';
            //$cMunFG = '3509502'; //Código do município de ocorrência do fato gerador do ICMS do transporte
            //$resp = $make->tagretTransp($vServ, $vBCRet, $pICMSRet, $vICMSRet, $CFOP, $cMunFG);
            //dados dos veiculos de transporte
            //$placa = 'AAA1212';
            //$UF = 'SP';
            //$RNTC = '12345678';
            //$resp = $make->tagveicTransp($placa, $UF, $RNTC);
            //dados dos reboques
            //$aReboque = array(
            //    array('ZZQ9999', 'SP', '', '', ''),
            //    array('QZQ2323', 'SP', '', '', '')
            //);
            //foreach ($aReboque as $reb) {
            //    $placa = $reb[0];
            //    $UF = $reb[1];
            //    $RNTC = $reb[2];
            //    $vagao = $reb[3];
            //    $balsa = $reb[4];
            //    //$resp = $make->tagreboque($placa, $UF, $RNTC, $vagao, $balsa);
            //}
            //dados dos volumes transportados
            /*
              $aVol = array(array('24','VOLUMES','','','389.950','399.550',''));
              foreach ($aVol as $vol) {
              $qVol = $vol[0]; //Quantidade de volumes transportados
              $esp = $vol[1]; //Espécie dos volumes transportados
              $marca = $vol[2]; //Marca dos volumes transportados
              $nVol = $vol[3]; //Numeração dos volume
              $pesoL = $vol[4];
              $pesoB = $vol[5];
              $aLacres = $vol[6];
              $resp = $make->tagvol($qVol, $esp, $marca, $nVol, $pesoL, $pesoB, $aLacres);
              }

              //dados da fatura
              $nFat = '000034189';
              $vOrig = '19466.30';
              $vDesc = '';
              $vLiq = '19466.30';
              $resp = $make->tagfat($nFat, $vOrig, $vDesc, $vLiq);

              //dados das duplicadas
              $aDup = array(array('34189-1','2015-04-10','19466.30'));
              foreach ($aDup as $dup) {
              $nDup = $dup[0];
              $dVenc = $dup[1];
              $vDup = $dup[2];
              $resp = $make->tagdup($nDup, $dVenc, $vDup);
              }
             */
            if ($nf->modelo != NotaFiscal::MODELO_NFCE) {
                foreach ($nf->NotaFiscalDuplicatass as $nfd) {
                    $nDup = utf8_encode($nfd->fatura);
                    $dh = DateTime::createFromFormat('d/m/Y', $nfd->vencimento); //para versão 3.10 '2014-02-03T13:22:42-3.00' não informar para NFCe
                    $dVenc = $dh->format('Y-m-d');
                    $vDup = number_format($nfd->valor, 2, '.', '');
                    $resp = $make->tagdup($nDup, $dVenc, $vDup);
                }
            } else {
                //TODO: Trazer informação do tipo de pagamento do negocio
                $tPag = (sizeof($nf->NotaFiscalDuplicatass) > 0) ? '05' : '01'; //01=Dinheiro 02=Cheque 03=Cartão de Crédito 04=Cartão de Débito 05=Crédito Loja 10=Vale Alimentação 11=Vale Refeição 12=Vale Presente 13=Vale Combustível 99=Outros
                $vPag = number_format($nf->valortotal, 2, '.', '');
                $resp = $make->tagpag($tPag, $vPag);
            }

            //*************************************************************
            //Grupo obrigatório para a NFC-e. Não informar para a NF-e.
            //$tPag = '03'; //01=Dinheiro 02=Cheque 03=Cartão de Crédito 04=Cartão de Débito 05=Crédito Loja 10=Vale Alimentação 11=Vale Refeição 12=Vale Presente 13=Vale Combustível 99=Outros
            //$vPag = '1452,33';
            //$resp = $make->tagpag($tPag, $vPag);
            //se a operação for com cartão de crédito essa informação é obrigatória
            //$cnpj = '31551765000143'; //cnpj da operadora de cartão
            //$tBand = '01'; //01=Visa 02=Mastercard 03=American Express 04=Sorocred 99=Outros
            //$cAut = 'AB254FC79001'; //número da autorização da tranzação
            //$resp = $make->tagcard($cnpj, $tBand, $cAut);
            //**************************************************************
            //informações Adicionais
            //informações Adicionais
            $infAdFisco = '';
            $infCpl = '';

            // Mensagem IBPT
            if ($totalTrib > 0) {
                $totalSemTributos = $nf->valortotal - $totalTrib;
                $infCpl = "Voce pagou aproximadamente:;";
                if ($totalTribFederal > 0)
                    $infCpl .= "R$ " . Yii::app()->format->formatNumber($totalTribFederal) . " de tributos federais;";
                if ($totalTribEstadual > 0)
                    $infCpl .= "R$ " . Yii::app()->format->formatNumber($totalTribEstadual) . " de tributos estaduais;";
                if ($totalTribMunicipal > 0)
                    $infCpl .= "R$ " . Yii::app()->format->formatNumber($totalTribMunicipal) . " de tributos municipais;";
                $infCpl .= "R$ " . Yii::app()->format->formatNumber($totalSemTributos) . " pelos produtos;";
                $infCpl .= "Fonte: $ibptFonte;;";
            }
            $infCpl .= Yii::app()->format->removeAcentos($nf->observacoes);
            $infCpl = str_replace("\r\n", "\n", $infCpl);

            // MENSAGEM Aproveitamento ICMS
            $infCpl = str_replace("#ICMSVALOR#", Yii::app()->format->formatNumber($nf->icmsvalor), $infCpl);
            if ($nf->icmsbase > 0 && $nf->icmsvalor > 0)
                $perc = ($nf->icmsvalor / $nf->icmsbase) * 100;
            else
                $perc = 0;
            $infCpl = str_replace("#ICMSPERCENTUAL#", Yii::app()->format->formatNumber($perc), $infCpl);

            $infCpl = utf8_encode($infCpl);

            $resp = $make->taginfAdic($infAdFisco, $infCpl);

            //observações emitente
            //$aObsC = array(
            //    array('email','roberto@x.com.br'),
            //    array('email','rodrigo@y.com.br'),
            //    array('email','rogerio@w.com.br'));
            //foreach ($aObsC as $obs) {
            //    $xCampo = $obs[0];
            //    $xTexto = $obs[1];
            //    $resp = $make->tagobsCont($xCampo, $xTexto);
            //}
            //observações fisco
            //$aObsF = array(
            //    array('email','roberto@x.com.br'),
            //    array('email','rodrigo@y.com.br'),
            //    array('email','rogerio@w.com.br'));
            //foreach ($aObsF as $obs) {
            //    $xCampo = $obs[0];
            //    $xTexto = $obs[1];
            //    //$resp = $make->tagobsFisco($xCampo, $xTexto);
            //}
            //Dados do processo
            //0=SEFAZ; 1=Justiça Federal; 2=Justiça Estadual; 3=Secex/RFB; 9=Outros
            //$aProcRef = array(
            //    array('nProc1','0'),
            //    array('nProc2','1'),
            //    array('nProc3','2'),
            //    array('nProc4','3'),
            //    array('nProc5','9')
            //);
            //foreach ($aProcRef as $proc) {
            //    $nProc = $proc[0];
            //    $indProc = $proc[1];
            //    //$resp = $make->tagprocRef($nProc, $indProc);
            //}
            //dados exportação
            //$UFSaidaPais = 'SP';
            //$xLocExporta = 'Maritimo';
            //$xLocDespacho = 'Porto Santos';
            //$resp = $make->tagexporta($UFSaidaPais, $xLocExporta, $xLocDespacho);
            //dados de compras
            //$xNEmp = '';
            //$xPed = '12345';
            //$xCont = 'A342212';
            //$resp = $make->tagcompra($xNEmp, $xPed, $xCont);
            //dados da colheita de cana
            //$safra = '2014';
            //$ref = '01/2014';
            //$resp = $make->tagcana($safra, $ref);
            //$aForDia = array(
            //    array('1', '100', '1400', '1000', '1400'),
            //    array('2', '100', '1400', '1000', '1400'),
            //    array('3', '100', '1400', '1000', '1400'),
            //    array('4', '100', '1400', '1000', '1400'),
            //    array('5', '100', '1400', '1000', '1400'),
            //    array('6', '100', '1400', '1000', '1400'),
            //    array('7', '100', '1400', '1000', '1400'),
            //    array('8', '100', '1400', '1000', '1400'),
            //    array('9', '100', '1400', '1000', '1400'),
            //    array('10', '100', '1400', '1000', '1400'),
            //    array('11', '100', '1400', '1000', '1400'),
            //    array('12', '100', '1400', '1000', '1400'),
            //    array('13', '100', '1400', '1000', '1400'),
            ///    array('14', '100', '1400', '1000', '1400')
            //);
            //foreach ($aForDia as $forDia) {
            //    $dia = $forDia[0];
            //    $qtde = $forDia[1];
            //    $qTotMes = $forDia[2];
            //    $qTotAnt = $forDia[3];
            //    $qTotGer = $forDia[4];
            //    //$resp = $make->tagforDia($dia, $qtde, $qTotMes, $qTotAnt, $qTotGer);
            //}
            //monta a NFe e retorna na tela
            $resp = $make->montaNFe();
            $xml = $make->getXML();

            //die($xml);
            //@file_put_contents('/tmp/nota.xml', $xml);

            $config = $this->montarConfiguracao($nf->codfilial, $nf->modelo, $codnotafiscal);
            $tools = new ToolsNFe($config);
            $xml = $tools->assina($xml);

            if (!$tools->validarXml($xml)) {
                $erros = "Erro ao Validar XML:\n\n";
                foreach ($tools->errors as $err) {
                    $erro = implode($err, "\n");
                    $erros .= $erro . "\n";
                }

                throw new Exception($erros);
            }

            if (!$ret = @file_put_contents($this->arquivoXMLValidada, $xml))
                throw new Exception("Erro ao Salvar Arquivo XML {$this->arquivoXMLValidada}!");

            $aRetorno['retorno'] = true;
        } catch (Exception $ex) {
            $aRetorno['ex'] = $ex->getMessage();
        }

        $aRetorno['cStat'] = isset($aResposta['cStat']) ? $aResposta['cStat'] : null;
        $aRetorno['xMotivo'] = isset($aResposta['xMotivo']) ? $aResposta['xMotivo'] : null;
        $aRetorno['aResposta'] = null;

        header('Content-type: text/json; charset=UTF-8');
        echo json_encode($aRetorno);
    }

    public function actionEnviar($codnotafiscal) {
        $nf = $this->loadModelNotaFiscal($codnotafiscal);

        $config = $this->montarConfiguracao($nf->codfilial, $nf->modelo, $codnotafiscal);
        $tools = new ToolsNFe($config);

        $tools->setModelo($nf->modelo);

        $aResposta = array();
        $chave = $nf->nfechave;
        $tpAmb = $nf->Filial->nfeambiente;

        $aRetorno['retorno'] = false;
        $aRetorno['ex'] = null;

        try {

            if (!$nf->emitida)
                throw new Exception('Nota fiscal não é de nossa emissão!');

            if (empty($nf->nfechave))
                throw new Exception('Chave da Nota Fiscal não informada! Verifique se o arquivo XML já foi gerado!');

            switch ($nf->codstatus) {
                case NotaFiscal::CODSTATUS_NOVA:
                case NotaFiscal::CODSTATUS_DIGITACAO:
                case NotaFiscal::CODSTATUS_NOSSA_EMISSAO:
                case NotaFiscal::CODSTATUS_NAOAUTORIZADA:
                    break;

                case NotaFiscal::CODSTATUS_AUTORIZADA:
                case NotaFiscal::CODSTATUS_LANCADA:
                case NotaFiscal::CODSTATUS_INUTILIZADA:
                case NotaFiscal::CODSTATUS_CANCELADA:
                    throw new Exception('Status da Nota Fiscal não permite esta ação!');
                    break;
            }

            if (!file_exists($this->arquivoXMLValidada))
                throw new Exception("Arquivo Inexistente ({$this->arquivoXMLValidada})! Arquivo XML ainda nao foi gerado!");

            $aXml = file_get_contents($this->arquivoXMLValidada);
            $idLote = '';
            $indSinc = '0';
            $flagZip = false;
            $retorno = $tools->sefazEnviaLote($aXml, $tpAmb, $idLote, $aResposta, $indSinc, $flagZip);

            if (!$aResposta['bStat'])
                throw new Exception('Erro na comunicacao!');

            if ($aResposta['cStat'] == 103) { // Lote Recebido com Sucesso
                $nf->nfereciboenvio = $aResposta['nRec'];
                $dh = DateTime::createFromFormat('Y-m-d\TH:i:sP', $aResposta['dhRecbto']);
                $nf->nfedataenvio = $dh->format('d/m/Y H:i:s');
                $nf->save();
                $aRetorno['retorno'] = true;
            }
        } catch (Exception $ex) {
            $aRetorno['ex'] = $ex->getMessage();
        }

        $aRetorno['cStat'] = isset($aResposta['cStat']) ? $aResposta['cStat'] : null;
        $aRetorno['xMotivo'] = isset($aResposta['xMotivo']) ? $aResposta['xMotivo'] : null;
        $aRetorno['aResposta'] = $aResposta;

        header('Content-type: text/json; charset=UTF-8');
        echo json_encode($aRetorno);
    }

    public function actionConsultarProtocolo($codnotafiscal, $protocolo = '') {
        $nf = $this->loadModelNotaFiscal($codnotafiscal);

        if (empty($protocolo))
            $protocolo = $nf->nfereciboenvio;

        $config = $this->montarConfiguracao($nf->codfilial, $nf->modelo, $codnotafiscal);
        $tools = new ToolsNFe($config);
        $tools->setModelo($nf->modelo);
        $tpAmb = $nf->Filial->nfeambiente;

        $aRetorno['retorno'] = false;
        $aRetorno['ex'] = null;

        $aResposta = array();

        try {
            if (!$nf->emitida)
                throw new Exception('Nota fiscal não é de nossa emissão!');

            if (empty($nf->nfereciboenvio))
                throw new Exception('Nota Fiscal não tem número do Recibo de Envio!');

            switch ($nf->codstatus) {
                case NotaFiscal::CODSTATUS_NOVA:
                case NotaFiscal::CODSTATUS_DIGITACAO:
                case NotaFiscal::CODSTATUS_NOSSA_EMISSAO:
                case NotaFiscal::CODSTATUS_NAOAUTORIZADA:
                case NotaFiscal::CODSTATUS_INUTILIZADA:
                case NotaFiscal::CODSTATUS_CANCELADA:
                case NotaFiscal::CODSTATUS_AUTORIZADA:
                    break;

                case NotaFiscal::CODSTATUS_LANCADA:
                    throw new Exception('Status da Nota Fiscal não permite esta ação!');
                    break;
            }

            $retorno = $tools->sefazConsultaRecibo($protocolo, $tpAmb, $aResposta);

            if (!$aResposta['bStat'])
                throw new Exception('Erro na comunicacao!');

            if (!isset($aResposta['aProt'][0]))
                throw new Exception('Erro na comunicacao!');

            if (($aResposta['aProt'][0]['cStat'] == 100) // Autorizado o uso da NF-e
                || ($aResposta['aProt'][0]['cStat'] == 150)) { // Autorizado o uso da NF-e, autorizacao fora de prazo
                $saveFile = true;
                $retorno = $tools->addProtocolo($this->arquivoXMLValidada, $this->arquivoXMLProtocoloRecibo, $saveFile);

                $nf->nfeautorizacao = $aResposta['aProt'][0]['nProt'];
                $dh = DateTime::createFromFormat('Y-m-d\TH:i:sP', $aResposta['aProt'][0]['dhRecbto']);
                $nf->nfedataautorizacao = $dh->format('d/m/Y H:i:s');
                $nf->save();

                $aRetorno['retorno'] = true;
            }
        } catch (Exception $ex) {
            $aRetorno['ex'] = $ex->getMessage();
        }

        $aRetorno['cStat'] = isset($aResposta['aProt'][0]['cStat']) ? $aResposta['aProt'][0]['cStat'] : null;
        $aRetorno['xMotivo'] = isset($aResposta['aProt'][0]['xMotivo']) ? $aResposta['aProt'][0]['xMotivo'] : null;
        $aRetorno['aResposta'] = $aResposta;

        header('Content-type: text/json; charset=UTF-8');
        echo json_encode($aRetorno);
    }

    public function gerarDanfe($codnotafiscal) {
        $nf = $this->loadModelNotaFiscal($codnotafiscal);
        $config = $this->montarConfiguracao($nf->codfilial, $nf->modelo, $codnotafiscal);

        if ((!is_file($this->arquivoXMLAprovada)) && ($nf->tpemis == NotaFiscal::TPEMIS_OFFLINE))
            $xml = FilesFolders::readFile($this->arquivoXMLValidada);
        else
            $xml = FilesFolders::readFile($this->arquivoXMLAprovada);

        if ($nf->modelo == NotaFiscal::MODELO_NFCE) {
            $saida = isset($_REQUEST['o']) ? $_REQUEST['o'] : 'pdf';
            $danfe = new Danfce($xml, '/var/www/MGsis/images/MGPapelariaSeloSloganPretoBranco.jpg', 2);
            $id = $danfe->montaDANFE(true);
            $teste = $danfe->printDANFE($saida, $this->arquivoPDF, 'F');
            return true;
        } else {
            //$danfe = new Danfe($xml, 'P', 'A4', '/var/www/MGsis/images/MGPapelariaSloganPretoBranco.jpg', 'I', '');
            $danfe = new Danfe($xml, 'P', 'A4', '', 'I', '');
            $id = $danfe->montaDANFE();
            $teste = $danfe->printDANFE($this->arquivoPDF, 'F');
            return true;
        }

        return false;
    }

    public function actionGerarDanfe($codnotafiscal) {
        //echo 'actionGerarDanfe<hr>';

        $nf = $this->loadModelNotaFiscal($codnotafiscal);
        $config = $this->montarConfiguracao($nf->codfilial, $nf->modelo, $codnotafiscal);

        try {

            switch ($nf->codstatus) {
                case NotaFiscal::CODSTATUS_AUTORIZADA:
                    break;

                case NotaFiscal::CODSTATUS_LANCADA:
                case NotaFiscal::CODSTATUS_NOVA:
                case NotaFiscal::CODSTATUS_DIGITACAO:
                case NotaFiscal::CODSTATUS_NOSSA_EMISSAO:
                case NotaFiscal::CODSTATUS_INUTILIZADA:
                case NotaFiscal::CODSTATUS_CANCELADA:
                    throw new Exception('Status da Nota Fiscal não permite esta ação!');

                case NotaFiscal::CODSTATUS_NAOAUTORIZADA:
                    if ($nf->tpemis != NotaFiscal::TPEMIS_OFFLINE)
                        throw new Exception('Status da Nota Fiscal não permite esta ação!');
                    break;
            }

            $arquivopdf = $this->gerarDanfe($codnotafiscal);

            header('Content-type: application/pdf');
            header('Content-Disposition: inline; filename="' . basename($this->arquivoPDF) . '"');

            readfile($this->arquivoPDF);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }

    public function actionCancelar($codnotafiscal, $justificativa) {
        $nf = $this->loadModelNotaFiscal($codnotafiscal);
        $config = $this->montarConfiguracao($nf->codfilial, $nf->modelo, $codnotafiscal);

        $tools = new ToolsNFe($config);

        $tools->setModelo($nf->modelo);

        if (is_file($this->arquivoXMLProtocoloCancelamento) && is_file($this->arquivoXMLAprovada)) {
            $saveFile = true;
            $retorno = $tools->addCancelamento($this->arquivoXMLAprovada, $this->arquivoXMLProtocoloCancelamento, $saveFile);
        }

        $aResposta = array();

        $chave = $nf->nfechave;
        $nProt = $nf->nfeautorizacao;
        $tpAmb = $nf->Filial->nfeambiente;
        $xJust = $justificativa;

        $aRetorno['retorno'] = false;
        $aRetorno['ex'] = null;

        try {
            if (!$nf->emitida)
                throw new Exception('Nota fiscal não é de nossa emissão!');

            switch ($nf->codstatus) {
                case NotaFiscal::CODSTATUS_AUTORIZADA:
                    break;

                case NotaFiscal::CODSTATUS_LANCADA:
                case NotaFiscal::CODSTATUS_NOVA:
                case NotaFiscal::CODSTATUS_DIGITACAO:
                case NotaFiscal::CODSTATUS_NOSSA_EMISSAO:
                case NotaFiscal::CODSTATUS_NAOAUTORIZADA:
                case NotaFiscal::CODSTATUS_INUTILIZADA:
                case NotaFiscal::CODSTATUS_CANCELADA:
                    throw new Exception('Status da Nota Fiscal não permite esta ação!');
                    break;
            }


            $retorno = $tools->sefazCancela($chave, $tpAmb, $xJust, $nProt, $aResposta);

            if (!$aResposta['bStat'])
                throw new Exception('Erro na comunicacao!');

            if ($aResposta['evento'][0]['cStat'] == 135) {
                $nf->nfecancelamento = $aResposta['evento'][0]['nProt'];
                $dh = DateTime::createFromFormat('Y-m-d\TH:i:sP', $aResposta['evento'][0]['dhRegEvento']);
                $nf->nfedatacancelamento = $dh->format('d/m/Y H:i:s');
                $nf->justificativa = $justificativa;
                $nf->save();

                if (is_file($this->arquivoXMLProtocoloCancelamento) && is_file($this->arquivoXMLAprovada)) {
                    $saveFile = true;
                    $retorno = $tools->addCancelamento($this->arquivoXMLAprovada, $this->arquivoXMLProtocoloCancelamento, $saveFile);
                }

                $aRetorno['retorno'] = true;
            }
        } catch (Exception $ex) {
            $aRetorno['ex'] = $ex->getMessage();
        }

        $aRetorno['cStat'] = isset($aResposta['evento'][0]['cStat']) ? $aResposta['evento'][0]['cStat'] : null;
        $aRetorno['xMotivo'] = isset($aResposta['evento'][0]['xMotivo']) ? $aResposta['evento'][0]['xMotivo'] : null;
        $aRetorno['aResposta'] = $aResposta;

        header('Content-type: text/json; charset=UTF-8');
        echo json_encode($aRetorno);
    }

    public function actionInutilizar($codnotafiscal, $justificativa) {
        $nf = $this->loadModelNotaFiscal($codnotafiscal);
        $config = $this->montarConfiguracao($nf->codfilial, $nf->modelo, $codnotafiscal);

        $tools = new ToolsNFe($config);

        $tools->setModelo($nf->modelo);

        $aResposta = array();
        $nSerie = $nf->serie;
        $nIni = $nf->numero;
        $nFin = $nf->numero;
        $xJust = $justificativa;
        $tpAmb = $nf->Filial->nfeambiente;

        $aRetorno['retorno'] = false;
        $aRetorno['ex'] = null;

        try {
            if (!$nf->emitida)
                throw new Exception('Nota fiscal não é de nossa emissão!');

            switch ($nf->codstatus) {
                case NotaFiscal::CODSTATUS_DIGITACAO:
                case NotaFiscal::CODSTATUS_NAOAUTORIZADA:
                case NotaFiscal::CODSTATUS_NOSSA_EMISSAO:
                case NotaFiscal::CODSTATUS_NOVA:
                    break;

                case NotaFiscal::CODSTATUS_LANCADA:
                case NotaFiscal::CODSTATUS_AUTORIZADA:
                case NotaFiscal::CODSTATUS_INUTILIZADA:
                case NotaFiscal::CODSTATUS_CANCELADA:
                    throw new Exception('Status da Nota Fiscal não permite esta ação!');
                    break;
            }

            $xml = $tools->sefazInutiliza($nSerie, $nIni, $nFin, $xJust, $tpAmb, $aResposta);

            if (!$aResposta['bStat'])
                throw new Exception('Erro na comunicacao!');

            if ($aResposta['cStat'] == 102) {
                $nf->nfeinutilizacao = $aResposta['nProt'];
                $dh = DateTime::createFromFormat('Y-m-d\TH:i:sP', $aResposta['dhRecbto']);
                $nf->nfedatainutilizacao = $dh->format('d/m/Y H:i:s');
                $nf->justificativa = $justificativa;
                $nf->save();
                $aRetorno['retorno'] = true;
            }
        } catch (Exception $ex) {
            $aRetorno['ex'] = $ex->getMessage();
        }

        $aRetorno['cStat'] = isset($aResposta['cStat']) ? $aResposta['cStat'] : null;
        $aRetorno['xMotivo'] = isset($aResposta['xMotivo']) ? $aResposta['xMotivo'] : null;
        $aRetorno['aResposta'] = $aResposta;

        header('Content-type: text/json; charset=UTF-8');
        echo json_encode($aRetorno);
    }

    public function actionConsultar($codnotafiscal) {
        $nf = $this->loadModelNotaFiscal($codnotafiscal);
        $config = $this->montarConfiguracao($nf->codfilial, $nf->modelo, $codnotafiscal);

        $tools = new ToolsNFe($config);

        $tools->setModelo($nf->modelo);
        $chave = $nf->nfechave;
        $tpAmb = $nf->Filial->nfeambiente;
        $aResposta = array();

        $aRetorno['retorno'] = false;
        $aRetorno['ex'] = null;

        try {

            if (!$nf->emitida)
                throw new Exception('Nota fiscal não é de nossa emissão!');

            if (empty($nf->nfechave))
                throw new Exception('Nota Fiscal não possui chave!');

            switch ($nf->codstatus) {
                case NotaFiscal::CODSTATUS_AUTORIZADA:
                case NotaFiscal::CODSTATUS_CANCELADA:
                case NotaFiscal::CODSTATUS_DIGITACAO:
                case NotaFiscal::CODSTATUS_NAOAUTORIZADA:
                case NotaFiscal::CODSTATUS_NOSSA_EMISSAO:
                case NotaFiscal::CODSTATUS_NOVA:
                case NotaFiscal::CODSTATUS_INUTILIZADA:
                    break;

                case NotaFiscal::CODSTATUS_LANCADA:
                    throw new Exception('Status da Nota Fiscal não permite esta ação!');
                    break;
            }

            $xml = $tools->sefazConsultaChave($chave, $tpAmb, $aResposta);

            if ($aResposta['cStat'] == 109)
                throw new Exception('Serviço Paralisado sem Previsão!');

            if (!$aResposta['bStat'])
                throw new Exception('Erro na comunicacao!');

            if ((@$aResposta['aProt']['cStat'] == 100) || (@$aResposta['aProt']['cStat'] == 150)) {
                $nf->nfeautorizacao = $aResposta['aProt']['nProt'];
                $dh = DateTime::createFromFormat('Y-m-d\TH:i:sP', $aResposta['aProt']['dhRecbto']);
                $nf->nfedataautorizacao = $dh->format('d/m/Y H:i:s');
                $nf->save();

                if (is_file($this->arquivoXMLProtocoloSituacao) && is_file($this->arquivoXMLValidada)) {
                    $saveFile = true;
                    $retorno = $tools->addProtocolo($this->arquivoXMLValidada, $this->arquivoXMLProtocoloSituacao, $saveFile);
                }
            }

            if (@$aResposta['aProt']['cStat'] == 302) { // Uso Denegado
                $nf->nfeinutilizacao = $aResposta['aProt']['nProt'];
                $dh = DateTime::createFromFormat('Y-m-d\TH:i:sP', $aResposta['aProt']['dhRecbto']);
                $nf->nfedatainutilizacao = $dh->format('d/m/Y H:i:s');
                $nf->justificativa = $aResposta['aProt']['xMotivo'];
                $nf->save();

                if (is_file($this->arquivoXMLProtocoloSituacao) && is_file($this->arquivoXMLValidada)) {
                    $saveFile = true;
                    $retorno = $tools->addProtocolo($this->arquivoXMLValidada, $this->arquivoXMLProtocoloSituacao, $saveFile);
                    if (!@file_put_contents($this->arquivoXMLDenegada, $retorno))
                        throw new Exception('Erro ao salvar Arquivo XML Denegada!');
                }
            }

            foreach ($aResposta['aEvent'] as $aEvent) {
                foreach ($aEvent as $aEventB) {
                    if ($aEventB['tpEvento'] == 110111) {
                        $nf->nfecancelamento = $aEventB['nProt'];
                        $dh = DateTime::createFromFormat('Y-m-d\TH:i:sP', $aEventB['dhRegEvento']);
                        $nf->nfedatacancelamento = $dh->format('d/m/Y H:i:s');
                        $nf->save();

                        if (is_file($this->arquivoXMLProtocoloSituacao) && is_file($this->arquivoXMLAprovada)) {
                            $saveFile = true;
                            $retorno = $tools->addCancelamento($this->arquivoXMLAprovada, $this->arquivoXMLProtocoloSituacao, $saveFile);
                        }
                    }
                }
            }

            //retorno já esta sendo dado se foi denegada/autorizada
            $aRetorno['retorno'] = true;
        } catch (Exception $ex) {
            $aRetorno['ex'] = $ex->getMessage();
        }

        $aRetorno['cStat'] = isset($aResposta['cStat']) ? $aResposta['cStat'] : null;
        $aRetorno['xMotivo'] = isset($aResposta['xMotivo']) ? $aResposta['xMotivo'] : null;
        $aRetorno['aResposta'] = $aResposta;

        header('Content-type: text/json; charset=UTF-8');
        echo json_encode($aRetorno);
    }

    public function actionCartaCorrecao($codnotafiscal, $texto) {

        $nf = $this->loadModelNotaFiscal($codnotafiscal);
        $config = $this->montarConfiguracao($nf->codfilial, $nf->modelo, $codnotafiscal);

        $tools = new ToolsNFe($config);

        $tools->setModelo($nf->modelo);

        $lote = 0;
        $sequencia = 0;

        foreach ($nf->NotaFiscalCartaCorrecaos as $cc) {
            if ($cc->lote > $lote)
                $lote = $cc->lote;
            if ($cc->sequencia > $sequencia)
                $sequencia = $cc->sequencia;
        }
        $lote++;
        $sequencia++;


        $aResposta = array();
        $chNFe = $nf->nfechave;
        $tpAmb = $nf->Filial->nfeambiente;
        $xCorrecao = $texto;
        $nSeqEvento = $sequencia;

        $aRetorno['retorno'] = false;
        $aRetorno['ex'] = null;

        try {

            if (!$nf->emitida)
                throw new Exception('Nota fiscal não é de nossa emissão!');

            if ($nf->modelo != NotaFiscal::MODELO_NFE)
                throw new Exception('Modelo da Nota Fiscal não permite carta de correção!');

            switch ($nf->codstatus) {
                case NotaFiscal::CODSTATUS_AUTORIZADA:
                case NotaFiscal::CODSTATUS_NOSSA_EMISSAO:
                    break;

                case NotaFiscal::CODSTATUS_LANCADA:
                case NotaFiscal::CODSTATUS_INUTILIZADA:
                case NotaFiscal::CODSTATUS_CANCELADA:
                case NotaFiscal::CODSTATUS_DIGITACAO:
                case NotaFiscal::CODSTATUS_NAOAUTORIZADA:
                case NotaFiscal::CODSTATUS_NOVA:
                    throw new Exception('Status da Nota Fiscal não permite esta ação!');
                    break;
            }

            $retorno = $tools->sefazCCe($chNFe, $tpAmb, $xCorrecao, $nSeqEvento, $aResposta);

            if (!$aResposta['bStat'])
                throw new Exception('Erro na comunicacao!');

            if ($aResposta['evento'][0]['cStat'] == 135) {
                $nfcc = new NotaFiscalCartaCorrecao();
                $nfcc->codnotafiscal = $nf->codnotafiscal;
                $nfcc->texto = $texto;
                $nfcc->protocolo = $aResposta['evento'][0]['nProt'];
                $dh = DateTime::createFromFormat('Y-m-d\TH:i:sP', $aResposta['evento'][0]['dhRegEvento']);
                $nfcc->protocolodata = $dh->format('d/m/Y H:i:s');
                $nfcc->sequencia = $sequencia;
                $nfcc->lote = $lote;
                $nfcc->data = $nfcc->protocolodata;
                $nfcc->save();

                $aRetorno['retorno'] = true;
            }
        } catch (Exception $ex) {
            $aRetorno['ex'] = $ex->getMessage();
        }

        $aRetorno['cStat'] = isset($aResposta['evento'][0]['cStat']) ? $aResposta['evento'][0]['cStat'] : null;
        $aRetorno['xMotivo'] = isset($aResposta['evento'][0]['xMotivo']) ? $aResposta['evento'][0]['xMotivo'] : null;
        $aRetorno['aResposta'] = $aResposta;

        header('Content-type: text/json; charset=UTF-8');
        echo json_encode($aRetorno);
    }

    public function actionRobo($codfilial = 0) {
        echo 'Action Robo';
    }

    public function actionEnviarEmail($codnotafiscal, $email = "", $alterarcadastro = 0) {
        $nf = $this->loadModelNotaFiscal($codnotafiscal);
        $config = $this->montarConfiguracao($nf->codfilial, $nf->modelo, $codnotafiscal);

        require 'PHPMailerAutoload.php';

        $aRetorno['retorno'] = false;
        $aRetorno['cStat'] = null;
        $aRetorno['xMotivo'] = null;
        $aRetorno['aResposta'] = null;

        try {

            //verifica se arquivo xml esta na pasta das aprovadas
            if (!is_file($this->arquivoXMLAprovada))
                throw new Exception("Arquivo XML não existe: {$this->arquivoXMLAprovada}");

            //gera o pdf se nao existir
            if (!is_file($this->arquivoPDF))
                $this->gerarDanfe($codnotafiscal);


            //instancia classe de envio de email
            $mail = new PHPMailer;

            //configura servidor de email
            $mail->isSMTP();                                      // Set mailer to use SMTP
            $mail->Host = 'smtp.gmail.com';  // Specify main and backup SMTP servers
            $mail->SMTPAuth = true;                               // Enable SMTP authentication
            $mail->Username = 'nfe@mgpapelaria.com.br';                 // SMTP username
            $mail->Password = '701flamboyants';                           // SMTP password
            $mail->SMTPAutoTLS = false;
            $mail->Helo = 'mail.mgpapelaria.com.br';
            $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
            $mail->Port = 587;                                    // TCP port to connect to
            $mail->setFrom('nfe@mgpapelaria.com.br', 'MG Papelaria - Sitema de NFe');

            //Estas opcoes sao para ignorar o certificado do servidor, que nao eh valido
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            //se nao veio o email pelo parametro, busca do cadastro
            if (empty($email))
                $email = $nf->Pessoa->emailnfe;
            if (empty($email))
                $email = $nf->Pessoa->email;
            if (empty($email))
                $email = $nf->Pessoa->emailcobranca;
            if (empty($email))
                throw new Exception('Nenhum E-mail informado!');


            //altera cadastro da pessoa
            if ($alterarcadastro && $nf->codpessoa != Pessoa::CONSUMIDOR) {
                Yii::app()->db
                    ->createCommand("UPDATE tblPessoa SET emailnfe = :emailnfe WHERE codpessoa=:codpessoa")
                    ->bindValues(array(':emailnfe' => $email, ':codpessoa' => $nf->codpessoa))
                    ->execute();
            }

            //cria um array com os endereços de email
            $email = $email . ";";
            $emails = str_replace(',', ';', $email);
            $emails = explode(';', $emails);
            foreach ($emails as $email)
                $mail->addAddress($email);     // Add a recipient
            $aRetorno['aResposta'] = $emails;

            //Adiciona anexos
            $mail->addAttachment($this->arquivoXMLAprovada);         // Add attachments
            $mail->addAttachment($this->arquivoPDF);         // Add attachments
            $mail->isHTML(false);                                  // Set email format to HTML
            //texto do email
            $mail->Subject = (($nf->modelo == NotaFiscal::MODELO_NFCE) ? 'NFC-e ' : 'NF-e ') .
                $nf->numero . ' - ' .
                $nf->Filial->Pessoa->fantasia
            ;

            $mail->CharSet = 'UTF-8';

            $mail->Body = "Olá,\n\n" .
                "Segue em anexo, o arquivo eletrônico da sua Nota Fiscal devidamente autorizado, além de um arquivo em formato .PDF para fácil visualização.\n\n" .
                "ATENÇÃO : Não responda a esta mensagem, pois se trata de um processo AUTOMÁTICO. Caso queira se comunicar com nossa empresa, responda para mg@mgpapelaria.com.br.\n\n" .
                "Atenciosamente,\n\n" .
                "--\n" .
                "{$nf->Filial->Pessoa->fantasia}\n" .
                "{$nf->Filial->Pessoa->pessoa}\n" .
                "{$nf->Filial->Pessoa->endereco}, {$nf->Filial->Pessoa->numero} - {$nf->Filial->Pessoa->bairro} - {$nf->Filial->Pessoa->Cidade->cidade}/{$nf->Filial->Pessoa->Cidade->Estado->sigla} - Cep {$nf->Filial->Pessoa->cep}\n" .
                "{$nf->Filial->Pessoa->telefone1}\n";

            //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

            if (!$mail->send()) {
                throw new Exception('Erro ao enviar E-mail: ' . $mail->ErrorInfo);
            } else {
                $aRetorno['retorno'] = true;
                $aRetorno['xMotivo'] = 'E-mail enviado!';
            }
        } catch (Exception $ex) {
            $aRetorno['ex'] = $ex->getMessage();
            $aRetorno['xMotivo'] = $ex->getMessage();
        }


        header('Content-type: text/json; charset=UTF-8');
        echo json_encode($aRetorno);
    }

    public function actionImprimirNFCe($codnotafiscal, $impressoraUsuarioCriacao = 0) {

        $nf = $this->loadModelNotaFiscal($codnotafiscal);
        $config = $this->montarConfiguracao($nf->codfilial, $nf->modelo, $codnotafiscal);
        $aRetorno['retorno'] = false;

        if (!is_file($this->arquivoPDF))
            $this->gerarDanfe($codnotafiscal);

        if ($impressoraUsuarioCriacao)
            $impressora = $nf->UsuarioCriacao->impressoratermica;
        else
            $impressora = Yii::app()->user->impressoraTermica;

        $cmd = "lpr -P $impressora {$this->arquivoPDF};";
        $aRetorno['xMotivo'] = $cmd;
        $aRetorno['ex'] = exec($cmd);
        if (empty($aRetorno['ex'])) {
            $aRetorno['retorno'] = true;
        }

        header('Content-type: text/json; charset=UTF-8');
        echo json_encode($aRetorno);
    }

    public function actionView() {
        $this->layout = '//layouts/quiosque';
        $this->render('view');
    }

    public function actionListagemNotasPendentes() {

        $notas = NotaFiscal::model()->pendentes()->findAll();

        $listagem = array();
        foreach ($notas as $nota) {
            $arr['codnotafiscal'] = $nota->codnotafiscal;
            $arr['modelo'] = $nota->modelo;
            $arr['numero'] = $nota->numero;
            $arr['serie'] = $nota->serie;
            $arr['codfilial'] = $nota->codfilial;
            $arr['filial'] = $nota->Filial->filial;
            $arr['tpEmis'] = $nota->tpemis;
            $arr['emissao'] = $nota->emissao;
            $arr['nfedataenvio'] = $nota->nfedataenvio;
            $arr['usuario'] = $nota->UsuarioCriacao->usuario;
            $listagem[$nota->codnotafiscal] = $arr;
        }

        header('Content-type: text/json; charset=UTF-8');
        echo json_encode($listagem);
    }

    public function actionSefazStatus($codfilial) {
        $filial = $this->loadModelFilial($codfilial);
        $config = $this->montarConfiguracao($codfilial, '65');

        $tools = new ToolsNFe($config);

        $aRetorno = array();

        $tools->sefazStatus($filial->Pessoa->Cidade->Estado->sigla, $filial->nfeambiente, $aRetorno);

        header('Content-type: text/json; charset=UTF-8');
        echo json_encode($aRetorno);
    }

    public function actionSefazDistDFe($codfilial, $nsu = 0) {
        $filial = $this->loadModelFilial($codfilial);
        $config = $this->montarConfiguracao($codfilial, '65');

        $tools = new ToolsNFe($config);

        $aRetorno = array();
        $importadas = array();
        $aRetorno['retorno'] = false;

        try {
            $tools->sefazDistDFe(
                $fonte = 'AN', $tpAmb = $filial->nfeambiente, $cnpj = str_pad($filial->Pessoa->cnpj, 14, '0', STR_PAD_LEFT), $ultNSU = $nsu, $numNSU = 0, $aRetorno
            );

            foreach ($aRetorno['aDoc'] as $doc) {
                if (substr($doc['schema'], 0, 6) != 'resNFe')
                    continue;

                $sXml = new \SimpleXMLElement($doc['doc']);
                $chave = $sXml->chNFe->__toString();

                file_put_contents("/tmp/NSU-[{$doc['NSU']}].xml", $doc['doc']);

                if (empty($chave))
                    continue;

                $nfe = NfeTerceiro::model()->find("nfechave = :nfechave", array("nfechave" => $chave));

                if ($nfe === null)
                    $nfe = new NfeTerceiro();

                $nfe->codfilial = $codfilial;
                $nfe->nsu = @$doc['NSU'];
                $nfe->nfechave = $chave;
                $nfe->cnpj = @$sXml->CNPJ->__toString();
                $nfe->emitente = @Yii::app()->format->removeAcentos(utf8_encode($sXml->xNome->__toString()));
                if (empty($nfe->emitente))
                    $nfe->emitente = '<Vazio>';
                $nfe->ie = @$sXml->IE->__toString();
                $dh = DateTime::createFromFormat('Y-m-d\TH:i:sP', $sXml->dhEmi->__toString()); //  AAAA-MM-DDThh:mm:ssTZD
                $nfe->emissao = @$dh->format('d/m/Y H:i:s');
                $nfe->codoperacao = @$sXml->tpNF->__toString() + 1;
                $nfe->valortotal = @Yii::app()->format->unformatNumber($sXml->vNF->__toString());
                $dh = DateTime::createFromFormat('Y-m-d\TH:i:sP', $sXml->dhRecbto->__toString()); //  AAAA-MM-DDThh:mm:ssTZD
                $nfe->nfedataautorizacao = @$dh->format('d/m/Y H:i:s');
                $nfe->indsituacao = @$sXml->cSitNFe->__toString();
                //$nfe->indmanifestacao = $sXml->cSitConf;
                if (!$nfe->save())
                    throw new Exception("Erro ao salvar NF-e de Terceiros:\n\n" . print_r($nfe->getErrors(), true));

                $importadas[$chave] = $nfe->codnfeterceiro;
            }

            $filial->ultimonsu = $aRetorno['ultNSU'];
            $filial->save();

            $aRetorno['retorno'] = true;
        } catch (Exception $ex) {
            $aRetorno['ex'] = $ex->getMessage();
        }

        $aRetorno['importadas'] = $importadas;

        header('Content-type: text/json; charset=UTF-8');
        echo json_encode($aRetorno);
    }

    public function actionIBPT($codfilial, $ncm, $exTarif = 0) {

        $filial = $this->loadModelFilial($codfilial);
        $config = $this->montarConfiguracao($codfilial, '55');

        $tools = new ToolsNFe($config);
        $tools->setModelo('55');

        $siglaUF = $filial->Pessoa->Cidade->Estado->sigla;
        //$ncm = '60063100';
        //$exTarif = '0';
        //$siglaUF = 'SP';

        $resp = $tools->getImpostosIBPT($ncm, $exTarif, $siglaUF);
        header('Content-type: text/json; charset=UTF-8');
        echo json_encode($resp);
    }

    public function actionManifesta($codnfeterceiro, $indmanifestacao, $justificativa = '') {
        $nfet = $this->loadModelNfeTerceiro($codnfeterceiro);
        $config = $this->montarConfiguracao($nfet->codfilial, '55');

        $aRetorno = array();
        $aRetorno['retorno'] = false;
        $aRetorno['ex'] = null;

        try {

            $tools = new ToolsNFe($config);
            $tools->setModelo('55');
            //210200 – Confirmação da Operação
            //210210 – Ciência da Operação
            //210220 – Desconhecimento da Operação
            //210240 – Operação não Realizada ===> é obritatoria uma justificativa para esse caso
            $chave = $nfet->nfechave;
            $tpAmb = $nfet->Filial->nfeambiente;
            $xJust = $justificativa;
            $tpEvento = $indmanifestacao;
            $aResposta = array();
            $xml = $tools->sefazManifesta($chave, $tpAmb, $xJust, $tpEvento, $aResposta);

            switch ($aResposta['evento'][0]['cStat']) {
                case 135: //Evento registrado e vinculado a NF-e
                    $aRetorno['retorno'] = true;
                case 573: //Rejeição: Duplicidade de Evento
                case 136: //Evento registrado, mas nao vinculado a NF-e
                    $nfet->indmanifestacao = $indmanifestacao;
                    $nfet->justificativa = $justificativa;
                    $nfet->save();
                    break;
            }
        } catch (Exception $ex) {
            $aRetorno['ex'] = $ex->getMessage();
        }

        $aRetorno['cStat'] = isset($aResposta['evento'][0]['cStat']) ? $aResposta['evento'][0]['cStat'] : array();
        $aRetorno['xMotivo'] = isset($aResposta['evento'][0]['xMotivo']) ? $aResposta['evento'][0]['xMotivo'] : array();
        $aRetorno['aResposta'] = isset($aResposta) ? $aResposta : array();

        header('Content-type: text/json; charset=UTF-8');
        echo json_encode($aRetorno);
    }

    public function actionDownload($codnfeterceiro) {
        $nfet = $this->loadModelNfeTerceiro($codnfeterceiro);
        $config = $this->montarConfiguracao($nfet->codfilial, '55', false, $codnfeterceiro);

        $aRetorno = array();
        $aRetorno['retorno'] = false;
        $aRetorno['ex'] = null;

        try {
            $tools = new ToolsNFe($config);
            $tools->setModelo('55');
            $chNFe = $nfet->nfechave;
            $tpAmb = $nfet->Filial->nfeambiente;
            $cnpj = str_pad($nfet->Filial->Pessoa->cnpj, 14, '0', STR_PAD_LEFT);
            $aResposta = array();
            $resp = $tools->sefazDownload($chNFe, $tpAmb, $cnpj, $aResposta);

            if ($aResposta['bStat'] && $aResposta['aRetNFe']['cStat'] == 140) { // Download disponibilizado
                $xml = $aResposta['aRetNFe']['nfeProc'];
                file_put_contents($this->arquivoXMLRecebida, $xml);
                $aRetorno['retorno'] = $nfet->importarXml();
            }
        } catch (Exception $ex) {
            $aRetorno['ex'] = $ex->getMessage();
        }

        $aRetorno['cStat'] = isset($aResposta['aRetNFe']['cStat']) ? $aResposta['aRetNFe']['cStat'] : array();
        $aRetorno['xMotivo'] = isset($aResposta['aRetNFe']['xMotivo']) ? $aResposta['aRetNFe']['xMotivo'] : array();
        $aRetorno['aResposta'] = isset($aResposta) ? $aResposta : array();

        header('Content-type: text/json; charset=UTF-8');
        echo json_encode($aRetorno);
    }

    public function actionProcuraXml($diretorio) {
        $aRetorno['retorno'] = false;
        $aRetorno['ex'] = null;
        $aRetorno['aResposta'] = array();

        try {
            $Directory = new RecursiveDirectoryIterator($diretorio);
            $Iterator = new RecursiveIteratorIterator($Directory);
            $Regex = new RegexIterator($Iterator, '/^.+\.XML$/i', RecursiveRegexIterator::GET_MATCH);
            $arquivos = array();
            foreach ($Regex as $key => $arquivo)
                $arquivos[] = $arquivo[0];

            $aRetorno['retorno'] = true;
            $aRetorno['aResposta'] = $arquivos;
            $aRetorno['xMotivo'] = sizeof($arquivos) . ' arquivos localizados!';
        } catch (Exception $ex) {
            $aRetorno['ex'] = $ex->getMessage();
        }

        header('Content-type: text/json; charset=UTF-8');
        echo json_encode($aRetorno);
    }

    public function actionImportaArquivoXml($arquivo) {
        $aRetorno['retorno'] = false;
        $aRetorno['ex'] = null;
        $aRetorno['codnfeterceiro'] = null;

        try {

            if (!is_file($arquivo))
                throw new Exception("Arquivo '{$arquivo}' inexistente!");

            $xmlOriginal = @file_get_contents($arquivo);
            if (empty(trim($xmlOriginal)))
                throw new Exception("Arquivo '{$arquivo}' sem conteúdo!");

            $xmlLimpo = NfeTerceiro::limpaCaracteresEspeciaisXml($xmlOriginal);

            $sXml = @simplexml_load_string($xmlLimpo);
            if (!$sXml)
                throw new Exception("Arquivo '{$arquivo}' não contém um XML válido!");

            if (isset($sXml->NFe->infNFe))
                $infNFe = $sXml->NFe->infNFe;

            if (isset($sXml->infNFe))
                $infNFe = $sXml->infNFe;

            if (!isset($infNFe))
                throw new Exception("Arquivo '{$arquivo}' não contém uma NF-e válida!");

            if (!isset($infNFe->attributes()->Id))
                throw new Exception("Arquivo '{$arquivo}' não contém uma chave de NF-e!");

            $chave = Yii::app()->format->NumeroLimpo($infNFe->attributes()->Id->__toString());
            if (!NFePHP\Common\Keys\Keys::testaChave($chave))
                throw new Exception("Arquivo '{$arquivo}' não contém uma chave de NF-e Válida!");

            // busca registro no banco
            $nfe = NfeTerceiro::model()->find("nfechave = :nfechave", array(":nfechave" => $chave));
            if ($nfe === NULL)
                $nfe = new NfeTerceiro;

            $cnpj = $infNFe->dest->CNPJ;
            if (empty($cnpj))
                $cnpj = $infNFe->dest->CPF;

            if ($pessoa = Pessoa::model()->find("cnpj = :cnpj", array(":cnpj" => $cnpj)))
                if ($filial = Filial::model()->find("codpessoa = :codpessoa", array(":codpessoa" => $pessoa->codpessoa)))
                    $nfe->codfilial = $filial->codfilial;

            if (empty($nfe->codfilial))
                throw new Exception("Impossível Localizar a filial com o CNPJ/CPF '{$cnpj}'!");

            $nfe->nsu = null;
            $nfe->nfechave = $chave;

            $nfe->cnpj = $infNFe->emit->CNPJ->__toString();
            $nfe->ie = $infNFe->emit->IE->__toString();

            $nfe->emitente = Yii::app()->format->removeAcentos(utf8_encode($infNFe->emit->xNome->__toString()));
            if (empty($nfe->emitente))
                $nfe->emitente = '<Vazio>';


            if (!($dh = DateTime::createFromFormat('Y-m-d\TH:i:sP', $infNFe->ide->dhEmi->__toString())))
                if (!($dh = DateTime::createFromFormat('Y-m-d', $infNFe->ide->dEmi->__toString())))
                    throw new Exception("Impossível determinar a data de emissão da NF-e!");

            $nfe->emissao = $dh->format("d/m/Y H:i:s");

            if (!$nfe->save())
                throw new Exception("Erro ao Salvar NF-e de Terceiro:\n\n" . print_r($nfe->getErrors(), true));

            $nfe = NfeTerceiro::model()->find("nfechave = :nfechave", array(":nfechave" => $chave));
            if ($nfe === NULL)
                throw new Exception("Erro localizar NF-e de Terceiro pela chave {$chave}!");

            $config = $this->montarConfiguracao($nfe->codfilial, '55', $codnotafiscal = null, $nfe->codnfeterceiro);
            file_put_contents($this->arquivoXMLRecebida, $xmlOriginal);

            if (!$nfe->importarXml())
                throw new Exception("Erro ao Importaro Arquivo XML:\n\n" . print_r($nfe->getErrors(), true));

            //TODO: APAGAR XML ORIGINAL
            $aRetorno['codnfeterceiro'] = $nfe->codnfeterceiro;

            if (!unlink($arquivo))
                throw new Exception("Erro ao apagar o arquivo '{$arquivo}'!");

            $aRetorno['retorno'] = true;
        } catch (Exception $ex) {
            $aRetorno['ex'] = $ex->getMessage();
        }

        header('Content-type: text/json; charset=UTF-8');
        echo json_encode($aRetorno);
    }

    public function actionVisualizaXml($codnfeterceiro = '', $codnotafiscal = '') {
        if (!empty($codnfeterceiro)) {
            $nfeTerceiro = $this->loadModelNfeTerceiro($codnfeterceiro);
            $arquivo = $nfeTerceiro->montarCaminhoArquivoXml();
        }

        if (!empty($codnotafiscal)) {
            $nfe = $this->loadModelNotaFiscal($codnotafiscal);
            $config = $this->montarConfiguracao($nfe->codfilial, '55', $codnotafiscal);

            $arquivo = $this->arquivoXMLAprovada;
            if (!is_file($arquivo))
                $arquivo = $this->arquivoXMLDenegada;
            if (!is_file($arquivo))
                $arquivo = $this->arquivoXMLValidada;
        }

        if (empty($arquivo))
            throw new CHttpException(404, 'Nenhum documento informado!');

        if (!is_file($arquivo))
            throw new CHttpException(404, "Arquivo '{$arquivo}' inexistente!");

        $xml = file_get_contents($arquivo);

        header("Content-type: text/xml");
        echo $xml;
    }

}
