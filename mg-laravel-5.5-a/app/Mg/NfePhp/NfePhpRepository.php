<?php

namespace Mg\NfePhp;

use Carbon\Carbon;
use DB;

use Mg\MgRepository;
use Mg\NotaFiscal\NotaFiscal;
use Mg\NaturezaOperacao\Operacao;
use Mg\Filial\Empresa;
use Mg\Filial\Filial;
use Mg\Produto\Barras;
use Mg\Pessoa\Pessoa;

use NFePHP\NFe\Make;
use NFePHP\NFe\Tools;
use NFePHP\NFe\Complements;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Factories\Protocol;
use NFePHP\Common\Certificate;
use NFePHP\Common\Strings;
use NFePHP\Ibpt\Ibpt;
use NFePHP\DA\NFe\Danfe;
use NFePHP\DA\Legacy\FilesFolders;


class NfePhpRepository extends MgRepository
{

    public static function config (Filial $filial)
    {
        $config = [
           "atualizacao" => "2018-02-06 06:01:21",
           "tpAmb" => $filial->nfeambiente, // Se deixar o tpAmb como 2 você emitirá a nota em ambiente de homologação(teste) e as notas fiscais aqui não tem valor fiscal
           "razaosocial" => $filial->Pessoa->pessoa,
           "siglaUF" => $filial->Pessoa->Cidade->Estado->sigla,
           "cnpj" => str_pad($filial->Pessoa->cnpj, 14, '0', STR_PAD_LEFT),
           "schemes" => "PL_009_V4",
           "versao" => "4.00",
           "tokenIBPT" => $filial->tokenibpt
        ];
        return json_encode($config);
    }

    public static function instanciaTools (Filial $filial)
    {
        // Monta Configuracao da Filial
        $config = static::config($filial);

        // Le Certificado Digital
        $pfx = file_get_contents(env('NFE_PHP_PATH') . "/Certs/{$filial->codfilial}.pfx");

        // retorna Instancia Tools para a configuracao e certificado
        return new Tools($config, Certificate::readPfx($pfx, $filial->senhacertificado));
    }

    public static function pathNFeAssinada (NotaFiscal $nf, bool $criar = false)
    {
        $path = env('NFE_PHP_PATH') . "/NFe/{$nf->codfilial}/homologacao/assinadas/";
        $path .= $nf->emissao->format('Ym');
        if ($criar) {
            @mkdir($path, 0775, true);
        }
        $path .= "/{$nf->nfechave}-NFe.xml";
        return $path;
    }

    public static function pathNFeAutorizada (NotaFiscal $nf, bool $criar = false)
    {
        $path = env('NFE_PHP_PATH') . "/NFe/{$nf->codfilial}/homologacao/enviadas/aprovadas/";
        $path .= $nf->emissao->format('Ym');
        if ($criar) {
            @mkdir($path, 0775, true);
        }
        $path .= "/{$nf->nfechave}-NFe.xml";
        return $path;
    }

    public static function pathNFeDenegada (NotaFiscal $nf, bool $criar = false)
    {
        $path = env('NFE_PHP_PATH') . "/NFe/{$nf->codfilial}/homologacao/enviadas/denegadas/";
        $path .= $nf->emissao->format('Ym');
        if ($criar) {
            @mkdir($path, 0775, true);
        }
        $path .= "/{$nf->nfechave}-NFe.xml";
        return $path;
    }

    public static function pathNFeCancelada (NotaFiscal $nf, bool $criar = false)
    {
        $path = env('NFE_PHP_PATH') . "/NFe/{$nf->codfilial}/homologacao/canceladas/";
        $path .= $nf->emissao->format('Ym');
        if ($criar) {
            @mkdir($path, 0775, true);
        }
        $path .= "/{$nf->nfechave}-NFe.xml";
        return $path;
    }

