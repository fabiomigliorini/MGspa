<?php

namespace Mg\NFePHP;

use DB;
use Carbon\Carbon;

use Mg\NotaFiscal\NotaFiscal;
use Mg\NaturezaOperacao\Operacao;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\Pessoa\Pessoa;
use Mg\Produto\Barras;
use Mg\Filial\Filial;
use Mg\Filial\Empresa;

use NFePHP\NFe\Make;
use NFePHP\Common\Strings;
use NFePHP\Gtin\Gtin;

class NFePHPRepositoryMake
{

    public static function gerarNumeroNotaFiscal(NotaFiscal $nf)
    {

        // Se Nota Fiscal já tem número já está tudo OK
        If (!empty($nf->numero)) {
            return true;
        }

        // Busca Proximo Valor da Sequence de Numero da Nota Fiscal
        $sql = "SELECT NEXTVAL('tblnotafiscal_numero_{$nf->codfilial}_{$nf->serie}_{$nf->modelo}_seq') as numero";
        $ret = DB::select($sql);

        // Se não veio resultado do Banco, retorna Erro
        if (!isset($ret[0])) {
            return false;
        }

        // Atualiza número da Nota no Banco de Dados
        $emissao = Carbon::now();
        NotaFiscal::where('codnotafiscal', $nf->codnotafiscal)->update([
            'numero' => $ret[0]->numero,
            'emissao' => $emissao,
            'saida' => $emissao,
        ]);

        return true;
    }

    public static function montarXml (NotaFiscal $nf)
    {
        // Valida se o preenchimento da NFe está correto
        NFePHPRepositoryValidacao::validar($nf);

        // Confere se nota Fiscal tem Número
        if (!static::gerarNumeroNotaFiscal($nf)) {
            throw new \Exception('Erro ao Gerar Número da Nota Fiscal!');
        }
        $nf = $nf->fresh();

        $nfe = new Make();

        // Infomacao Nfe
        $std = new \stdClass();
        $std->versao = '4.00';
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
        $std->dhEmi = $nf->emissao->toW3cString();
        $std->dhSaiEnt = $nf->saida->toW3cString();
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

            $std->tpImp = 4; // Danfe NFC-e
            $std->tpEmis = $nf->Filial->Empresa->modoemissaonfce;

            // Se estiver em modo OffLine
            if ($std->tpEmis == Empresa::MODOEMISSAONFCE_OFFLINE) {

                // Salva Informacao de NFCe Offline na tabela
                $nf->tpemis = NotaFiscal::TPEMIS_OFFLINE;
                $nf->save();

                // $aRetorno['tpEmis'] = $nf->tpemis;

                // Data, Hora e Justificativa da contingencia
                $std->dhCont = $nf->Filial->Empresa->contingenciadata->toW3cString();
                $std->xJust = $nf->Filial->Empresa->contingenciajustificativa; //Justificativa da entrada em contingência

            }

        }

        // Cria Tag Ide
        $ret = $nfe->tagide($std);

        // NFe's referenciadas
        $anoICMSInterPart = $nf->emissao->year;
        foreach ($nf->NotaFiscalReferenciadaS()->orderBy('nfechave')->get() as $nfr) {
            //dd($nfr->nfechave);
            $anoICMSInterPart = 2000 + substr($nfr->nfechave, 2, 2);
            $std = new \stdClass();
            $std->refNFe = $nfr->nfechave;
            $elem = $nfe->tagrefNFe($std);
        }

        // Emitente
        $std->xNome = Strings::replaceSpecialsChars($nf->Filial->Pessoa->pessoa);
        $std->xFant = Strings::replaceSpecialsChars($nf->Filial->Pessoa->fantasia);
        $std->IE = numeroLimpo($nf->Filial->Pessoa->ie);
        $std->CRT = $nf->Filial->crt;
        $std->CNPJ = str_pad($nf->Filial->Pessoa->cnpj, 14, '0', STR_PAD_LEFT);
        $nfe->tagemit($std);

