<?php

namespace Mg\Mdfe;

// use DB;
use Carbon\Carbon;

use NFePHP\MDFe\Make;
use NFePHP\MDFe\Complements;
use NFePHP\MDFe\Common\Standardize;
use NFePHP\Common\Keys;
use NFePHP\Common\Strings;
use NFePHP\DA\MDFe\Damdfe;

class MdfeNfePhpService
{

    public static function validarPreenchimento (Mdfe $mdfe)
    {
        $mdfeVeiculos = $mdfe->MdfeVeiculoS;
        if (empty($mdfeVeiculos)) {
            throw new \Exception("Nenhum Veículo informado!", 1);
        }
        foreach ($mdfeVeiculos as $mdfeVeiculo) {
            if ($mdfeVeiculo->Veiculo->VeiculoTipo->tracao) {
                if (empty($mdfeVeiculo->codpessoacondutor)) {
                    throw new \Exception("Condutor do Veículo de Tração não informado!", 1);
                }
            }
        }
    }

    public static function criarXml (Mdfe $mdfe)
    {

        static::validarPreenchimento($mdfe);

        $mdfe = MdfeService::atribuirNumero($mdfe);

        $make = new Make();
        $make->setOnlyAscii(true);

        /*
         * Grupo ide ( Identificação )
         */
        $std = new \stdClass();
        $std->cUF = $mdfe->Filial->Pessoa->Cidade->Estado->codigooficial;
        $std->tpAmb = $mdfe->Filial->nfeambiente;
        $std->tpEmit = $mdfe->tipoemitente;
        // 458 - Rejeição: Tipo de Transportador não deve ser informado para Emitente de Carga Própria proprietário do veículo
        if ($mdfe->tipoemitente != Mdfe::TIPO_EMITENTE_CARGA_PROPRIA) {
            $std->tpTransp = $mdfe->tipotransportador;
        }
        $std->mod = $mdfe->modelo;
        $std->serie = $mdfe->serie;
        $std->nMDF = $mdfe->numero;
        $std->cMDF = 99999999 - $mdfe->numero;
        $std->cDV = '?';
        $std->modal = $mdfe->modal;
        $std->dhEmi = $mdfe->emissao->toIso8601String();
        $std->tpEmis = $mdfe->tipoemissao;
        $std->procEmi = '0';
        $std->verProc = '1.0';
        $std->UFIni = $mdfe->CidadeCarregamento->Estado->sigla;
        $std->UFFim = $mdfe->EstadoFim->sigla;
        $std->dhIniViagem = $mdfe->inicioviagem->toIso8601String();
        // $std->indCanalVerde = '1';
        // $std->indCarregaPosterior = '1';
        $make->tagide($std);

        // for {
        $infMunCarrega = new \stdClass();
        $infMunCarrega->cMunCarrega = $mdfe->CidadeCarregamento->codigooficial;
        $infMunCarrega->xMunCarrega = $mdfe->CidadeCarregamento->cidade;
        $make->taginfMunCarrega($infMunCarrega);
        // }

        foreach ($mdfe->MdfeEstadoS as $mdfeEstado) {
            $infPercurso = new \stdClass();
            $infPercurso->UFPer = $mdfeEstado->Estado->sigla;
            $make->taginfPercurso($infPercurso);
        }

        /*
         * fim ide
         */

        /*
         * Grupo emit ( Emitente )
         */
        $std = new \stdClass();
        if ($mdfe->Filial->Pessoa->fisica) {
            $std->CPF = str_pad($mdfe->Filial->Pessoa->cnpj, 11, '0', STR_PAD_LEFT);
        } else {
            $std->CNPJ = str_pad($mdfe->Filial->Pessoa->cnpj, 14, '0', STR_PAD_LEFT);
        }
        $std->IE = $mdfe->Filial->Pessoa->ie;
        $std->xNome = $mdfe->Filial->Pessoa->pessoa;
        $std->xFant = $mdfe->Filial->Pessoa->fantasia;
        $make->tagemit($std);

        $std = new \stdClass();
        $std->xLgr = $mdfe->Filial->Pessoa->endereco;
        $std->nro = $mdfe->Filial->Pessoa->numero;
        $std->xBairro = $mdfe->Filial->Pessoa->bairro;
        $std->cMun = $mdfe->Filial->Pessoa->Cidade->codigooficial;
        $std->xMun = $mdfe->Filial->Pessoa->Cidade->cidade;
        $std->CEP = str_pad($mdfe->Filial->Pessoa->cep, 8, '0', STR_PAD_LEFT);
        $std->UF = $mdfe->Filial->Pessoa->Cidade->Estado->sigla;
        $std->fone = Strings::onlyNumbers($mdfe->Filial->Pessoa->telefone1);
        $std->email = $mdfe->Filial->Pessoa->email;
        $make->tagenderEmit($std);
        /*
         * fim emit
         */

        /*
         * Grupo rodo ( Rodoviário )
         */

        /* Grupo infANTT */
        $infANTT = new \stdClass();
        $infANTT->RNTRC = $mdfe->Filial->Pessoa->rntrc;
        $make->taginfANTT($infANTT);

        /* informações do CIOT */
        // for {
        // $infCIOT = new \stdClass();
        // $infCIOT->CIOT = '123456789012';
        // $infCIOT->CPF = '11122233344';
        // $infCIOT->CNPJ = '11222333444455';
        // $make->taginfCIOT($infCIOT);
        // }

        /* informações do Vale Pedágio */
        // for {
        // $valePed = new \stdClass();
        // $valePed->CNPJForn = '11222333444455';
        // $valePed->CNPJPg = '66777888999900';
        // $valePed->CPFPg = '11122233355';
        // $valePed->nCompra = '777778888999999';
        // $valePed->vValePed = '100.00';
        // $make->tagdisp($valePed);
        // }

        /* informações do contratante */
        // for {
        // $infContratante = new \stdClass();
        // $infContratante->CNPJ = '09230232000372';
        // $make->taginfContratante($infContratante);
        // }

        /* fim infANTT */

        foreach ($mdfe->MdfeVeiculoS as $mdfeVeiculo) {
            if ($mdfeVeiculo->Veiculo->VeiculoTipo->tracao) {

                /* Grupo veicTracao */
                $veicTracao = new \stdClass();
                $veicTracao->cInt = $mdfeVeiculo->Veiculo->codveiculo;
                $veicTracao->placa = $mdfeVeiculo->Veiculo->placa;
                $veicTracao->tara = $mdfeVeiculo->Veiculo->tara??0;
                if (!empty($mdfeVeiculo->Veiculo->capacidade)) {
                    $veicTracao->capKG = $mdfeVeiculo->Veiculo->capacidade;
                }
                if (!empty($mdfeVeiculo->Veiculo->capacidadem3)) {
                    $veicTracao->capM3 = $mdfeVeiculo->Veiculo->capacidadem3;
                }
                $veicTracao->tpRod = str_pad($mdfeVeiculo->Veiculo->VeiculoTipo->tiporodado, 2, '0', STR_PAD_LEFT);
                $veicTracao->tpCar = str_pad($mdfeVeiculo->Veiculo->VeiculoTipo->tipocarroceria, 2, '0', STR_PAD_LEFT);
                $veicTracao->UF = $mdfeVeiculo->Veiculo->Estado->sigla;

                // Condutor
                if (!empty($mdfeVeiculo->codpessoacondutor)) {
                    $condutor = new \stdClass();
                    $condutor->xNome = $mdfeVeiculo->PessoaCondutor->pessoa;
                    if (!$mdfeVeiculo->PessoaCondutor->fisica) {
                        throw new \Exception("Informado condutor pessoa jurídica!", 1);
                    }
                    $condutor->CPF = str_pad($mdfeVeiculo->PessoaCondutor->cnpj, 11, '0', STR_PAD_LEFT);
                    $veicTracao->condutor = [$condutor];
                }

                // Proprietario
                if (!empty($mdfeVeiculo->Veiculo->codpessoaproprietario)) {
                    $prop = new \stdClass();
                    if ($mdfeVeiculo->Veiculo->PessoaProprietario->fisica) {
                        $prop->CPF = str_pad($mdfeVeiculo->Veiculo->PessoaProprietario->cnpj, 11, '0', STR_PAD_LEFT);
                    } else {
                        $prop->CNPJ = str_pad($mdfeVeiculo->Veiculo->PessoaProprietario->cnpj, 14, '0', STR_PAD_LEFT);
                    }
                    $prop->RNTRC = $mdfeVeiculo->Veiculo->PessoaProprietario->rntrc;
                    $prop->xNome = $mdfeVeiculo->Veiculo->PessoaProprietario->pessoa;
                    $prop->IE = $mdfeVeiculo->Veiculo->PessoaProprietario->ie??'ISENTO';
                    $prop->UF = $mdfeVeiculo->Veiculo->PessoaProprietario->Cidade->Estado->sigla;
                    $prop->tpProp = $mdfeVeiculo->Veiculo->tipoproprietario??2;
                    $veicTracao->prop = $prop;
                }

                $make->tagveicTracao($veicTracao);
                /* fim veicTracao */

            } elseif ($mdfeVeiculo->Veiculo->VeiculoTipo->reboque) {

                /* Grupo veicReboque */
                $veicReboque = new \stdClass();
                $veicReboque->cInt = $mdfeVeiculo->Veiculo->codveiculo;
                $veicReboque->placa = $mdfeVeiculo->Veiculo->placa;
                $veicReboque->tara = $mdfeVeiculo->Veiculo->tara??0;
                if (!empty($mdfeVeiculo->Veiculo->capacidade)) {
                    $veicReboque->capKG = $mdfeVeiculo->Veiculo->capacidade;
                }
                if (!empty($mdfeVeiculo->Veiculo->capacidadem3)) {
                    $veicReboque->capM3 = $mdfeVeiculo->Veiculo->capacidadem3;
                }
                if (empty($veicTracao->capM3) && empty($veicTracao->capKG)) {
                    $veicReboque->capKG = 0;
                }
                $veicReboque->tpCar = str_pad($mdfeVeiculo->Veiculo->VeiculoTipo->tipocarroceria, 2, '0', STR_PAD_LEFT);
                $veicReboque->UF = $mdfeVeiculo->Veiculo->Estado->sigla;

                // Proprietario
                if (!empty($mdfeVeiculo->Veiculo->codpessoaproprietario)) {
                    $prop = new \stdClass();
                    if ($mdfeVeiculo->Veiculo->PessoaProprietario->fisica) {
                        $prop->CPF = str_pad($mdfeVeiculo->Veiculo->PessoaProprietario->cnpj, 11, '0', STR_PAD_LEFT);
                    } else {
                        $prop->CNPJ = str_pad($mdfeVeiculo->Veiculo->PessoaProprietario->cnpj, 14, '0', STR_PAD_LEFT);
                    }
                    $prop->RNTRC = $mdfeVeiculo->Veiculo->PessoaProprietario->rntrc;
                    $prop->xNome = $mdfeVeiculo->Veiculo->PessoaProprietario->pessoa;
                    $prop->IE = $mdfeVeiculo->Veiculo->PessoaProprietario->ie??'ISENTO';
                    $prop->UF = $mdfeVeiculo->Veiculo->PessoaProprietario->Cidade->Estado->sigla;
                    $prop->tpProp = $mdfeVeiculo->Veiculo->tipoproprietario??9;
                    $veicReboque->prop = $prop;
                }

                $make->tagveicReboque($veicReboque);
                /* fim veicReboque */

            }
        }

        // $lacRodo = new \stdClass();
        // $lacRodo->nLacre = '1502400';
        // $make->taglacRodo($lacRodo);
        /* fim rodo */

        /*
         * Grupo infDoc ( Documentos fiscais )
         */
        // $infMunDescarga = new \stdClass();
        // $infMunDescarga->cMunDescarga = '1502400';
        // $infMunDescarga->xMunDescarga = 'CASTANHAL';
        // $infMunDescarga->nItem = 0;
        // $make->taginfMunDescarga($infMunDescarga);

        /* infCTe */
        // $std = new \stdClass();
        // $std->chCTe = '35310800000000000372570010001999091000027765';
        // $std->SegCodBarra = '012345678901234567890123456789012345';
        // $std->indReentrega = '1';
        // $std->nItem = 0;

        /* Informações das Unidades de Transporte (Carreta/Reboque/Vagão) */
        // $stdinfUnidTransp = new \stdClass();
        // $stdinfUnidTransp->tpUnidTransp = '1';
        // $stdinfUnidTransp->idUnidTransp = 'AAA-1111';

        /* Lacres das Unidades de Transporte */
        // $stdlacUnidTransp = new \stdClass();
        // $stdlacUnidTransp->nLacre = ['00000001', '00000002'];
        // $stdinfUnidTransp->lacUnidTransp = $stdlacUnidTransp;

        /* Informações das Unidades de Carga (Containeres/ULD/Outros) */
        // $stdinfUnidCarga = new \stdClass();
        // $stdinfUnidCarga->tpUnidCarga = '1';
        // $stdinfUnidCarga->idUnidCarga = '01234567890123456789';

        /* Lacres das Unidades de Carga */
        // $stdlacUnidCarga = new \stdClass();
        // $stdlacUnidCarga->nLacre = ['00000001', '00000002'];
        // $stdinfUnidCarga->lacUnidCarga = $stdlacUnidCarga;
        // $stdinfUnidCarga->qtdRat = '3.50';
        // $stdinfUnidTransp->infUnidCarga = [$stdinfUnidCarga];
        // $stdinfUnidTransp->qtdRat = '3.50';

        // $std->infUnidTransp = [$stdinfUnidTransp];

        /* transporte de produtos classificados pela ONU como perigosos */
        // $stdperi = new \stdClass();
        // $stdperi->nONU = '1234';
        // $stdperi->xNomeAE = 'testeNome';
        // $stdperi->xClaRisco = 'testeClaRisco';
        // $stdperi->grEmb = 'testegrEmb';
        // $stdperi->qTotProd = '1';
        // $stdperi->qVolTipo = '1';
        // $std->peri = [$stdperi];

        /* Grupo de informações da Entrega Parcial (Corte de Voo) */
        // $stdinfEntregaParcial = new \stdClass();
        // $stdinfEntregaParcial->qtdTotal = '1234.56';
        // $stdinfEntregaParcial->qtdParcial = '1234.56';
        // $std->infEntregaParcial = $stdinfEntregaParcial;

        // $make->taginfCTe($std);

        $nItem = 0;
        $vCarga = 0;
        $qCarga = 0;
        foreach ($mdfe->MdfeNfeS as $mdfeNfe) {

            // dd($mdfeNfe);
            $infMunDescarga = new \stdClass();
            $infMunDescarga->cMunDescarga = $mdfeNfe->CidadeDescarga->codigooficial;
            $infMunDescarga->xMunDescarga = $mdfeNfe->CidadeDescarga->cidade;
            $infMunDescarga->nItem = $nItem;
            $make->taginfMunDescarga($infMunDescarga);

            /* infCTe */
            // $std = new \stdClass();
            // $std->chCTe = '35310800000000000372570010001998991000614492';
            // $std->nItem = 1;
            // $make->taginfCTe($std);

            /* infNFe */
            $std = new \stdClass();
            $std->chNFe = $mdfeNfe->nfechave;
            // $std->SegCodBarra = '012345678901234567890123456789012345';
            // $std->indReentrega = '1';
            $std->nItem = $nItem;

            // Informações das Unidades de Transporte (Carreta/Reboque/Vagão)
            // $stdinfUnidTransp = new \stdClass();
            // $stdinfUnidTransp->tpUnidTransp = '1';
            // $stdinfUnidTransp->idUnidTransp = 'AAA-1111';

            // // Lacres das Unidades de Transporte
            // $stdlacUnidTransp = new \stdClass();
            // $stdlacUnidTransp->nLacre = ['00000001', '00000002'];
            //
            // $stdinfUnidTransp->lacUnidTransp = $stdlacUnidTransp;

            // Informações das Unidades de Carga (Containeres/ULD/Outros)
            // $stdinfUnidCarga = new \stdClass();
            // $stdinfUnidCarga->tpUnidCarga = '1';
            // $stdinfUnidCarga->idUnidCarga = '01234567890123456789';

            // lacres das Unidades de Carga
            // $stdlacUnidCarga = new \stdClass();
            // $stdlacUnidCarga->nLacre = ['00000001', '00000002'];
            // $stdinfUnidCarga->lacUnidCarga = $stdlacUnidCarga;
            // $stdinfUnidCarga->qtdRat = '3.50';
            //
            // $stdinfUnidTransp->infUnidCarga = [$stdinfUnidCarga];
            // $stdinfUnidTransp->qtdRat = '3.50';

            // $std->infUnidTransp = [$stdinfUnidTransp];

            // transporte de produtos classificados pela ONU como perigosos
            // $stdperi = new \stdClass();
            // $stdperi->nONU = '1234';
            // $stdperi->xNomeAE = 'testeNome';
            // $stdperi->xClaRisco = 'testeClaRisco';
            // $stdperi->grEmb = 'testegrEmb';
            // $stdperi->qTotProd = '1';
            // $stdperi->qVolTipo = '1';
            // $std->peri = [$stdperi];

            $make->taginfNFe($std);
            $qCarga += $mdfeNfe->peso;
            $vCarga += $mdfeNfe->valor;
            $nItem++;
        }


        /* infMDFeTransp */

        // $std = new \stdClass();
        // $std->chMDFe = '35310800000000000372570010001999091000088888';
        // $std->indReentrega = '1';
        // $std->nItem = 0;
        //
        // // Informações das Unidades de Transporte (Carreta/Reboque/Vagão)
        // $stdinfUnidTransp = new \stdClass();
        // $stdinfUnidTransp->tpUnidTransp = '1';
        // $stdinfUnidTransp->idUnidTransp = 'AAA-1111';
        //
        // // Lacres das Unidades de Transporte
        // $stdlacUnidTransp = new \stdClass();
        // $stdlacUnidTransp->nLacre = ['00000001', '00000002'];
        //
        // $stdinfUnidTransp->lacUnidTransp = $stdlacUnidTransp;
        //
        // // Informações das Unidades de Carga (Containeres/ULD/Outros)
        // $stdinfUnidCarga = new \stdClass();
        // $stdinfUnidCarga->tpUnidCarga = '1';
        // $stdinfUnidCarga->idUnidCarga = '01234567890123456789';
        //
        // // lacres das Unidades de Carga
        // $stdlacUnidCarga = new \stdClass();
        // $stdlacUnidCarga->nLacre = ['00000001', '00000002'];
        //
        // $stdinfUnidCarga->lacUnidCarga = $stdlacUnidCarga;
        // $stdinfUnidCarga->qtdRat = '3.50';
        //
        // $stdinfUnidTransp->infUnidCarga = [$stdinfUnidCarga];
        // $stdinfUnidTransp->qtdRat = '3.50';
        //
        // $std->infUnidTransp = [$stdinfUnidTransp];
        //
        // // transporte de produtos classificados pela ONU como perigosos
        // $stdperi = new \stdClass();
        // $stdperi->nONU = '1234';
        // $stdperi->xNomeAE = 'testeNome';
        // $stdperi->xClaRisco = 'testeClaRisco';
        // $stdperi->grEmb = 'testegrEmb';
        // $stdperi->qTotProd = '1';
        // $stdperi->qVolTipo = '1';
        // $std->peri = [$stdperi];
        //
        // $make->taginfMDFeTransp($std);

        /* fim grupo infDoc */

        // /* Grupo do Seguro */
        // $std = new \stdClass();
        // $std->respSeg = '1';
        //
        // /* Informações da seguradora */
        // $stdinfSeg = new \stdClass();
        // $stdinfSeg->xSeg = 'SOMPO SEGUROS';
        // $stdinfSeg->CNPJ = '11222333444455';
        //
        // $std->infSeg = $stdinfSeg;
        // $std->nApol = '11223344555';
        // $std->nAver = ['0572012190000000000007257001000199899140', '0572012190000000000007257001000199708140'];
        // $make->tagseg($std);
        // /* fim grupo Seguro */

        /* grupo de totais */
        $std = new \stdClass();
        $std->vCarga = number_format($vCarga, 2, '.', '');
        $std->cUnid = '01'; //KG Fixo
        $std->qCarga = number_format($qCarga, 4, '.', '');
        $make->tagtot($std);
        /* fim grupo de totais */

        /* grupo de lacres */
        // for {
        $std = new \stdClass();
        $std->nLacre = '0000000';
        $make->taglacres($std);
        // }
        /* fim grupo de lacres */

        /* grupo Autorizados para download do XML do DF-e */
        // for {
        // $std = new \stdClass();
        // $std->CNPJ = '11122233344455';
        // $make->tagautXML($std);
        // }

        /*
        $prodPred = new \stdClass();
        $prodPred->tpCarga = '01';
        $prodPred->xProd = 'teste';
        $prodPred->cEAN = null;
        $prodPred->NCM = null;

        $localCarrega = new \stdClass();
        $localCarrega->CEP = '00000000';
        $localCarrega->latitude = null;
        $localCarrega->longitude = null;

        $localDescarrega = new \stdClass();
        $localDescarrega->CEP = '00000000';
        $localDescarrega->latitude = null;
        $localDescarrega->longitude = null;

        $lotacao = new \stdClass();
        $lotacao->infLocalCarrega = $localCarrega;
        $lotacao->infLocalDescarrega = $localDescarrega;

        $prodPred->infLotacao = $lotacao;

        $make->tagProdPred($prodPred);
        */

        /*
        $infPag = new \stdClass();
        $infPag->xNome = 'JOSE';
        $infPag->CPF = '01234567890';
        $infPag->CNPJ = null;
        $infPag->idEstrangeiro = null;

        $componentes = [];
        // {
        $Comp = new \stdClass();
        $Comp->tpComp = '01';
        $Comp->vComp = 10.00;
        $Comp->xComp = 'NADA';
        $componentes[] = $Comp;
        // }
        $infPag->Comp = $componentes;
        $infPag->vContrato = 10.00;
        $infPag->indPag = 1;

        $parcelas = [];
        // {
        $infPrazo = new \stdClass();
        $infPrazo->nParcela = '001';
        $infPrazo->dVenc = '2020-04-30';
        $infPrazo->vParcela = 10.00;
        $parcelas[] = $infPrazo;
        // }
        $infPag->infPrazo = $parcelas;

        $infBanc = new \stdClass();
        $infBanc->codBanco = '341';
        $infBanc->codAgencia = '12345';
        $infBanc->CNPJIPEF = null;
        $infPag->infBanc = $infBanc;

        $make->taginfPag($infPag);
        */

        /* grupo Informações Adicionais */
        $std = new \stdClass();
        $std->infCpl = $mdfe->informacoescomplementares;
        $std->infAdFisco = $mdfe->informacoesadicionais;
        $make->taginfAdic($std);
        /* fim grupo Informações Adicionais */

        // Monta XML
        $xml = $make->getXML(); // O conteúdo do XML fica armazenado na variável $xml

        // Salva Chave do MDFE no Banco de Dados
        $mdfe->update(['chmdfe' => $make->chMDFe]);

        // Assina XML
        $tools = MdfeNfePhpConfigService::instanciaTools($mdfe->Filial);
        $xmlAssinado = $tools->signMDFe($xml);

        // Salva XML
        $path = MdfeNfePhpPathService::pathMdfeCriado($mdfe, true);
        file_put_contents($path, $xmlAssinado);

        return $xmlAssinado;
    }