    public static function gerarNumeroNotaFiscal(NotaFiscal $nf) {

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

    public static function sefazStatus ($codfilial)
    {
        $filial = Filial::findOrFail($codfilial);
        $tools = static::instanciaTools($filial);
        $resp = $tools->sefazStatus();
        $st = new Standardize();
        $r = $st->toStd($resp);
        return $r;
    }

    public static function criarXml ($codnotafiscal)
    {

        $nf = NotaFiscal::findOrFail($codnotafiscal);

        // Confere se nota Fiscal tem Número
        if (empty($nf->numero)) {
            if (!static::gerarNumeroNotaFiscal($nf)) {
                abort(500, 'Erro ao Gerar Número da Nota Fiscal!');
            }
            $nf = $nf->fresh();
        }

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

            if (!empty($std->Pessoa->ie)) {
                abort(409, 'Não é permitida emissão de NFCe para Pessoas com Inscrição Estadual!');
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
                $std->dhCont = $nf->Filial->Empresa->contingenciadata->toW3cString();
                $std->xJust = $nf->Filial->Empresa->contingenciajustificativa; //Justificativa da entrada em contingência

                if ($std->finNFe != 1) {
                    abort(409, "Finalidade de emissão ({$std->finNFe}) da Natureza de Operação não permite emissão OFFLINE!");
                }

                if ($std->indFinal != 1) {
                    abort(409, 'Não é permitida emissão OFFLINE para Pessoas que não sejam Consumidor Final!');
                }

                if ($std->idDest != 1) {
                    abort(409, "Não é permitida emissão OFFLINE para Pessoas de fora do Estado de {$nf->Filial->Pessoa->Cidade->Estado->estado}!");
                }

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
                $std->indIEDest = (empty($std->IE)) ? '9' : '1';
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

        // Se for consumidor não identificado numa NFe
        } elseif ($nf->modelo != NotaFiscal::MODELO_NFCE) {
            abort(409, 'Consumidor não identificado é permitido somente em NFCe!');
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
        $ibpt = new Ibpt(mascarar($nf->Filial->Pessoa->cnpj, '##############'), $nf->Filial->tokenibpt);

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
          $std->cEAN = Strings::replaceSpecialsChars(Barras::validar($nfpb->ProdutoBarra->barras)?$nfpb->ProdutoBarra->barras:'');
          $std->xProd = Strings::replaceSpecialsChars($nfpb->descricaoalternativa??$nfpb->ProdutoBarra->descricao);
          $std->NCM = Strings::replaceSpecialsChars($nfpb->ProdutoBarra->Produto->Ncm->ncm);
          $std->CFOP = $nfpb->codcfop;
          $std->uCom = Strings::replaceSpecialsChars($nfpb->ProdutoBarra->UnidadeMedida->sigla);
          $std->qCom = number_format($nfpb->quantidade, 3, '.', '');
          $std->vUnCom = number_format($nfpb->valorunitario, 10, '.', '');
          $std->vProd = number_format($nfpb->valortotal, 2, '.', '');
          $std->cEANTrib = $std->cEAN;
          $std->uTrib = Strings::replaceSpecialsChars($nfpb->ProdutoBarra->UnidadeMedida->sigla); //number_format($nf->valorunitario, 3, '.', '');
          $std->qTrib = number_format($nfpb->quantidade, 3, '.', '');
          $std->vUnTrib = number_format($nfpb->valorunitario, 10, '.', '');
          if (!empty($nf->valorfrete)) {
              $std->vFrete = round(($nf->valorfrete / $nf->valorprodutos) * $nfpb->valortotal, 2);
          }
          if (!empty($nf->valorseguro)) {
              $std->vSeg = round(($nf->valorseguro / $nf->valorprodutos) * $nfpb->valortotal, 2);
          }
          if (!empty($nf->valordesconto)) {
              $std->vDesc = round(($nf->valordesconto / $nf->valorprodutos) * $nfpb->valortotal, 2);
          }
          if (!empty($nf->valoroutras)) {
              $std->vOutro = round(($nf->valoroutras / $nf->valorprodutos) * $nfpb->valortotal, 2);
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
              $tax = $ibpt->productTaxes(
                  $nf->Filial->Pessoa->Cidade->Estado->sigla,
                  $nfpb->ProdutoBarra->Produto->Ncm->ncm,
                  0
              );

              // Se nao houve erro ao consultar
              if (!isset($tax->error)) {

                  // monta string com fonte do IBPT para utilizar nos Dados Adicionais
                  $ibptFonte = "{$tax->Fonte} {$tax->Chave} {$tax->Versao}";

                  // Valcula valor dos tributos
                  $vTotTribFederal = ($nfpb->valortotal * (($nfpb->ProdutoBarra->Produto->importado)?$tax->Importado:$tax->Nacional)) / 100;
                  $vTotTribEstadual = ($nfpb->valortotal * $tax->Estadual) / 100;
                  $vTotTribMunicipal = ($nfpb->valortotal * $tax->Municipal) / 100;
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
                      // $std->vBCFCPUFDest = ;
                      // $std->pFCPUFDest = 0;
                      // $std->pICMSUFDest = 0;
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
                      // $std->vFCPUFDest = 0;
                      // $std->vICMSUFDest = 0;
                      // $std->vICMSUFRemet = 0;
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

        // Duplicatas - Somente quando NFE
        if ($nf->modelo == NotaFiscal::MODELO_NFE) {
            foreach ($nf->NotaFiscalDuplicatass()->orderBy('vencimento')->orderBy('fatura')->orderBy('codnotafiscalduplicatas')->get() as $nfd) {
                // Duplicatas
                $std = new \stdClass();
                $std->nDup = Strings::replaceSpecialsChars($nfd->fatura);
                $std->dVenc = $nfd->vencimento->format('Y-m-d');
                $std->vDup = number_format($nfd->valor, 2, '.', '');
                $totalPrazo += $nfd->valor;
                $nfe->tagdup($std);
            }
        }

        $std = new \stdClass();
        $std->vTroco = null; //incluso no layout 4.00, obrigatório informar para NFCe (65)
        $elem = $nfe->tagpag($std);

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
            $infCpl .= ". Fonte: {$ibptFonte}. ";
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
        return $xml;

    }

    public static function assinarXml($codnotafiscal)
    {

        // Busca Nota Fsical no Banco de Dados
        $nf = NotaFIscal::findOrFail($codnotafiscal);

        // Instancia Tools para a configuracao e certificado
        $tools = static::instanciaTools($nf->Filial);

        // Cria Arquivo XML
        $xml = static::criarXml($codnotafiscal);

        // Assina XML
        $xmlAssinado = $tools->signNFe($xml);

        // Grava arquivo XML Assinado na pasta de "assinadas"
        $nf = $nf->fresh();
        $path = static::pathNFeAssinada($nf, true);
        file_put_contents($path, $xmlAssinado);

        // Retorna XML Assinado
        return $xmlAssinado;
    }

    public static function enviarXml($codnotafiscal)
    {

        // Busca Nota Fsical no Banco de Dados
        $nf = NotaFIscal::findOrFail($codnotafiscal);

        // Instancia Tools para a configuracao e certificado
        $tools = static::instanciaTools($nf->Filial);

        // Carrega Arquivo XML Assinado
        $path = static::pathNFeAssinada($nf);
        $xmlAssinado = file_get_contents($path);

        // Monta Configuracao do Lote
        $idLote = str_pad(1, 15, '0', STR_PAD_LEFT);

        // Envia Lote para Sefaz
        $resp = $tools->sefazEnviaLote([$xmlAssinado], $idLote);
        $st = new Standardize();
        $respStd = $st->toStd($resp);

        // inicializa variaveis para retorno
        $sucesso = false;
        $cStat = null;
        $xMotivo = 'Falha Comunicação SEFAZ!';

        // Se veio cStat
        if (isset($respStd->cStat)) {

            // Se Lote Recebido Com Sucesso
            if ($respStd->cStat == 103) {

                // Salva Numero do Protocolo na tabela de Nota Fiscal
                NotaFiscal::where('codnotafiscal', $codnotafiscal)->update([
                    'nfereciboenvio' => $respStd->infRec->nRec,
                    'nfedataenvio' => Carbon::parse($respStd->dhRecbto)
                ]);
                $nf = $nf->fresh();
                $sucesso = true;
            }

            // joga mensagem recebida da Sefaz para Variaveis de Retorno
            $cStat = $respStd->cStat;
            $xMotivo = $respStd->xMotivo;

        }

        // Retorna Resultado do processo
        return [
            'sucesso' => $sucesso,
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'nfereciboenvio' => $nf->nfereciboenvio,
            'nfedataenvio' => ($nf->nfedataenvio)?$nf->nfedataenvio->toW3cString():null,
            'resp' => $resp,
        ];

    }

    public static function vincularProtocoloAutorizacao (NotaFiscal $nf, $resp, $respStd)
    {
        // Verifica se tem o infProt
        if (!isset($respStd->protNFe->infProt)) {
          return false;
        }
        $infProt = $respStd->protNFe->infProt;

        // Guarda no Banco de Dados informação da Autorização
        NotaFiscal::where('codnotafiscal', $nf->codnotafiscal)->update([
          'nfeautorizacao' => $infProt->nProt,
          'nfedataautorizacao' => Carbon::parse($infProt->dhRecbto)
        ]);

        // Carrega o Arquivo com o XML Assinado
        $pathAssinada = static::pathNFeAssinada($nf);
        $xmlAssinado = file_get_contents($pathAssinada);

        // Vincula o Protocolo no XML Assinado
        $prot = new Protocol();
        $xmlProtocolado = $prot->add($xmlAssinado, $resp);

        // Salva o Arquivo com a NFe Aprovada
        $pathAprovada = static::pathNFeAutorizada($nf, true);
        file_put_contents($pathAprovada, $xmlProtocolado);

        return true;
    }

    public static function vincularProtocoloDenegacao (NotaFiscal $nf, $resp, $respStd)
    {
        // Verifica se tem o infProt
        if (!isset($respStd->protNFe->infProt)) {
          return false;
        }
        $infProt = $respStd->protNFe->infProt;

        // Guarda no Banco de Dados informação da Autorização
        NotaFiscal::where('codnotafiscal', $nf->codnotafiscal)->update([
          'nfeinutilizacao' => $infProt->nProt,
          'justificativa' => $infProt->xMotivo,
          'nfedatainutilizacao' => Carbon::parse($infProt->dhRecbto)
        ]);

        // Carrega o Arquivo com o XML Assinado
        $pathAssinada = static::pathNFeAssinada($nf);
        $xmlAssinado = file_get_contents($pathAssinada);

        // Vincula o Protocolo no XML Assinado
        $prot = new Protocol();
        $xmlProtocolado = $prot->add($xmlAssinado, $resp);

        // Salva o Arquivo com a NFe Aprovada
        $pathNFeDenegada = static::pathNFeDenegada($nf, true);
        file_put_contents($pathNFeDenegada, $xmlProtocolado);

        return true;
    }

    public static function vincularProtocoloCancelamento (NotaFiscal $nf, $resp, $respStd, $justificativa, $tools)
    {
        // Verifica se tem o infEvento
        if (!isset($respStd->retEvento->infEvento)) {
          return false;
        }
        $infEvento = $respStd->retEvento->infEvento;

        // Guarda no Banco de Dados informação da Autorização
        NotaFiscal::where('codnotafiscal', $nf->codnotafiscal)->update([
          'justificativa' => $justificativa,
          'nfecancelamento' => $infEvento->nProt,
          'nfedatacancelamento' => Carbon::parse($infEvento->dhRegEvento)
        ]);

        // Pega XML do Cancelamento
        $xmlProtocolado = Complements::toAuthorize($tools->lastRequest, $resp);

        // Salva o Arquivo com a NFe Aprovada
        $pathNFeCancelada = static::pathNFeCancelada($nf, true);
        file_put_contents($pathNFeCancelada, $xmlProtocolado);

        return true;
    }

    public static function vincularProtocoloInutilizacao (NotaFiscal $nf, $resp, $respStd, $justificativa)
    {
        // Verifica se tem o infInut
        if (!isset($respStd->infInut)) {
            return false;
        }
        $infInut = $respStd->infInut;

        // Guarda no Banco de Dados informação da Autorização
        NotaFiscal::where('codnotafiscal', $nf->codnotafiscal)->update([
          'justificativa' => $justificativa,
          'nfeinutilizacao' => $infInut->nProt,
          'nfedatainutilizacao' => Carbon::parse($infInut->dhRecbto)
        ]);

        return true;
    }

    public static function enviarXmlSincrono($codnotafiscal)
    {

      // Busca Nota Fsical no Banco de Dados
      $nf = NotaFIscal::findOrFail($codnotafiscal);

      // Instancia Tools para a configuracao e certificado
      $tools = static::instanciaTools($nf->Filial);

      // Carrega Arquivo XML Assinado
      $path = static::pathNFeAssinada($nf);
      $xmlAssinado = file_get_contents($path);

      // Monta Configuracao do Lote
      $idLote = str_pad(1, 15, '0', STR_PAD_LEFT);

      // Envia Lote para Sefaz
      $resp = $tools->sefazEnviaLote([$xmlAssinado], $idLote, 1);
      $st = new Standardize();
      $respStd = $st->toStd($resp);

      // inicializa variaveis para retorno
      $sucesso = false;
      $cStat = null;
      $xMotivo = 'Falha Comunicação SEFAZ!';

      // Se veio cStat do Protocolo
      if (isset($respStd->protNFe->infProt->cStat)) {

          // Se Autorizado
          // 100 - Autorizado o uso da NF-e
          // 150 - Autorizado o uso da NF-e, autorizacao fora de prazo
          if (in_array($respStd->protNFe->infProt->cStat, [100, 150])) {
              static::vincularProtocoloAutorizacao($nf, $resp, $respStd);
              $nf = $nf->fresh();
              $sucesso = true;
          }

          // Se Denegada
          // 301 Uso Denegado: Irregularidade fiscal do emitente
          // 302 Uso Denegado: Irregularidade fiscal do destinatário
          if (in_array($respStd->protNFe->infProt->cStat, [301, 302])) {
              static::vincularProtocoloDenegacao($nf, $resp, $respStd);
              $nf = $nf->fresh();
          }

          // joga mensagem recebida da Sefaz para Variaveis de Retorno
          $cStat = $respStd->protNFe->infProt->cStat;
          $xMotivo = $respStd->protNFe->infProt->xMotivo;

      // Se veio cStat na Raiz
      } elseif (isset($respStd->cStat)) {
          $cStat = $respStd->cStat;
          $xMotivo = $respStd->xMotivo;
      }

      // Retorna Resultado do processo
      return [
        'sucesso' => $sucesso,
        'cStat' => $cStat,
        'xMotivo' => $xMotivo,
        'nfeautorizacao' => $nf->nfeautorizacao,
        'nfedataautorizacao' => ($nf->nfedataautorizacao)?$nf->nfedataautorizacao->toW3cString():null,
        'resp' => $resp
        ];

    }

    public static function consultarReciboEnvio($codnotafiscal)
    {

        // Busca Nota Fsical no Banco de Dados
        $nf = NotaFIscal::findOrFail($codnotafiscal);

        // Instancia Tools para a configuracao e certificado
        $tools = static::instanciaTools($nf->Filial);

        // Busca na sefaz status do recibo
        $resp = $tools->sefazConsultaRecibo($nf->nfereciboenvio);
        $st = new Standardize();
        $respStd = $st->toStd($resp);

        // inicializa variaveis para retorno
        $sucesso = false;
        $cStat = null;
        $xMotivo = 'Falha Comunicação SEFAZ!';

        // Se veio cStat do Protocolo
        if (isset($respStd->protNFe->infProt->cStat)) {

            // Se Autorizado
            // 100 - Autorizado o uso da NF-e
            // 150 - Autorizado o uso da NF-e, autorizacao fora de prazo
            if (in_array($respStd->protNFe->infProt->cStat, [100, 150])) {
                static::vincularProtocoloAutorizacao($nf, $resp, $respStd);
                $nf = $nf->fresh();
                $sucesso = true;
            }

            // Se Denegada
            // 301 Uso Denegado: Irregularidade fiscal do emitente
            // 302 Uso Denegado: Irregularidade fiscal do destinatário
            if (in_array($respStd->protNFe->infProt->cStat, [301, 302])) {
                static::vincularProtocoloDenegacao($nf, $resp, $respStd);
                $nf = $nf->fresh();
            }

            // joga mensagem recebida da Sefaz para Variaveis de Retorno
            $cStat = $respStd->protNFe->infProt->cStat;
            $xMotivo = $respStd->protNFe->infProt->xMotivo;

        }

        // Retorna Resultado do processo
        return [
            'sucesso' => $sucesso,
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'nfeautorizacao' => $nf->nfeautorizacao,
            'nfedataautorizacao' => ($nf->nfedataautorizacao)?$nf->nfedataautorizacao->toW3cString():null,
            'resp' => $resp
        ];

    }

    public static function cancelar($codnotafiscal, $justificativa)
    {

        // Busca Nota Fsical no Banco de Dados
        $nf = NotaFiscal::findOrFail($codnotafiscal);

        // Instancia Tools para a configuracao e certificado
        $tools = static::instanciaTools($nf->Filial);

        // solicita a sefaz cancelamento
        $tools->model('55');
        $resp = $tools->sefazCancela($nf->nfechave, $justificativa, $nf->nfeautorizacao);
        $st = new Standardize();
        $respStd = $st->toStd($resp);

        // inicializa variaveis para retorno
        $sucesso = false;
        $cStat = null;
        $xMotivo = 'Falha Comunicação SEFAZ!';

        // Se veio cStat do Protocolo
        if (isset($respStd->retEvento->infEvento->cStat)) {

            // Se Autorizado
            // 101 - Cancelamento de NFe Homologado
            // 135 - Evento registrado e vinculado A NFe
            // 155 - Cancelamento Homologado Fora de Prazo
            if (in_array($respStd->retEvento->infEvento->cStat, [101, 135, 155])) {
                static::vincularProtocoloCancelamento ($nf, $resp, $respStd, $justificativa, $tools);
                $nf = $nf->fresh();
                $sucesso = true;
            }

            // joga mensagem recebida da Sefaz para Variaveis de Retorno
            $cStat = $respStd->retEvento->infEvento->cStat;
            $xMotivo = $respStd->retEvento->infEvento->xMotivo;

        }

        // Retorna Resultado do processo
        return [
            'sucesso' => $sucesso,
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'nfecancelamento' => $nf->nfecancelamento,
            'nfedatanfecancelamento' => ($nf->nfedatanfecancelamento)?$nf->nfedatanfecancelamento->toW3cString():null,
            'resp' => $resp
        ];

    }

    public static function inutilizar($codnotafiscal, $justificativa)
    {

        // Busca Nota Fsical no Banco de Dados
        $nf = NotaFiscal::findOrFail($codnotafiscal);

        // Instancia Tools para a configuracao e certificado
        $tools = static::instanciaTools($nf->Filial);

        // solicita a sefaz cancelamento
        $tools->model('55');
        $resp = $tools->sefazInutiliza($nf->serie, $nf->numero, $nf->numero, $justificativa, $nf->Filial->nfeambiente);
        $st = new Standardize();
        $respStd = $st->toStd($resp);

        //return $respStd;

        // inicializa variaveis para retorno
        $sucesso = false;
        $cStat = null;
        $xMotivo = 'Falha Comunicação SEFAZ!';

        // Se veio cStat do Protocolo
        if (isset($respStd->infInut->cStat)) {

            // Se Inutilizacao Homologada
            // 102 - Inutilizacao de Numero Homologado
            if (in_array($respStd->infInut->cStat, [102])) {
                static::vincularProtocoloInutilizacao ($nf, $resp, $respStd, $justificativa);
                $nf = $nf->fresh();
                $sucesso = true;
            }

            // joga mensagem recebida da Sefaz para Variaveis de Retorno
            $cStat = $respStd->infInut->cStat;
            $xMotivo = $respStd->infInut->xMotivo;

        }

        // Retorna Resultado do processo
        return [
            'sucesso' => $sucesso,
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'nfeinutilizacao' => $nf->nfeinutilizacao,
            'nfedatainutilizacao' => ($nf->nfedatainutilizacao)?$nf->nfedatainutilizacao->toW3cString():null,
            'resp' => $resp
        ];

    }

    public static function consultar($codnotafiscal)
    {

        // Busca Nota Fsical no Banco de Dados
        $nf = NotaFiscal::findOrFail($codnotafiscal);

        // Instancia Tools para a configuracao e certificado
        $tools = static::instanciaTools($nf->Filial);

        // solicita a sefaz cancelamento
        $tools->model('55');
        $chave = '52170522555994000145550010000009651275106690';
        //$chave = '51180604576775000322550010000187011000187012';
        $resp = $tools->sefazConsultaChave($chave, $nf->Filial->nfeambiente);
        //$resp = $tools->sefazConsultaChave($nf->nfechave, $nf->Filial->nfeambiente);
        $st = new Standardize();
        $respStd = $st->toStd($resp);

        return $respStd;

        //return $respStd;

        // inicializa variaveis para retorno
        $sucesso = false;
        $cStat = null;
        $xMotivo = 'Falha Comunicação SEFAZ!';

        // Se veio cStat do Protocolo
        if (isset($respStd->infInut->cStat)) {

            // Se Inutilizacao Homologada
            // 102 - Inutilizacao de Numero Homologado
            if (in_array($respStd->infInut->cStat, [102])) {
                static::vincularProtocoloInutilizacao ($nf, $resp, $respStd, $justificativa);
                $nf = $nf->fresh();
                $sucesso = true;
            }

            // joga mensagem recebida da Sefaz para Variaveis de Retorno
            $cStat = $respStd->infInut->cStat;
            $xMotivo = $respStd->infInut->xMotivo;

        }

        // Retorna Resultado do processo
        return [
            'sucesso' => $sucesso,
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'nfeinutilizacao' => $nf->nfeinutilizacao,
            'nfedatainutilizacao' => ($nf->nfedatainutilizacao)?$nf->nfedatainutilizacao->toW3cString():null,
            'resp' => $resp
        ];

    }

    public static function danfe($codnotafiscal)
    {

        // Busca Nota Fsical no Banco de Dados
        $nf = NotaFiscal::findOrFail($codnotafiscal);

        // busca XML autorizado
        $pathNFeAutorizada = static::pathNFeAutorizada($nf);
        $xml = file_get_contents($pathNFeAutorizada);

        $danfe = new Danfe($xml, 'P', 'A4', 'images/logo.jpg', 'I', '');
        $id = $danfe->montaDANFE();
        dd($id);
        $pdf = $danfe->render();

    }

}