        // Endereço Emitente
        $std = new \stdClass();
        $std->xLgr = Strings::replaceSpecialsChars($nf->Filial->Pessoa->endereco);
        $std->nro = Strings::replaceSpecialsChars($nf->Filial->Pessoa->numero);
        $std->xCpl = Strings::replaceSpecialsChars($nf->Filial->Pessoa->complemento);
        $std->xBairro = Strings::replaceSpecialsChars($nf->Filial->Pessoa->bairro);
        $std->cMun = $nf->Filial->Pessoa->Cidade->codigooficial;
        $std->xMun = Strings::replaceSpecialsChars($nf->Filial->Pessoa->Cidade->cidade);
        $std->UF = Strings::replaceSpecialsChars($nf->Filial->Pessoa->Cidade->Estado->sigla);
        $std->CEP = $nf->Filial->Pessoa->cep;
        $std->cPais = $nf->Filial->Pessoa->Cidade->Estado->Pais->codigooficial;
        $std->xPais = Strings::replaceSpecialsChars($nf->Filial->Pessoa->Cidade->Estado->Pais->pais);
        $std->fone = numeroLimpo(($nf->Filial->Pessoa->telefone1??$nf->Filial->Pessoa->telefone2)??$nf->Filial->Pessoa->telefone3);
        $nfe->tagenderEmit($std);

        // Destinatario
        if ($nf->codpessoa != Pessoa::CONSUMIDOR) {

            $std = new \stdClass();
            $std->xNome = substr(Strings::replaceSpecialsChars($nf->Pessoa->pessoa), 0, 60);

            $std->IE = numeroLimpo($nf->Pessoa->ie);
            if ($nf->Pessoa->Cidade->Estado->sigla != 'MT') {
                $std->indIEDest = (empty($std->IE)) ? '2' : '1';
            } else {
                $std->indIEDest = (empty($std->IE)) ? '2' : '1';
            }

            if ($nf->Pessoa->fisica) {
                $std->CNPJ = '';
                $std->CPF = str_pad($nf->Pessoa->cnpj, 11, '0', STR_PAD_LEFT); //'58716523000119';
            } else {
                $std->CNPJ = str_pad($nf->Pessoa->cnpj, 14, '0', STR_PAD_LEFT); //'58716523000119';
                $std->CPF = '';
            }
            $std->email = ($nf->Pessoa->emailnfe??$nf->Pessoa->email)??$nf->Pessoa->emailcobranca;
            $nfe->tagdest($std);

            // Endereco Destinatario
            $std = new \stdClass();
            $std->xLgr = Strings::replaceSpecialsChars($nf->Pessoa->endereco);
            $std->nro = Strings::replaceSpecialsChars($nf->Pessoa->numero);
            if (!empty($nf->Pessoa->complemento)) {
                $std->xCpl = Strings::replaceSpecialsChars($nf->Pessoa->complemento);
            }
            $std->xBairro = Strings::replaceSpecialsChars($nf->Pessoa->bairro);
            $std->cMun = $nf->Pessoa->Cidade->codigooficial;
            $std->xMun = Strings::replaceSpecialsChars($nf->Pessoa->Cidade->cidade);
            $std->UF = Strings::replaceSpecialsChars($nf->Pessoa->Cidade->Estado->sigla);
            $std->CEP = $nf->Pessoa->cep;
            $std->cPais = $nf->Pessoa->Cidade->Estado->Pais->codigooficial;
            $std->xPais = Strings::replaceSpecialsChars($nf->Pessoa->Cidade->Estado->Pais->pais);
            $std->fone = numeroLimpo(($nf->Pessoa->telefone1??$nf->Pessoa->telefone2)??$nf->Pessoa->telefone3);
            $nfe->tagenderDest($std);

        }

        $nItem = 0;
        $totalPis = 0;
        $totalCofins = 0;
        $totalTrib = 0;
        $totalTribFederal = 0;
        $totalTribEstadual = 0;
        $totalTribMunicipal = 0;
        $ibptFonte = '';

        //instancia a classe Ibpt
        $ibpt = new MgIbpt($nf->Filial);

