<?php

namespace Mg\Nfephp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use NFePHP\NFe\Make;

class NfeController extends Controller
{

  public function actionCriaXml($codnotafiscal){

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

    if (!$this->confereNumero($nf)){
        throw new Exception('Erro ao determinar numero da Nota Fiscal!');
    }

    $nfe = new Make();


    $std = new \stdClass();
    $std->versao = '4.0';
    $nfe->taginfNFe($std);

    $std = new \stdClass();
    $std->cUF = $nf->Filial->Pessoa->Cidade->Estado->codigooficial;
    $std->cNF = str_pad($nf->numero, 8, '0', STR_PAD_LEFT); //numero aleatório da NF
    $std->natOp = utf8_encode($nf->NaturezaOperacao->naturezaoperacao); //natureza da operação
    $std->indPag = (sizeof($nf->NotaFiscalDuplicatass) > 0) ? 1 : 0; //0=Pagamento à vista; 1=Pagamento a prazo; 2=Outros
    $std->mod = $nf->modelo; //modelo da NFe 55 ou 65 essa última NFCe
    $std->serie = $nf->serie; //serie da NFe
    $std->nNF = $nf->numero; // numero da NFe]
    $dh = DateTime::createFromFormat('d/m/Y H:i:s', $nf->emissao); //para versão 3.10 '2014-02-03T13:22:42-3.00' não informar para NFCe
    $std->dhEmi = $dh->format('Y-m-d\TH:i:sP');
    $dh = DateTime::createFromFormat('d/m/Y H:i:s', $nf->saida);
    $std->dhSaiEnt = $dh->format('Y-m-d\TH:i:sP'); //versão 2.00, 3.00 e 3.10
    $std->tpNF = ($nf->codoperacao == Operacao::ENTRADA) ? 0 : 1; //0=Entrada; 1=Saída
    $std->idDest = 1; //1=Operação interna; 2=Operação interestadual; 3=Operação com exterior.
    if ($nf->Pessoa->Cidade->codestado <> $nf->Filial->Pessoa->Cidade->codestado){
        $std->idDest = '2';
    }
    $std->cMunFG = $nf->Filial->Pessoa->Cidade->codigooficial;
    $std->tpImp = 1; //0=Sem geração de DANFE; 1=DANFE normal, Retrato; 2=DANFE normal, Paisagem;
    //3=DANFE Simplificado; 4=DANFE NFC-e; 5=DANFE NFC-e em mensagem eletrônica
    //(o envio de mensagem eletrônica pode ser feita de forma simultânea com a impressão do DANFE;
    //usar o tpImp=5 quando esta for a única forma de disponibilização do DANFE).
    $std->tpEmis = 1; //1=Emissão normal (não em contingência);
    //2=Contingência FS-IA, com impressão do DANFE em formulário de segurança;
    //3=Contingência SCAN (Sistema de Contingência do Ambiente Nacional);
    //4=Contingência DPEC (Declaração Prévia da Emissão em Contingência);
    //5=Contingência FS-DA, com impressão do DANFE em formulário de segurança;
    //6=Contingência SVC-AN (SEFAZ Virtual de Contingência do AN);
    //7=Contingência SVC-RS (SEFAZ Virtual de Contingência do RS);
    //9=Contingência off-line da NFC-e (as demais opções de contingência são válidas também para a NFC-e);
    //Nota: Para a NFC-e somente estão disponíveis e são válidas as opções de contingência 5 e 9.
    if ($nf->modelo == NotaFiscal::MODELO_NFCE) {
        $std->tpEmis = 4; //DANFE NFC-e
        $std->tpEmis = $nf->Filial->Empresa->modoemissaonfce;

        if ($std->tpEmis == Empresa::MODOEMISSAONFCE_OFFLINE) {
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

    $std->cDV = substr($chave, -1); //digito verificador
    $std->tpAmb = 2; // Se deixar o tpAmb como 2 você emitirá a nota em ambiente de homologação(teste) e as notas fiscais aqui não tem valor fiscal
    //$std->tpAmb = $nf->Filial->nfeambiente; //1=Produção; 2=Homologação
    $std->finNFe = $nf->NaturezaOperacao->finnfe; //1=NF-e normal; 2=NF-e complementar; 3=NF-e de ajuste; 4=Devolução/Retorno.
    $std->indFinal = ($nf->Pessoa->consumidor) ? '1' : '0'; //0=Não; 1=Consumidor final;
    $std->indPres = 1; //0=Não se aplica (por exemplo, Nota Fiscal complementar ou de ajuste);
    //1=Operação presencial;
    //2=Operação não presencial, pela Internet;
    //3=Operação não presencial, Teleatendimento;
    //4=NFC-e em operação com entrega a domicílio;
    //9=Operação não presencial, outros.
    $std->procEmi = '3.10.31,';//0=Emissão de NF-e com aplicativo do contribuinte;
    //1=Emissão de NF-e avulsa pelo Fisco;
    //2=Emissão de NF-e avulsa, pelo contribuinte com seu certificado digital, através do site do Fisco;
    //3=Emissão NF-e pelo contribuinte com aplicativo fornecido pelo Fisco
    $std->verProc = 1; //versão do aplicativo emissor
    $nfe->tagide($std);

    $std = new \stdClass();
    $std->xNome = utf8_encode($nf->Filial->Pessoa->pessoa);
    $std->IE = Yii::app()->format->NumeroLimpo($nf->Filial->Pessoa->ie);
    $std->CRT = $nf->Filial->crt;
    $std->CNPJ = '00000000000';
    $nfe->tagemit($std);

    //endereço do emitente
    $std = new \stdClass();
    $std->xLgr = utf8_encode($nf->Filial->Pessoa->endereco);
    $std->nro = utf8_encode($nf->Filial->Pessoa->numero);
    $std->xBairro =  utf8_encode($nf->Filial->Pessoa->bairro);
    $std->cMun = $nf->Filial->Pessoa->Cidade->codigooficial;
    $std->xMun = utf8_encode($nf->Filial->Pessoa->Cidade->cidade);
    $std->UF = utf8_encode($nf->Filial->Pessoa->Cidade->Estado->sigla);
    $std->CEP = $nf->Filial->Pessoa->cep;
    $std->cPais = $nf->Filial->Pessoa->Cidade->Estado->Pais->codigooficial;
    $std->xPais = utf8_encode($nf->Filial->Pessoa->Cidade->Estado->Pais->pais);
    $nfe->tagenderEmit($std);

    // Empresa Destinatario
    $std = new \stdClass();
    $std->xNome = 'Empresa destinatário teste';
    $std->indIEDest = 1;
    $std->IE = '6564344535';
    $std->CNPJ = '78767865000156';
    $nfe->tagdest($std);

    $std = new \stdClass();
    $std->xLgr = utf8_encode($nf->Pessoa->endereco);
    $std->nro = utf8_encode($nf->Pessoa->numero);
    $std->xBairro = utf8_encode($nf->Pessoa->bairro);
    $std->cMun = $nf->Pessoa->Cidade->codigooficial;
    $std->xMun = utf8_encode($nf->Pessoa->Cidade->cidade);
    $std->UF = utf8_encode($nf->Pessoa->Cidade->Estado->sigla);
    $std->CEP = $nf->Pessoa->cep;
    $std->cPais = $nf->Pessoa->Cidade->Estado->Pais->codigooficial;
    $std->xPais = utf8_encode($nf->Pessoa->Cidade->Estado->Pais->pais);
    $nfe->tagenderDest($std);

    $std = new \stdClass();
    $std->item = 1;
    $std->cProd = utf8_encode($cProd);
    $std->xProd = utf8_encode((empty($nfpb->descricaoalternativa)) ? $nfpb->ProdutoBarra->descricao : $nfpb->descricaoalternativa);
    $std->NCM = utf8_encode(Yii::app()->format->formataPorMascara($nfpb->ProdutoBarra->Produto->Ncm->ncm, "########"));
    //concatena o '-Quantidade' da embalagem
    if (isset($nfpb->ProdutoBarra->ProdutoEmbalagem)){
        $cProd .= '-' . str_replace('C/', '', $nfpb->ProdutoBarra->ProdutoEmbalagem->descricao);
    }
    $std->CFOP = $nfpb->codcfop;
    $std->uCom = utf8_encode($nfpb->ProdutoBarra->UnidadeMedida->sigla);
    $std->qCom = number_format($nfpb->quantidade, 3, '.', '');
    $std->vUnCom = number_format($nfpb->valorunitario, 10, '.', '');
    $std->vProd = number_format($nfpb->valortotal, 2, '.', '');
    $std->uTrib = utf8_encode($nfpb->ProdutoBarra->UnidadeMedida->sigla); //number_format($nfpb->valorunitario, 3, '.', '');
    $std->qTrib = number_format($nfpb->quantidade, 3, '.', '');
    $std->vUnTrib = number_format($nfpb->valorunitario, 10, '.', '');
    $std->indTot = 1;
    $nfe->tagprod($std);

    // TAG IMPOSTO
    $std = new \stdClass();
    $std->item = 1; // rever este <-------------------------------------
    $std->vTotTrib = 0;
    $nfe->tagimposto($std);

    $std = new \stdClass();
    $std->item = 1;
    $std->orig = 0;

    //ICMS
    $std->CST = MGFormatter::formataPorMascara($nfpb->icmscst, '##');
    $std->modBC = 3;
    $std->vBC = number_format($nfpb->icmsbase, 2, '.', '');
    $std->pICMS = number_format($nfpb->icmspercentual, 2, '.', '');
    $std->vICMS = number_format($nfpb->icmsvalor, 2, '.', '');
    $nfe->tagICMS($std);

    //IPI
    if (!empty($nfpb->ipivalor)) {
        $std = new \stdClass();
        $std->item = 1;
        $std->CST = str_pad($nfpb->ipicst, 2, '0', STR_PAD_LEFT);
        $std->cEnq = '';
        // TODO: Jogar logica cEnq para modelagem do banco de dados
        if ($nfpb->ipicst == 4 || $nfpb->ipicst == 54){
            $cEnq = '001'; // Livros, jornais, periódicos e o papel destinado à sua impressão – Art. 18 Inciso I do Decreto 7.212/2010
        }
        else{
            $cEnq = '999'; // Tributação normal IPI; Outros;
        }
        $std->vBC = number_format($nfpb->ipibase, 2, '.', '');
        $std->pIPI = number_format($nfpb->ipipercentual, 2, '.', '');

        $std->vIPI = number_format($nfpb->ipivalor, 2, '.', '');

        $nfe->tagIPI($std);
    }

    //PIS
    $std = new \stdClass();
    $std->item = 1;
    $std->CST = MGFormatter::formataPorMascara($nfpb->piscst, '##');
    $std->vBC = number_format($nfpb->pisbase, 2, '.', '');
    $std->pPIS = number_format($nfpb->pispercentual, 2, '.', '');
    $std->vPIS = number_format($nfpb->pisvalor, 2, '.', '');
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
    $nfe->tagPIS($std);

    $std = new \stdClass();
    $std->item = 1;
    $std->vCOFINS = 0;
    $std->vBC = 0;
    $std->pCOFINS = 0;
    $nfe->tagCOFINSST($std);

    $std = new \stdClass();
    $std->vBC = 0.20;
    $std->vICMS = 0.04;
    $std->vICMSDeson = 0.00;
    $std->vBCST = number_format($nfpb->icmsstbase, 2, '.', '');
    $std->vST = 0.00;
    $std->vProd = 10.99;
    $std->vFrete = 0.00;
    $std->vSeg = 0.00;
    $std->vDesc = 0.00;
    $std->vII = 0.00;
    $std->vIPI = 0.00;
    $std->vPIS = 0.00;
    $std->vCOFINS = 0.00;
    $std->vOutro = 0.00;
    $std->vNF = 11.03;
    $std->vTotTrib = 0.00;
    $nfe->tagICMSTot($std);

    $std = new \stdClass();
    $std->modFrete = 1;
    $nfe->tagtransp($std);

    $std = new \stdClass();
    $std->item = 1;
    $std->qVol = 2;
    $std->esp = 'caixa';
    $std->marca = 'OLX';
    $std->nVol = '11111';
    $std->pesoL = 10.00;
    $std->pesoB = 11.00;
    $nfe->tagvol($std);

    $std = new \stdClass();
    $std->nFat = '100';
    $std->vOrig = 100;
    $std->vLiq = 100;
    $nfe->tagfat($std);

    $std = new \stdClass();
    $std->nDup = '100';
    $std->dVenc = '2017-08-22';
    $std->vDup = 11.03;
    $nfe->tagdup($std);

    $xml = $nfe->getXML(); // O conteúdo do XML fica armazenado na variável $xml

    return $xml;
   }




}