    public static function enviar (Mdfe $mdfe)
    {
        $tools = MdfeNfePhpConfigService::instanciaTools($mdfe->Filial);

        // valida se existe Chave da NFe
        if (empty($mdfe->chmdfe)) {
            throw new \Exception('Chave da MDFe ausente!');
        }

        // Carrega Arquivo XML Assinado
        $path = MdfeNfePhpPathService::pathMdfeCriado($mdfe);
        if (!file_exists($path)) {
            throw new \Exception("Arquivo da MDFe não localizado ($path)!");
        }
        $xmlAssinado = file_get_contents($path);

        // Monta Configuracao do Lote
        $idLote = str_pad(1, 15, '0', STR_PAD_LEFT);

        // Envia Lote para Sefaz
        $resp = $tools->sefazEnviaLote([$xmlAssinado], $idLote);
        $path = MdfeNfePhpPathService::pathMdfeRetorno($mdfe, true);
        file_put_contents($path, $resp);

        $st = new Standardize();
        $respStd = $st->toStd($resp);

        // inicializa variaveis para retorno
        $sucesso = false;
        $cStat = null;
        $xMotivo = 'Falha Comunicação SEFAZ!';

        // Se veio cStat
        $recibo = null;
        if (isset($respStd->infRec->nRec)) {
            $recibo = $respStd->infRec->nRec;
        }

        $recebimento = null;
        if (isset($respStd->infRec->dhRecbto)) {
            $recebimento = Carbon::parse($respStd->infRec->dhRecbto);
        }

        if (isset($respStd->cStat)) {
            // Se Lote Recebido Com Sucesso
            if ($respStd->cStat == 103) {
                $sucesso = true;
            }

            // joga mensagem recebida da Sefaz para Variaveis de Retorno
            $cStat = $respStd->cStat;
            $xMotivo = $respStd->xMotivo;
        }

        $envio = MdfeEnvioSefaz::create([
            'codmdfe' => $mdfe->codmdfe,
            'recibo' => $recibo,
            'recebimento' => $recebimento,
            'cstatenvio' => $cStat,
        ]);


        // Retorna Resultado do processo
        return [
            'sucesso' => $sucesso,
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'recibo' => $recibo,
            'recebimento' => empty($recebimento)?null:$recebimento->format('Y-m-d H:i:s'),
            'resp' => $resp,
        ];
    }