        $rateio = static::rateiaDescontoSeguroFreteOutras($nf);

        // Produtos
        foreach ($nf->NotaFiscalProdutoBarraS()->orderBy('codnotafiscalprodutobarra')->get() as $nfpb) {
          $nItem++;

          // Item
          $std = new \stdClass();
          $std->item = $nItem;
          $std->cProd = mascarar($nfpb->ProdutoBarra->codproduto, "######");
          if (!empty($nfpb->ProdutoBarra->codprodutoembalagem)){
              $std->cProd .= '-' . formataNumero($nfpb->ProdutoBarra->ProdutoEmbalagem->quantidade, 0);
          }
          $std->cEAN = 'SEM GTIN';
          try {
              $gtin = new Gtin($nfpb->ProdutoBarra->barras);
              if ($gtin->isValid()) {
                  $std->cEAN = $nfpb->ProdutoBarra->barras;
              }
          } catch (\Exception $e) {
          }
          $std->xProd = Strings::replaceSpecialsChars($nfpb->descricaoalternativa??$nfpb->ProdutoBarra->descricao);
          $std->NCM = Strings::replaceSpecialsChars($nfpb->ProdutoBarra->Produto->Ncm->ncm);
          $std->CFOP = $nfpb->codcfop;
          $std->uCom = Strings::replaceSpecialsChars($nfpb->ProdutoBarra->UnidadeMedida->sigla);
          $std->qCom = number_format($nfpb->quantidade, 3, '.', '');
          $std->vUnCom = number_format($nfpb->valorunitario, 10, '.', '');
          $std->vProd = number_format($nfpb->valortotal, 2, '.', '');

          $std->cEANTrib = $std->cEAN;
          $std->uTrib = $std->uCom;
          $std->qTrib = $std->qCom;

          // SE FOR EMBALAGEM, PEGA PRIMEIRO CODIGO DE BARRAS DE UNIDADE POSSIVEL
          if (!empty($nfpb->ProdutoBarra->codprodutoembalagem) && !empty($std->cEAN)) {
             foreach ($nfpb->ProdutoBarra->ProdutoVariacao->ProdutoBarraS()->whereNull('codprodutoembalagem')->get() as $pbUnidade) {
                 try {
                     $gtin = new Gtin($pbUnidade->barras);
                     if ($gtin->isValid()) {
                         $std->cEANTrib = $pbUnidade->barras;
                         if (empty($pbUnidade->codprodutoembalagem)) {
                             $std->uTrib = Strings::replaceSpecialsChars($nfpb->ProdutoBarra->Produto->UnidadeMedida->sigla);
                         }
                         $std->qTrib = number_format($nfpb->ProdutoBarra->ProdutoEmbalagem->quantidade * $nfpb->quantidade, 3, '.', '');
                         break;
                     }
                 } catch (\Exception $e) {
                 }
             }
          }

          // SE NAO ACHOU NENHUM CODIGO DE BARRAS DE UNIDADE, USA MESMO CODIGO DE BARRAS DA EMBALAGEM
          $std->vUnTrib = number_format($std->vProd / $std->qTrib, 10, '.', '');
          if (!empty($rateio[$nfpb->codnotafiscalprodutobarra]->vFrete)) {
              $std->vFrete = number_format($rateio[$nfpb->codnotafiscalprodutobarra]->vFrete, 2, '.', '');
          }
          if (!empty($rateio[$nfpb->codnotafiscalprodutobarra]->vSeg)) {
              $std->vSeg = number_format($rateio[$nfpb->codnotafiscalprodutobarra]->vSeg, 2, '.', '');
          }
          if (!empty($rateio[$nfpb->codnotafiscalprodutobarra]->vDesc)) {
              $std->vDesc = number_format($rateio[$nfpb->codnotafiscalprodutobarra]->vDesc, 2, '.', '');
          }
          if (!empty($rateio[$nfpb->codnotafiscalprodutobarra]->vOutro)) {
              $std->vOutro = number_format($rateio[$nfpb->codnotafiscalprodutobarra]->vOutro, 2, '.', '');
          }
          $std->indTot = 1;
          $nfe->tagprod($std);

          // Cest
          if (isset($nfpb->ProdutoBarra->Produto->Cest)) {
              $std = new \stdClass();
              $std->item = $nItem;
              $std->CEST = $nfpb->ProdutoBarra->Produto->Cest->cest;
              $cest = $nfe->tagCEST($std);
          }

          // Se natureza de operacao esta marcada para destacar IBPT
          $vTotTrib = 0;
          if ($nf->NaturezaOperacao->ibpt) {

              // Faz consulta ao WebService do IBPT
              $tax = $ibpt->pesquisar($nfpb);

              // Se nao houve erro ao consultar
              if (!isset($tax->error)) {

                  // monta string com fonte do IBPT para utilizar nos Dados Adicionais
                  $ibptFonte = "{$tax->fonte} {$tax->chave} {$tax->versao}";

                  // Valcula valor dos tributos
                  $vTotTribFederal = ($nfpb->valortotal * (($nfpb->ProdutoBarra->Produto->importado)?$tax->importado:$tax->nacional)) / 100;
                  $vTotTribEstadual = ($nfpb->valortotal * $tax->estadual) / 100;
                  $vTotTribMunicipal = ($nfpb->valortotal * $tax->municipal) / 100;
                  $vTotTrib = round($vTotTribFederal + $vTotTribEstadual + $vTotTribMunicipal, 2);

                  // Acumula totais dos tributos da nota
                  $totalTribFederal += $vTotTribFederal;
                  $totalTribEstadual += $vTotTribEstadual;
                  $totalTribMunicipal += $vTotTribMunicipal;
                  $totalTrib += $vTotTrib;


              }

          }

          // Gera TAG Imposto
          $std = new \stdClass();
          $std->item = $nItem;
          $std->vTotTrib = number_format($vTotTrib, 2, '.', '');
          $nfe->tagimposto($std);

          // Verifica Codigo do Regime Tributario
          switch ($nf->Filial->crt) {

              // Lucro Presumido
              case Filial::CRT_REGIME_NORMAL:

                  // ICMS
                  $std = new \stdClass();
                  $std->item = $nItem;
                  $std->orig = ($nfpb->ProdutoBarra->Produto->importado)?2:0;
                  $std->CST = mascarar($nfpb->icmscst, '##');
                  $std->modBC = 3; // 3 - Valor da Operacao
                  $std->vBC = number_format($nfpb->icmsbase, 2, '.', '');
                  $std->pICMS = number_format($nfpb->icmspercentual, 2, '.', '');
                  $std->vICMS = number_format($nfpb->icmsvalor, 2, '.', '');
                  if (!empty($nfpb->icmsbase) && ($nfpb->icmsbase < $nfpb->valortotal) && $nfpb->icmscst == 20) {
                      $std->pRedBC = number_format((1 - round($nfpb->icmsbase / $nfpb->valortotal, 2)) * 100, 2, '.', '');
                  }
                  if ($nfpb->icmsstvalor > 0) {
                      $std->modBCST = 4; // 4 - Margem de Valor Agregado (%)
                      $std->vBCST = number_format($nfpb->icmsstbase, 2, '.', '');
                      $std->pICMSST = number_format($nfpb->icmsstpercentual, 2, '.', '');
                      $std->vICMSST = number_format($nfpb->icmsstvalor, 2, '.', '');
                  }
                  $nfe->tagICMS($std);

                  // Partilha ICMS
                  if ($nf->Filial->Pessoa->Cidade->codestado != $nf->Pessoa->Cidade->codestado) {

                      $std = new \stdClass();
                      $std->item = $nItem; //item da NFe
                      $std->vBCUFDest = number_format($nfpb->icmsbase, 2, '.', '');
                      $std->vBCFCPUFDest = 0;
                      $std->pFCPUFDest = 0;
                      $std->pICMSUFDest = 0;

                      $std->pICMSInter = number_format(($nfpb->ProdutoBarra->Produto->importado)?4:12, 2, '.', '');
                      switch ($anoICMSInterPart) {
                        case '2016':
                          $std->pICMSInterPart = number_format(40, 2, '.', '');
                          break;
                        case '2017':
                          $std->pICMSInterPart = number_format(60, 2, '.', '');
                          break;
                        case '2018':
                          $std->pICMSInterPart = number_format(80, 2, '.', '');
                          break;
                        default:
                          $std->pICMSInterPart = number_format(100, 2, '.', '');
                          break;
                      }
                      $std->vFCPUFDest = 0;
                      $std->vICMSUFDest = 0;
                      $std->vICMSUFRemet = 0;
                      $elem = $nfe->tagICMSUFDest($std);
                  }

                  // PIS Produto
                  $std = new \stdClass();
                  $std->item = $nItem;
                  $std->CST = mascarar($nfpb->piscst, '##');
                  $std->vBC = number_format($nfpb->pisbase, 2, '.', '');
                  $std->pPIS = number_format($nfpb->pispercentual, 2, '.', '');
                  $std->vPIS = number_format($nfpb->pisvalor, 2, '.', '');
                  // if (!in_array($nfpb->piscst, ['49', '99', '70'])) {
                  //     $std->qBCProd = number_format(0, 2, '.', '');
                  //     $std->vAliqProd = number_format(0, 2, '.', '');
                  // }
                  $nfe->tagPIS($std);

                  // PISST
                  // $resp = $make->tagPISST($nItem, $vBC, $pPIS, $qBCProd, $vAliqProd, $vPIS);

                  // COFINS Produto
                  $std = new \stdClass();
                  $std->item = $nItem;
                  $std->CST = mascarar($nfpb->cofinscst, '##');
                  $std->vBC = number_format($nfpb->cofinsbase, 2, '.', '');
                  $std->pCOFINS = number_format($nfpb->cofinspercentual, 2, '.', '');
                  $std->vCOFINS = number_format($nfpb->cofinsvalor, 2, '.', '');
                  // if (!in_array($nfpb->cofinscst, ['49', '99', '70'])) {
                  //     $std->qBCProd = number_format(0, 2, '.', '');
                  //     $std->vAliqProd = number_format(0, 2, '.', '');
                  // }
                  $nfe->tagCOFINS($std);

                  // COFINSST
                  // $resp = $make->tagCOFINSST($nItem, $vBC, $pCOFINS, $qBCProd, $vAliqProd, $vCOFINS);

                  // IPI Produto
                  if (!empty($nfpb->ipivalor)) {
                      $std = new \stdClass();
                      $std->item = $nItem;
                      // TODO: Jogar logica cEnq para modelagem do banco de dados
                      if ($nfpb->ipicst == 4 || $nfpb->ipicst == 54) {
                          $std->cEnq = '001'; // Livros, jornais, periódicos e o papel destinado à sua impressão – Art. 18 Inciso I do Decreto 7.212/2010
                      } else {
                          $std->cEnq = '999'; // Tributação normal IPI; Outros;
                      }
                      $std->CST = str_pad($nfpb->ipicst, 2, '0', STR_PAD_LEFT);
                      $std->vIPI = number_format($nfpb->ipivalor, 2, '.', '');
                      $std->vBC = number_format($nfpb->ipibase, 2, '.', '');
                      $std->pIPI = number_format($nfpb->ipipercentual, 2, '.', '');
                      $nfe->tagIPI($std);
                  }

                  break;

              // SIMPLES
              default:

                  // ICMS
                  $std = new \stdClass();
                  $std->item = $nItem; //item da NFe
                  $std->orig = ($nfpb->ProdutoBarra->Produto->importado)?2:0;
                  $std->CSOSN = $nfpb->csosn;
                  // $std->pCredSN = number_format($nfpb->icmspercentual, 2, '.', '');
                  // $std->vCredICMSSN = number_format($nfpb->icmsvalor, 2, '.', '');
                  // $std->modBCST = '';
                  // $std->pMVAST = '';
                  // $std->pRedBCST = '';
                  // $std->vBCST = '';
                  // $std->pICMSST = '';
                  // $std->vICMSST = '';
                  // $std->vBCFCPST = null; //incluso no layout 4.00
                  // $std->pFCPST = null; //incluso no layout 4.00
                  // $std->vFCPST = null; //incluso no layout 4.00
                  // $std->vBCSTRet = '';
                  // $std->pST = null;
                  // $std->vICMSSTRet = '';
                  // $std->vBCFCPSTRet = null; //incluso no layout 4.00
                  // $std->pFCPSTRet = null; //incluso no layout 4.00
                  // $std->vFCPSTRet = null; //incluso no layout 4.00
                  // $std->modBC = '';
                  // $std->vBC = '';
                  // $std->pRedBC = '';
                  // $std->pICMS = '';
                  // $std->vICMS = '';
                  $elem = $nfe->tagICMSSN($std);

                  // PIS Produto
                  $std = new \stdClass();
                  $std->item = $nItem;
                  $std->CST = '01';
                  $std->vBC = 0;
                  $std->pPIS = 0;
                  $std->vPIS = 0;
                  $nfe->tagPIS($std);

                  // COFINS Produto
                  $std = new \stdClass();
                  $std->item = $nItem;
                  $std->CST = '01';
                  $std->vBC = 0;
                  $std->pCOFINS = 0;
                  $std->vCOFINS = 0;
                  $nfe->tagCOFINS($std);

                  break;
          }

          $totalPis += $nfpb->pisvalor;
          $totalCofins += $nfpb->cofinsvalor;

        }

