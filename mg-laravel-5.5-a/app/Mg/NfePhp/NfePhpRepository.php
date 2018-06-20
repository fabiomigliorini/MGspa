<?php

namespace Mg\NfePhp;
use Mg\MgRepository;

use Mg\NotaFiscal\NotaFiscal;
use Mg\NaturezaOperacao\Operacao;
use Mg\Filial\Empresa;


use NFePHP\NFe\Make;
use NFePHP\NFe\Tools;
use NFePHP\NFe\Complements;
use NFePHP\NFe\Convert;
use NFePHP\Common\Certificate;
use NFePHP\Common\Validator;



class NfePhpRepository extends MgRepository
{

    public static function criaXml($codnotafiscal)
    {

        $nf = NotaFiscal::findOrFail($codnotafiscal);

        $nfe = new Make();

        // Infomacao Nfe
        $std = new \stdClass();
        $std->versao = '4.0';
        $nfe->taginfNFe($std);

        // Identificacao Nfe
        $std = new \stdClass();
        $std->cUF = $nf->Filial->Pessoa->Cidade->Estado->codigooficial;
        $std->cNF = $nf->numero;
        $std->natOp = $nf->NaturezaOperacao->naturezaoperacao;
        $std->mod = $nf->modelo;
        //$std->indPag = ($nf->NotaFiscalDuplicatasS()->count() > 0) ? 1 : 0; //0=Pagamento à vista; 1=Pagamento a prazo; 2=Outros
        $std->serie = $nf->serie;
        $std->nNF = $nf->numero;
        $std->dhEmi = $nf->emissao->format('Y-m-d\TH:i:sP');
        $std->dhSaiEnt = $nf->saida->format('Y-m-d\TH:i:sP');
        $std->tpNF = ($nf->codoperacao == Operacao::ENTRADA)?0:1; //0=Entrada; 1=Saída
        $std->idDest = ($nf->Pessoa->Cidade->codestado == $nf->Filial->Pessoa->Cidade->codestado)?1:2; //1=Operação interna; 2=Operação interestadual; 3=Operação com exterior.
        $std->cMunFG = $nf->Filial->Pessoa->Cidade->codigooficial;


        $std->tpAmb = $nf->Filial->nfeambiente; // Se deixar o tpAmb como 2 você emitirá a nota em ambiente de homologação(teste) e as notas fiscais aqui não tem valor fiscal
        $std->finNFe = $nf->NaturezaOperacao->finnfe; //1=NF-e normal; 2=NF-e complementar; 3=NF-e de ajuste; 4=Devolução/Retorno.
        $std->indFinal = ($nf->Pessoa->consumidor)?1:0; //0=Não; 1=Consumidor final;

        // 0=Não se aplica (por exemplo, Nota Fiscal complementar ou de ajuste);
        // 1=Operação presencial;
        // 2=Operação não presencial, pela Internet;
        // 3=Operação não presencial, Teleatendimento;
        // 4=NFC-e em operação com entrega a domicílio;
        // 9=Operação não presencial, outros.
        $std->indPres = 1;

        // 0=Emissão de NF-e com aplicativo do contribuinte;
        // 1=Emissão de NF-e avulsa pelo Fisco;
        // 2=Emissão de NF-e avulsa, pelo contribuinte com seu certificado digital, através do site do Fisco;
        // 3=Emissão NF-e pelo contribuinte com aplicativo fornecido pelo Fisco.
        $std->procEmi = '0';

        $std->verProc = 1; //versão do aplicativo emissor

        // NFe
        if ($nf->modelo == NotaFiscal::MODELO_NFE) {

            // 0=Sem geração de DANFE;
            // 1=DANFE normal, Retrato;
            // 2=DANFE normal, Paisagem;
            // 3=DANFE Simplificado;
            // 4=DANFE NFC-e;
            // 5=DANFE NFC-e em mensagem eletrônica
            // (o envio de mensagem eletrônica pode ser feita de forma simultânea com a impressão do DANFE;
            // usar o tpImp=5 quando esta for a única forma de disponibilização do DANFE).
            $std->tpImp = 1;

            // 1=Emissão normal (não em contingência);
            // 2=Contingência FS-IA, com impressão do DANFE em formulário de segurança;
            // 3=Contingência SCAN (Sistema de Contingência do Ambiente Nacional);
            // 4=Contingência DPEC (Declaração Prévia da Emissão em Contingência);
            // 5=Contingência FS-DA, com impressão do DANFE em formulário de segurança;
            // 6=Contingência SVC-AN (SEFAZ Virtual de Contingência do AN);
            // 7=Contingência SVC-RS (SEFAZ Virtual de Contingência do RS);
            // 9=Contingência off-line da NFC-e (as demais opções de contingência são válidas também para a NFC-e);
            // Nota: Para a NFC-e somente estão disponíveis e são válidas as opções de contingência 5 e 9.
            $std->tpEmis = 1;

        // DANFE NFCe
        } else {

            if (!empty($std->Pessoa->ie)) {
                throw new Exception("Não é permitida emissão de NFCe para Pessoas com Inscrição Estadual!");
            }

            $std->tpImp = 4; // Danfe NFC-e
            $std->tpEmis = $nf->Filial->Empresa->modoemissaonfce;

            // Se estiver em modo OffLine
            if ($std->tpEmis == Empresa::MODOEMISSAONFCE_OFFLINE) {

                // Salva Informacao de NFCe Offline na tabela
                $nf->tpemis = NotaFiscal::TPEMIS_OFFLINE;
                $nf->save();

                // $aRetorno['tpEmis'] = $nf->tpemis;

                // Data, Hora e Justificativa da contingencia
                $std->dhCont = $nf->Filial->Empresa->contingenciadata->format('Y-m-d\TH:i:sP');
                $std->xJust = $nf->Filial->Empresa->contingenciajustificativa; //Justificativa da entrada em contingência

                if ($std->finNFe != 1) {
                    throw new Exception("Finalidade de emissão ($finNFe) da Natureza de Operação não permite emissão OFFLINE!");
                }

                if ($std->indFinal != 1) {
                    throw new Exception("Não é permitida emissão OFFLINE para Pessoas que não sejam Consumidor Final!");
                }

                if ($std->idDest != 1) {
                    throw new Exception("Não é permitida emissão OFFLINE para Pessoas de fora do Estado de {$nf->Filial->Pessoa->Cidade->Estado->estado}!");
                }


            }

        }

        // Cria Tag Ide
        $nfe->tagide($std);

        // Emitente
        $std->xNome = utf8_encode($nf->Filial->Pessoa->pessoa);
        $std->xFant = utf8_encode($nf->Filial->Pessoa->fantasia);
        $std->IE = $nf->Filial->Pessoa->ie;
        $std->CRT = $nf->Filial->crt;
        $std->CNPJ = str_pad($nf->Filial->Pessoa->cnpj, 14, '0', STR_PAD_LEFT);
        $nfe->tagemit($std);

        // Endereço Emitente
        $std = new \stdClass();
        $std->xLgr = utf8_encode($nf->Filial->Pessoa->endereco);
        $std->nro = utf8_encode($nf->Filial->Pessoa->numero);
        $std->xCpl = utf8_encode($nf->Filial->Pessoa->complemento);
        $std->xBairro = utf8_encode($nf->Filial->Pessoa->bairro);
        $std->cMun = $nf->Filial->Pessoa->Cidade->codigooficial;
        $std->xMun = utf8_encode($nf->Filial->Pessoa->Cidade->cidade);
        $std->UF = utf8_encode($nf->Filial->Pessoa->Cidade->Estado->sigla);
        $std->CEP = $nf->Filial->Pessoa->cep;
        $std->cPais = $nf->Filial->Pessoa->Cidade->Estado->Pais->codigooficial;
        $std->xPais = utf8_encode($nf->Filial->Pessoa->Cidade->Estado->Pais->pais);
        $std->fone = $nf->Filial->Pessoa->telefone1;
        if (empty($std->fone)) {
            $std->fone = $nf->Filial->Pessoa->telefone2;
        }
        if (empty($std->fone)) {
            $std->fone = $nf->Filial->Pessoa->telefone3;
        }
        $std->fone = utf8_encode($std->fone);
        $nfe->tagenderEmit($std);

        // Destinatario
        //if ($nf->codpessoa != Pessoa::CONSUMIDOR) {
            $std = new \stdClass();
            $std->xNome = substr(utf8_encode($nf->Pessoa->pessoa), 0, 60);

            if ($nf->Pessoa->Cidade->Estado->sigla != 'MT') {
                $std->indIEDest = (empty($IE)) ? '9' : '1';
            } else {
                $std->indIEDest = (empty($IE)) ? '2' : '1';
            }
            // $std->indIEDest = 1;
            $std->IE = $nf->Pessoa->ie;

            if ($nf->Pessoa->fisica) {
                $std->CNPJ = '';
                $std->CPF = str_pad($nf->Pessoa->cnpj, 11, '0', STR_PAD_LEFT); //'58716523000119';
            } else {
                $std->CNPJ = str_pad($nf->Pessoa->cnpj, 14, '0', STR_PAD_LEFT); //'58716523000119';
                $std->CPF = '';
            }
            // $std->CNPJ = '78767865000156';
            $nfe->tagdest($std);

            // Endereco Destinatario
            $std = new \stdClass();
            $std->xLgr = utf8_encode($nf->Pessoa->endereco);
            $std->nro = utf8_encode($nf->Pessoa->numero);
            $std->xCpl = utf8_encode($nf->Pessoa->complemento);
            if (empty($std->xCpl))
            $std->xCpl = '-';
            $std->xBairro = utf8_encode($nf->Pessoa->bairro);
            $std->cMun = $nf->Pessoa->Cidade->codigooficial;
            $std->xMun = utf8_encode($nf->Pessoa->Cidade->cidade);
            $std->UF = utf8_encode($nf->Pessoa->Cidade->Estado->sigla);
            $std->CEP = $nf->Pessoa->cep;
            $std->cPais = $nf->Pessoa->Cidade->Estado->Pais->codigooficial;
            $std->xPais = utf8_encode($nf->Pessoa->Cidade->Estado->Pais->pais);
            $nfe->tagenderDest($std);
        //}


        // Produtos
        $std = new \stdClass();
        $std->item = 1;
        $std->cEAN = utf8_encode($nf->ProdutoBarra? $nf->ProdutoBarra->barras : null);
        $std->cProd = utf8_encode($nf->ProdutoBarra->codproduto);
        $std->xProd = utf8_encode((empty($nf->descricaoalternativa)) ? $nf->ProdutoBarra->descricao : $nf->descricaoalternativa);
        $std->NCM = utf8_encode($nf->ProdutoBarra->Produto->Ncm->ncm);
        $std->CFOP = $nf->codcfop;
        $std->uCom = utf8_encode($nf->ProdutoBarra->UnidadeMedida->sigla);
        $std->qCom = number_format($nf->quantidade, 3, '.', '');
        $std->vUnCom = number_format($nf->valorunitario, 10, '.', '');
        $std->vProd = number_format($nf->valortotal, 2, '.', '');
        $std->cEANTrib = utf8_encode($nf->ProdutoBarra->barrasValido() ? $nf->ProdutoBarra->barras : "");
        $std->uTrib = utf8_encode($nf->ProdutoBarra->UnidadeMedida->sigla); //number_format($nf->valorunitario, 3, '.', '');
        $std->qTrib = number_format($nf->quantidade, 3, '.', '');
        $std->vUnTrib = number_format($nf->valorunitario, 10, '.', '');
        $std->indTot = 1;
        $nfe->tagprod($std);

        // Impostos Produto
        $std = new \stdClass();
        $std->item = 1;
        $std->vTotTrib = 10.99;
        $nfe->tagimposto($std);

        // ICMS Produto
        $std = new \stdClass();
        $std->item = 1;
        $std->orig = 0;
        $std->CST = '00';
        $std->modBC = 0;
        $std->vBC = 0.20;
        $std->pICMS = '18.0000';
        $std->vICMS ='0.04';
        $nfe->tagICMS($std);

        // IPI Produto
        $std = new \stdClass();
        $std->item = 1;
        $std->cEnq = '999';
        $std->CST = '50';
        $std->vIPI = 0;
        $std->vBC = 0;
        $std->pIPI = 0;
        $nfe->tagIPI($std);

        // PIS Produto
        $std = new \stdClass();
        $std->item = 1;
        $std->CST = '07';
        $std->vBC = 0;
        $std->pPIS = 0;
        $std->vPIS = 0;
        $nfe->tagPIS($std);

        // Cofins Produto
        $std = new \stdClass();
        $std->item = 1;
        $std->vCOFINS = 0;
        $std->vBC = 0;
        $std->pCOFINS = 0;
        $nfe->tagCOFINSST($std);

        // Total ICMS da Nota
        $std = new \stdClass();
        $std->vBC = 0.20;
        $std->vICMS = 0.04;
        $std->vICMSDeson = 0.00;
        $std->vBCST = 0.00;
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

        // Modalidade de Frete
        $std = new \stdClass();
        $std->modFrete = 1;
        $nfe->tagtransp($std);

        // Volumes
        $std = new \stdClass();
        $std->item = 1;
        $std->qVol = 2;
        $std->esp = 'caixa';
        $std->marca = 'OLX';
        $std->nVol = '11111';
        $std->pesoL = 10.00;
        $std->pesoB = 11.00;
        $nfe->tagvol($std);

        // Faturas
        $std = new \stdClass();
        $std->nFat = '100';
        $std->vOrig = 100;
        $std->vLiq = 100;
        $nfe->tagfat($std);

        // Duplicatas
        $std = new \stdClass();
        $std->nDup = '100';
        $std->dVenc = '2017-08-22';
        $std->vDup = 11.03;
        $nfe->tagdup($std);

        // Gera o XML
        $xml = $nfe->getXML(); // O conteúdo do XML fica armazenado na variável $xml

        // Retorna o XML
        return $xml;

    }

}