    public static function consultarEnvio (MdfeEnvioSefaz $envio)
    {
        $mdfe = $envio->Mdfe;

        $tools = MdfeNfePhpConfigService::instanciaTools($mdfe->Filial);

        $resp = $tools->sefazConsultaRecibo($envio->recibo);
        $path = MdfeNfePhpPathService::pathMdfeRetorno($mdfe, true);
        file_put_contents($path, $resp);

        $st = new Standardize();
        $respStd = $st->toStd($resp);

        $sucesso = false;
        $cStat = null;
        $xMotivo = 'Falha Comunicação SEFAZ!';

        if (isset($respStd->protMDFe->infProt->cStat)) {
            $cStat = $respStd->protMDFe->infProt->cStat;
            // Processa Protocolo para saber se foi autorizada
            $sucesso = static::processarProtocolo($mdfe, $respStd->protMDFe, $resp);
        }

        if (isset($respStd->protMDFe->infProt->xMotivo)) {
            $xMotivo = $respStd->protMDFe->infProt->xMotivo;
        }

        $envio->update([
            'cstatretorno' => $cStat,
            'xmotivo' => $xMotivo
        ]);


        // Retorna Resultado do processo
        return [
            'sucesso' => $sucesso,
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'resp' => $resp,
        ];
    }