        // Total ICMS da Nota
        $std = new \stdClass();
        if ($nf->Filial->crt == Filial::CRT_REGIME_NORMAL) {
          $std->vBC = number_format($nf->icmsbase, 2, '.', '');
          $std->vICMS = number_format($nf->icmsvalor, 2, '.', '');
        }
        // $std->vICMSDeson = 0.00;
        $std->vBCST = number_format($nf->icmsstbase, 2, '.', '');
        $std->vST = number_format($nf->icmsstvalor, 2, '.', '');
        $std->vProd = number_format($nf->valorprodutos, 2, '.', '');
        $std->vFrete = number_format($nf->valorfrete, 2, '.', '');
        $std->vSeg = number_format($nf->valorseguro, 2, '.', '');
        $std->vDesc = number_format($nf->valordesconto, 2, '.', '');
        // $std->vII = 0.00;
        $std->vIPI = number_format($nf->ipivalor, 2, '.', '');
        $std->vPIS = number_format($totalPis, 2, '.', '');
        $std->vCOFINS = number_format($totalCofins, 2, '.', '');
        $std->vOutro = number_format($nf->valoroutras, 2, '.', '');
        $std->vNF = number_format($nf->valortotal, 2, '.', '');
        $std->vTotTrib = number_format($totalTrib, 2, '.', '');
        $nfe->tagICMSTot($std);

        // Modalidade de Frete
        $std = new \stdClass();
        $std->modFrete = $nf->frete;
        $nfe->tagtransp($std);

        // Volumes
        // $std = new \stdClass();
        // $std->item = 1;
        // $std->qVol = 2;
        // $std->esp = 'caixa';
        // $std->marca = 'OLX';
        // $std->nVol = '11111';
        // $std->pesoL = 10.00;
        // $std->pesoB = 11.00;
        // $nfe->tagvol($std);

        // Faturas
        // $std = new \stdClass();
        // $std->nFat = '100';
        // $std->vOrig = 100;
        // $std->vLiq = 100;
        // $nfe->tagfat($std);

        $totalPrazo = 0;

        $std = new \stdClass();
        $std->vTroco = null;

        // caso total das parcelas seja maior que o total da nota, calcula vTroco
        // para casos de dizima como por exemplo NF #00830316
        if ($nf->modelo == NotaFiscal::MODELO_NFE) {
          $prazo = $nf->NotaFiscalDuplicatass()->where('vencimento', '>', Carbon::today())->sum('valor');
          if ($prazo > $nf->valortotal) {
              $std->vTroco = number_format($prazo - $nf->valortotal, 2, '.', '');
          }
        }
        $elem = $nfe->tagpag($std);