    public static function processarProtocolo(Mdfe $mdfe, $protMDFe, $resp)
    {

        // Se Autorizado
        // 100 - Autorizado o uso da NF-e
        // 150 - Autorizado o uso da NF-e, autorizacao fora de prazo
        if (in_array($protMDFe->infProt->cStat, [100, 150])) {
            return static::vincularProtocoloAutorizacao($mdfe, $protMDFe, $resp);
        }

        // Se Denegada
        // 301 Uso Denegado: Irregularidade fiscal do emitente
        // 302 Uso Denegado: Irregularidade fiscal do destinatário
        // 303 Uso Denegado: Destinatario nao habilitado a operar na UF
        if (in_array($protMDFe->infProt->cStat, [301, 302, 303])) {
            static::vincularProtocoloDenegacao($mdfe, $protMDFe, $resp);
            return false;
        }

        return false;
    }

    public static function vincularProtocoloAutorizacao(Mdfe $mdfe, $protMDFe, $resp)
    {

        // Verifica se tem o infProt
        if (!isset($protMDFe->infProt)) {
            return false;
        }
        $infProt = $protMDFe->infProt;

        // Guarda no Banco de Dados informação da Autorização
        // $ret = NotaFiscal::where('codnotafiscal', $mdfe->codnotafiscal)->update([
        //   'nfeautorizacao' => $infProt->nProt,
        //   'nfedataautorizacao' => Carbon::parse($infProt->dhRecbto)
        // ]);

        // Carrega o Arquivo com o XML Assinado
        $pathAssinada = MdfeNfePhpPathService::pathMdfeCriado($mdfe);
        $xmlAssinado = file_get_contents($pathAssinada);

        // Vincula o Protocolo no XML Assinado
        // $prot = new Protocol();
        // $xmlProtocolado = $prot->add($xmlAssinado, $resp);
        $xmlProtocolado = Complements::toAuthorize($xmlAssinado, $resp);

        // Salva o Arquivo com a NFe Aprovada
        $pathAprovada = MdfeNfePhpPathService::pathMdfeAutorizado($mdfe, true);
        file_put_contents($pathAprovada, $xmlProtocolado);

        return true;
    }


    public static function damdfe (Mdfe $mdfe)
    {
        $path = MdfeNfePhpPathService::pathMdfeAutorizado($mdfe);

        // if (!file_exists($path)) {
        //     $path = MdfeNfePhpPathService::pathMdfeCriado($mdfe);
        // }

        if (!file_exists($path)) {
            throw new \Exception("Não foi Localizado o arquivo da MDFe ($path)");
        }

        // Carrega XML Assinado
        $xml = file_get_contents($path);
        $pathLogo = public_path('MGPapelariaLogo.jpeg');
        $damdfe = new Damdfe($xml);
        $pdf = $damdfe->render();

        $pathDamdfe = MdfeNfePhpPathService::pathDamdfe($mdfe, true);
        file_put_contents($pathDamdfe, $pdf);

        return $pathDamdfe;
    }

}