        // Informacoes de pagamento somente devem ser enviadas quando nao
        // for ajuste ou devolucao para evitar a Rejeicao:
        // 871 - Rejeicao: O campo Forma de Pagamento deve ser preenchido com a opcao Sem Pagamento
        if (in_array($nf->NaturezaOperacao->finnfe, [NaturezaOperacao::FINNFE_AJUSTE, NaturezaOperacao::FINNFE_DEVOLUCAO_RETORNO])) {

          $std = new \stdClass();
          $std->tPag = '90';
          $std->vPag = number_format($nf->valortotal, 2, '.', '');
          $elem = $nfe->tagdetPag($std);

        } else{

            // Duplicatas - Somente quando NFE
            if ($nf->modelo == NotaFiscal::MODELO_NFE) {

              $dups = [];
              $nDup = 0;
              foreach ($nf->NotaFiscalDuplicatass()->orderBy('vencimento')->orderBy('fatura')->orderBy('codnotafiscalduplicatas')->get() as $nfd) {

                // Se duplicata tiver vencimento <= hoje ocorre Rejeicao
                // 898 - Rejeicao: Data de vencimento da parcela nao informada ou menor que Data de Autorizacao
                if ($nfd->vencimento->isPast()) {
                    continue;
                }

                // Duplicatas
                $std = new \stdClass();
                $nDup++;
                //$std->nDup = Strings::replaceSpecialsChars($nfd->fatura);
                $std->nDup = mascarar($nDup, '###');
                #$nFat = $std->nDup;
                $nFat = Strings::replaceSpecialsChars($nfd->fatura);
                $std->dVenc = $nfd->vencimento->format('Y-m-d');
                $std->vDup = number_format($nfd->valor, 2, '.', '');
                $totalPrazo += $nfd->valor;
                $dups[] = $std;
              }

              if (!empty($totalPrazo)) {

                $std = new \stdClass();
                $std->nFat = $nFat;
                $std->vOrig = number_format($totalPrazo, 2, '.', '');
                $std->vDesc = null;
                $std->vLiq = number_format($totalPrazo, 2, '.', '');
                $elem = $nfe->tagfat($std);

                foreach ($dups as $dup) {
                  $nfe->tagdup($dup);
                }

              }
            }

            // Pagamento a Vista
            if ($nf->valortotal > $totalPrazo) {
              // TODO: Trazer informação do tipo de pagamento do negocio
              $std = new \stdClass();
              // 01=Dinheiro
              // 02=Cheque
              // 03=Cartão de Crédito
              // 04=Cartão de Débito
              // 05=Crédito Loja
              // 10=Vale Alimentação
              // 11=Vale Refeição
              // 12=Vale Presente
              // 13=Vale Combustível
              // 15=Boleto Bancário
              // 90=Sem Pagamento;
              // 99=Outros.
              $std->tPag = '01';
              $std->vPag = number_format($nf->valortotal - $totalPrazo, 2, '.', '');
              // $std->CNPJ = '12345678901234';
              // $std->tBand = '01';
              // $std->cAut = '3333333';
              // $std->tpIntegra = 1; //incluso na NT 2015/002
              $std->indPag = ($totalPrazo > 0)?1:0; //0= Pagamento à Vista 1= Pagamento à Prazo
              $elem = $nfe->tagdetPag($std);
            }

            // Pagamento a Prazo
            if ($totalPrazo > 0) {
              $std = new \stdClass();
              $std->tPag = '05';
              $std->vPag = number_format($totalPrazo, 2, '.', '');
              $std->indPag = 1; //0= Pagamento à Vista 1= Pagamento à Prazo
              $elem = $nfe->tagdetPag($std);
            }
        }

        $infCpl = '';

        // Mensagem IBPT - Valor dos Tributos
        if ($totalTrib > 0) {
            $infCpl = "Voce pagou aproximadamente:";
            if ($totalTribFederal > 0) {
                $infCpl .= " " . formataNumero($totalTribFederal) . " de tributos federais,";
            }
            if ($totalTribEstadual > 0) {
                $infCpl .= " " . formataNumero($totalTribEstadual) . " de tributos estaduais,";
            }
            if ($totalTribMunicipal > 0) {
                $infCpl .= " " . formataNumero($totalTribMunicipal) . " de tributos municipais";
            }
            $infCpl .= ". Fonte: {$ibptFonte}.;";
        }
        $infCpl .= $nf->observacoes;
        $infCpl = preg_replace('/\s+/', ' ', $infCpl);

        // MENSAGEM Aproveitamento ICMS - Simples
        $infCpl = str_replace("#ICMSVALOR#", formataNumero($nf->icmsvalor), $infCpl);
        if ($nf->icmsbase > 0 && $nf->icmsvalor > 0) {
            $perc = ($nf->icmsvalor / $nf->icmsbase) * 100;
        } else {
            $perc = 0;
        }
        $infCpl = str_replace("#ICMSPERCENTUAL#", formataNumero($perc), $infCpl);

        // Informacoes Adicionais
        $std = new \stdClass();
        // $std->infAdFisco = null;
        $std->infCpl = Strings::replaceSpecialsChars($infCpl);
        $nfe->taginfAdic($std);

        // Gera o XML
        $xml = $nfe->getXML(); // O conteúdo do XML fica armazenado na variável $xml

        // Salva no Banco de Dados a Chave da NFe
        $chave = $nfe->getChave();
        if ($chave != $nf->nfechave) {
            NotaFiscal::where('codnotafiscal', $nf->codnotafiscal)->update([
                'nfechave' => $chave
            ]);
        }

        // Retorna o XML
        $path = NFePHPRepositoryPath::pathNFeAssinada($nf, true);
        file_put_contents($path, $xml);
        return $xml;

    }

    public static function rateiaDescontoSeguroFreteOutras($nf) {

        // Inicializa totalizadores
        $vFreteTotal = 0;
        $vSegTotal = 0;
        $vDescTotal = 0;
        $vOutroTotal = 0;

        // busca itens da nota
        $nfpbs = $nf->NotaFiscalProdutoBarraS()->orderBy('valortotal')->orderBy('codnotafiscalprodutobarra')->get();

        // Calcula quantidade de registros
        $quantidade = $nfpbs->count();
        $i = 0;

        // Percorre itens da nota
        foreach ($nfpbs as $nfpb)
        {
            $i++;

            // se estiver no ultimo registro, faz o valor pela diferenca
            if ($i == $quantidade) {
                $vFrete = $nf->valorfrete - $vFreteTotal;
                $vSeg = $nf->valorseguro - $vSegTotal;
                $vDesc = $nf->valordesconto - $vDescTotal;
                $vOutro = $nf->valoroutras - $vOutroTotal;

            // Senao faz pela media ponderada
            } else {
                $vFrete = round(($nf->valorfrete / $nf->valorprodutos) * $nfpb->valortotal, 2);
                $vSeg = round(($nf->valorseguro / $nf->valorprodutos) * $nfpb->valortotal, 2);
                $vDesc = round(($nf->valordesconto / $nf->valorprodutos) * $nfpb->valortotal, 2);
                $vOutro = round(($nf->valoroutras / $nf->valorprodutos) * $nfpb->valortotal, 2);
            }

            // Busca
            $vFreteTotal += $vFrete;
            $vSegTotal += $vSeg;
            $vDescTotal += $vDesc;
            $vOutroTotal += $vOutro;

            $ret[$nfpb->codnotafiscalprodutobarra] = (object) [
              'vFrete' => $vFrete,
              'vSeg' => $vSeg,
              'vDesc' => $vDesc,
              'vOutro' => $vOutro,
            ];
        }

        return $ret;

    }


}
