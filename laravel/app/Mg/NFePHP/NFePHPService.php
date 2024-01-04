<?php

namespace Mg\NFePHP;

use Carbon\Carbon;

use Mg\MgService;
use Mg\NotaFiscal\NotaFiscal;
use Mg\NotaFiscal\NotaFiscalCartaCorrecao;
use Mg\Filial\Filial;
use Mg\Filial\Empresa;

use NFePHP\NFe\Complements;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Factories\Protocol;
use NFePHP\Common\Strings;
use NFePHP\DA\NFe\Danfe;

class NFePHPService extends MgService
{
    const EXCEPTION_CHAVE_NFE_AUSENTE = 999;
    const EXCEPTION_XML_ASSINADO_INEXISTENTE = 998;

    public static function sefazStatus(Filial $filial)
    {
        $tools = NFePHPConfigService::instanciaTools($filial);
        $resp = $tools->sefazStatus();
        $st = new Standardize();
        $r = $st->toStd($resp);
        return $r;
    }

    public static function cscConsulta(Filial $filial)
    {
        $tools = NFePHPConfigService::instanciaTools($filial);
        $tools->model('65');
        $resp = $tools->sefazCsc(1);
        $st = new Standardize();
        $r = $st->toStd($resp);
        return $r;
    }

    public static function criar(NotaFiscal $nf, $offline = false)
    {
        // Instancia Tools para a configuracao e certificado
        $tools = NFePHPConfigService::instanciaTools($nf->Filial);
        $tools->model($nf->modelo);

        // Cria Arquivo XML
        $xml = NFePHPMakeService::montarXml($nf, $offline);

        // Assina XML
        $xmlAssinado = $tools->signNFe($xml);

        // Grava arquivo XML Assinado na pasta de "assinadas"
        $nf = $nf->fresh();
        $path = NFePHPPathService::pathNFeAssinada($nf, true);
        file_put_contents($path, $xmlAssinado);

        // Retorna Resultado do processo
        return $xmlAssinado;
    }

    public static function enviar(NotaFiscal $nf)
    {

        // Instancia Tools para a configuracao e certificado
        $tools = NFePHPConfigService::instanciaTools($nf->Filial);
        $tools->model($nf->modelo);

        // valida se existe Chave da NFe
        if (empty($nf->nfechave)) {
            throw new \Exception('Chave da NFe ausente!', static::EXCEPTION_CHAVE_NFE_AUSENTE);
        }

        // Carrega Arquivo XML Assinado
        $path = NFePHPPathService::pathNFeAssinada($nf);
        if (!file_exists($path)) {
            throw new \Exception("Arquivo da NFe não localizado ($path)!", static::EXCEPTION_XML_ASSINADO_INEXISTENTE);
        }
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
                NotaFiscal::where('codnotafiscal', $nf->codnotafiscal)->update([
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
        return (object) [
            'sucesso' => $sucesso,
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'nfereciboenvio' => $nf->nfereciboenvio,
            'nfedataenvio' => ($nf->nfedataenvio) ? $nf->nfedataenvio->toW3cString() : null,
            'resp' => $resp,
        ];
    }


    public static function enviarSincrono(NotaFiscal $nf)
    {
        // Instancia Tools para a configuracao e certificado
        $tools = NFePHPConfigService::instanciaTools($nf->Filial);
        $tools->model($nf->modelo);

        // valida se existe Chave da NFe
        if (empty($nf->nfechave)) {
            throw new \Exception('Chave da NFe ausente!', static::EXCEPTION_CHAVE_NFE_AUSENTE);
        }

        // Carrega Arquivo XML Assinado
        $path = NFePHPPathService::pathNFeAssinada($nf);
        if (!file_exists($path)) {
            throw new \Exception("Arquivo da NFe não localizado ($path)!", static::EXCEPTION_XML_ASSINADO_INEXISTENTE);
        }
        $xmlAssinado = file_get_contents($path);

        // Monta Configuracao do Lote
        $idLote = str_pad(1, 15, '0', STR_PAD_LEFT);

        // Envia Lote para Sefaz
        //$tools->timeout(5);
        $resp = $tools->sefazEnviaLote([$xmlAssinado], $idLote, 1);
        $st = new Standardize();
        $respStd = $st->toStd($resp);

        // inicializa variaveis para retorno
        $sucesso = false;
        $cStat = null;
        $xMotivo = 'Falha Comunicação SEFAZ!';

        // Se veio cStat do Protocolo
        if (isset($respStd->protNFe->infProt->cStat)) {

            // Processa Protocolo para saber se foi autorizada
            $sucesso = static::processarProtocolo($nf, $respStd->protNFe, $resp);
            $nf = $nf->fresh();

            // joga mensagem recebida da Sefaz para Variaveis de Retorno
            $cStat = $respStd->protNFe->infProt->cStat;
            $xMotivo = $respStd->protNFe->infProt->xMotivo;

            // Se veio cStat na Raiz
        } elseif (isset($respStd->cStat)) {
            $cStat = $respStd->cStat;
            $xMotivo = $respStd->xMotivo;
        }

        // Retorna Resultado do processo
        return (object) [
            'sucesso' => $sucesso,
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'nfeautorizacao' => $nf->nfeautorizacao,
            'nfedataautorizacao' => ($nf->nfedataautorizacao) ? $nf->nfedataautorizacao->toW3cString() : null,
            'resp' => $resp
        ];
    }


    public static function vincularProtocoloAutorizacao(NotaFiscal $nf, $protNFe, $resp)
    {

        // Verifica se tem o infProt
        if (!isset($protNFe->infProt)) {
            return false;
        }
        $infProt = $protNFe->infProt;

        // Guarda no Banco de Dados informação da Autorização
        $ret = NotaFiscal::where('codnotafiscal', $nf->codnotafiscal)->update([
            'nfeautorizacao' => $infProt->nProt,
            'nfedataautorizacao' => Carbon::parse($infProt->dhRecbto)
        ]);

        // Carrega o Arquivo com o XML Assinado
        $pathAssinada = NFePHPPathService::pathNFeAssinada($nf);
        $xmlAssinado = file_get_contents($pathAssinada);

        // Vincula o Protocolo no XML Assinado
        // $prot = new Protocol();
        // $xmlProtocolado = $prot->add($xmlAssinado, $resp);
        $xmlProtocolado = Complements::toAuthorize($xmlAssinado, $resp);


        // Salva o Arquivo com a NFe Aprovada
        $pathAprovada = NFePHPPathService::pathNFeAutorizada($nf, true);
        file_put_contents($pathAprovada, $xmlProtocolado);

        return true;
    }

    public static function vincularProtocoloDenegacao(NotaFiscal $nf, $protNFe, $resp)
    {
        // Verifica se tem o infProt
        if (!isset($protNFe->infProt)) {
            return false;
        }
        $infProt = $protNFe->infProt;

        // Guarda no Banco de Dados informação da Autorização
        NotaFiscal::where('codnotafiscal', $nf->codnotafiscal)->update([
            'justificativa' => $infProt->xMotivo,
            'nfeinutilizacao' => $infProt->nProt,
            'nfedatainutilizacao' => Carbon::parse($infProt->dhRecbto)
        ]);

        // Carrega o Arquivo com o XML Assinado
        $pathAssinada = NFePHPPathService::pathNFeAssinada($nf);
        $xmlAssinado = file_get_contents($pathAssinada);

        // Vincula o Protocolo no XML Assinado
        // $prot = new Protocol();
        // $xmlProtocolado = $prot->add($xmlAssinado, $resp);
        $xmlProtocolado = Complements::toAuthorize($xmlAssinado, $resp);

        // Salva o Arquivo com a NFe Aprovada
        $pathNFeDenegada = NFePHPPathService::pathNFeDenegada($nf, true);
        file_put_contents($pathNFeDenegada, $xmlProtocolado);

        return true;
    }

    public static function vincularProtocoloCancelamento(NotaFiscal $nf, $procEventoNFe, $resp, $justificativa = null, $tools = null)
    {
        // Verifica se tem o infEvento
        if (!isset($procEventoNFe->retEvento->infEvento)) {
            return false;
        }
        $infEvento = $procEventoNFe->retEvento->infEvento;

        if (isset($procEventoNFe->evento->infEvento->detEvento->xJust)) {
            $justificativa = $procEventoNFe->evento->infEvento->detEvento->xJust;
        }

        // Guarda no Banco de Dados informação da Autorização
        NotaFiscal::where('codnotafiscal', $nf->codnotafiscal)->update([
            'justificativa' => $justificativa,
            'nfecancelamento' => $infEvento->nProt,
            'nfedatacancelamento' => Carbon::parse($infEvento->dhRegEvento)
        ]);

        // Pega XML do Cancelamento
        if (isset($procEventoNFe->evento)) {
            $xmlProtocolado = $resp;
        } else {
            // $xmlProtocolado = Complements::toAuthorize($tools->lastRequest, $resp);
            $pathAutorizada = NFePHPPathService::pathNFeAutorizada($nf);
            $xmlAutorizado = file_get_contents($pathAutorizada);
            $xmlProtocolado = Complements::cancelRegister($xmlAutorizado, $resp);
        }

        // Salva o Arquivo com a NFe Aprovada
        $pathNFeCancelada = NFePHPPathService::pathNFeCancelada($nf, true);
        file_put_contents($pathNFeCancelada, $xmlProtocolado);

        return true;
    }

    public static function vincularProtocoloInutilizacao(NotaFiscal $nf, $resp, $respStd, $justificativa)
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

    public static function consultarRecibo(NotaFiscal $nf)
    {
        // valida se existe Chave da NFe
        if (empty($nf->nfereciboenvio)) {
            throw new \Exception('Esta NFe não possui número de Recibo para ser consultado!');
        }

        // Instancia Tools para a configuracao e certificado
        $tools = NFePHPConfigService::instanciaTools($nf->Filial);
        $tools->model($nf->modelo);

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

            // Processa Protocolo para saber se foi autorizada
            $sucesso = static::processarProtocolo($nf, $respStd->protNFe, $resp);
            $nf = $nf->fresh();

            // joga mensagem recebida da Sefaz para Variaveis de Retorno
            $cStat = $respStd->protNFe->infProt->cStat;
            $xMotivo = $respStd->protNFe->infProt->xMotivo;
        }

        // Retorna Resultado do processo
        return (object) [
            'sucesso' => $sucesso,
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'nfeautorizacao' => $nf->nfeautorizacao,
            'nfedataautorizacao' => ($nf->nfedataautorizacao) ? $nf->nfedataautorizacao->toW3cString() : null,
            'resp' => $resp
        ];
    }

    public static function cancelar(NotaFiscal $nf, $justificativa)
    {
        // Valida Justificativa
        $justificativa = Strings::replaceSpecialsChars(trim($justificativa));
        if (strlen($justificativa) < 15) {
            throw new \Exception('A justificativa deve ter pelo menos 15 caracteres!');
        }

        // Valida Autorização
        if (empty($nf->nfeautorizacao)) {
            throw new \Exception('Esta nota ainda não está autorizada! Impossível prosseguir com o Cancelamento!');
        }

        // Valida Cancelamento
        if (!empty($nf->nfecancelamento)) {
            throw new \Exception('Esta nota já está Cancelada! Impossível prosseguir com o Cancelamento!');
        }

        // Valida Inutilizacao
        if (!empty($nf->nfeinutilizacao)) {
            throw new \Exception('Esta nota já está Inutilizada! Impossível prosseguir com o Cancelamento!');
        }

        // Instancia Tools para a configuracao e certificado
        // if ($nf->modelo == 65) {
        //     $tools = NFePHPConfigService::instanciaTools($nf->Filial, '3.10');
        // } else {
        //     $tools = NFePHPConfigService::instanciaTools($nf->Filial);
        // }
        $tools = NFePHPConfigService::instanciaTools($nf->Filial);

        $tools->model($nf->modelo);

        // solicita a sefaz cancelamento
        $resp = $tools->sefazCancela($nf->nfechave, $justificativa, $nf->nfeautorizacao);
        // return $resp;
        $st = new Standardize();
        $respStd = $st->toStd($resp);

        // inicializa variaveis para retorno
        $sucesso = false;
        $cStat = null;
        $xMotivo = 'Falha Comunicação SEFAZ!';

        // Se veio cStat do Protocolo
        if (isset($respStd->retEvento->infEvento->cStat)) {

            // Processa Retorno do Evento
            $sucesso = static::processarEventoCancelamento($nf, $respStd, $resp, $justificativa, $tools);
            $nf = $nf->fresh();

            // joga mensagem recebida da Sefaz para Variaveis de Retorno
            $cStat = $respStd->retEvento->infEvento->cStat;
            $xMotivo = $respStd->retEvento->infEvento->xMotivo;
        }

        // Retorna Resultado do processo
        return (object) [
            'sucesso' => $sucesso,
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'nfecancelamento' => $nf->nfecancelamento,
            'nfedatanfecancelamento' => ($nf->nfedatanfecancelamento) ? $nf->nfedatanfecancelamento->toW3cString() : null,
            'resp' => $resp
        ];
    }

    public static function inutilizar(NotaFiscal $nf, $justificativa)
    {

        // Valida Justificativa
        $justificativa = Strings::replaceSpecialsChars(trim($justificativa));
        if (strlen($justificativa) < 15) {
            throw new \Exception('A justificativa deve ter pelo menos 15 caracteres!');
        }

        // Valida Pessoa Juridica
        if (!empty($nf->Filial->Pessoa->fisica)) {
            throw new \Exception('Impossível inutilizar Nota Fiscal de Emitente Pessoal Física! Corrija as incoerências e transmita a nota fiscal novamente!');
        }

        // Valida Autorização
        if (!empty($nf->nfeautorizacao)) {
            throw new \Exception('Esta nota já está autorizada! Impossível prosseguir com a Inutilização!');
        }

        // Valida Cancelamento
        if (!empty($nf->nfecancelamento)) {
            throw new \Exception('Esta nota já está Cancelada! Impossível prosseguir com a Inutilização!');
        }

        // Valida Inutilizacao
        if (!empty($nf->nfeinutilizacao)) {
            throw new \Exception('Esta nota já está Inutilizada! Impossível prosseguir com a Inutilização!');
        }

        // Instancia Tools para a configuracao e certificado
        $tools = NFePHPConfigService::instanciaTools($nf->Filial);
        $tools->model($nf->modelo);

        // solicita a sefaz cancelamento
        $resp = $tools->sefazInutiliza($nf->serie, $nf->numero, $nf->numero, $justificativa, $nf->Filial->nfeambiente);
        $st = new Standardize();
        $respStd = $st->toStd($resp);

        // inicializa variaveis para retorno
        $sucesso = false;
        $cStat = null;
        $xMotivo = 'Falha Comunicação SEFAZ!';

        // Se veio cStat do Protocolo
        if (isset($respStd->infInut->cStat)) {

            // Se Inutilizacao Homologada
            // 102 - Inutilizacao de Numero Homologado
            if (in_array($respStd->infInut->cStat, [102])) {
                static::vincularProtocoloInutilizacao($nf, $resp, $respStd, $justificativa);
                $nf = $nf->fresh();
                $sucesso = true;
            }

            // joga mensagem recebida da Sefaz para Variaveis de Retorno
            $cStat = $respStd->infInut->cStat;
            $xMotivo = $respStd->infInut->xMotivo;
        }

        // Retorna Resultado do processo
        return (object) [
            'sucesso' => $sucesso,
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'nfeinutilizacao' => $nf->nfeinutilizacao,
            'nfedatainutilizacao' => ($nf->nfedatainutilizacao) ? $nf->nfedatainutilizacao->toW3cString() : null,
            'resp' => $resp
        ];
    }

    public static function cartaCorrecao(NotaFiscal $nf, $texto)
    {
        // Valida Justificativa
        $justificativa = Strings::replaceSpecialsChars(trim($texto));
        if (strlen($justificativa) < 15) {
            throw new \Exception('O Texto deve ter pelo menos 15 caracteres!');
        }

        // Valida Inutilizacao
        if ($nf->modelo == NotaFiscal::MODELO_NFCE) {
            throw new \Exception('Não é permitido Carta de Correção para NFCe!');
        }

        // Valida Inutilizacao
        if (!empty($nf->nfeinutilizacao)) {
            throw new \Exception('Esta nota já está Inutilizada!');
        }

        // Valida Autorização
        if (empty($nf->nfeautorizacao)) {
            throw new \Exception('Esta nota não está autorizada!');
        }

        // Valida Cancelamento
        if (!empty($nf->nfecancelamento)) {
            throw new \Exception('Esta nota já está Cancelada!');
        }

        // Instancia Tools para a configuracao e certificado
        $tools = NFePHPConfigService::instanciaTools($nf->Filial);
        $tools->model($nf->modelo);

        // solicita a sefaz cancelamento
        $nSeqEvento = NotaFiscalCartaCorrecao::where('codnotafiscal', $nf->codnotafiscal)->max('sequencia');
        $nSeqEvento++;
        $resp = $tools->sefazCCe($nf->nfechave, $texto, $nSeqEvento);
        $st = new Standardize();
        $respStd = $st->toStd($resp);

        // inicializa variaveis para retorno
        $sucesso = false;
        $cStat = null;
        $xMotivo = 'Falha Comunicação SEFAZ!';
        $protocolo = null;
        $protocolodata = null;

        // Se veio cStat do Protocolo
        if (isset($respStd->retEvento->infEvento->cStat)) {

            // Se Carta Correcao
            // 135 -	Evento registrado e vinculado a NF-e
            // 136 -	Evento registrado, mas não vinculado a NF-e
            if (in_array($respStd->retEvento->infEvento->cStat, [135, 136])) {

                // Salva Carta de Correcao no Banco de Dados
                NotaFiscalCartaCorrecao::create([
                    'codnotafiscal' => $nf->codnotafiscal,
                    'sequencia' => $respStd->retEvento->infEvento->nSeqEvento,
                    'lote' => $respStd->retEvento->infEvento->nSeqEvento,
                    'data' => Carbon::parse($respStd->retEvento->infEvento->dhRegEvento),
                    'texto' => $texto,
                    'protocolo' => $respStd->retEvento->infEvento->nProt,
                    'protocolodata' => Carbon::parse($respStd->retEvento->infEvento->dhRegEvento),
                ]);

                // Salva arquivo XML com retorno
                $xml = Complements::toAuthorize($tools->lastRequest, $resp);
                $pathCartaCorrecao = NFePHPPathService::pathCartaCorrecao($nf, true);
                file_put_contents($pathCartaCorrecao, $xml);

                // Variaveis de retorno
                $sucesso = true;
                $protocolo = $respStd->retEvento->infEvento->nProt;
                $protocolodata = $respStd->retEvento->infEvento->dhRegEvento;
            }

            // joga mensagem recebida da Sefaz para Variaveis de Retorno
            $cStat = $respStd->retEvento->infEvento->cStat;
            $xMotivo = $respStd->retEvento->infEvento->xMotivo;
        }

        // Retorna Resultado do processo
        return (object) [
            'sucesso' => $sucesso,
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'protocolo' => $protocolo,
            'protocolodata' => $protocolodata,
            'resp' => $resp
        ];
    }

    public static function processarProtocolo(NotaFiscal $nf, $protNFe, $resp)
    {

        // Se Autorizado
        // 100 - Autorizado o uso da NF-e
        // 150 - Autorizado o uso da NF-e, autorizacao fora de prazo
        if (in_array($protNFe->infProt->cStat, [100, 150])) {
            return static::vincularProtocoloAutorizacao($nf, $protNFe, $resp);
        }

        // Se Denegada
        // 301 Uso Denegado: Irregularidade fiscal do emitente
        // 302 Uso Denegado: Irregularidade fiscal do destinatário
        // 303 Uso Denegado: Destinatario nao habilitado a operar na UF
        if (in_array($protNFe->infProt->cStat, [301, 302, 303])) {
            static::vincularProtocoloDenegacao($nf, $protNFe, $resp);
            return false;
        }

        return false;
    }

    public static function processarEventoCancelamento(NotaFiscal $nf, $procEventoNFe, $resp, $justificativa = null, $tools = null)
    {

        // Se Autorizado
        // 101 - Cancelamento de NFe Homologado
        // 135 - Evento registrado e vinculado A NFe
        // 155 - Cancelamento Homologado Fora de Prazo
        if (in_array($procEventoNFe->retEvento->infEvento->cStat, [101, 135, 155])) {
            static::vincularProtocoloCancelamento($nf, $procEventoNFe, $resp, $justificativa, $tools);
            return true;
        }

        return false;
    }

    public static function consultar(NotaFiscal $nf)
    {
        // valida se existe Chave da NFe
        if (empty($nf->nfechave)) {
            throw new \Exception('Chave da NFe ausente!', static::EXCEPTION_CHAVE_NFE_AUSENTE);
        }

        // Instancia Tools para a configuracao e certificado
        // $tools = NFePHPConfigService::instanciaTools($nf->Filial, '3.10');
        $tools = NFePHPConfigService::instanciaTools($nf->Filial);
        $tools->model($nf->modelo);

        // consulta chave da NFe na sefaz
        $resp = $tools->sefazConsultaChave($nf->nfechave, $nf->Filial->nfeambiente);
        $st = new Standardize();
        $respStd = $st->toStd($resp);

        $sucesso = false;
        $cStat = null;
        $xMotivo = null;
        $tpEvento = null;

        // Verifica Retorno Principal
        if (isset($respStd->cStat)) {
            // joga mensagem recebida da Sefaz para Variaveis de Retorno
            $cStat = $respStd->cStat;
            $xMotivo = $respStd->xMotivo;
        }

        // Se existe Protocolo de Autorizacao / Denegacao
        if (isset($respStd->protNFe->infProt->cStat)) {

            // Processa Protocolo para saber se foi autorizada
            $sucesso = static::processarProtocolo($nf, $respStd->protNFe, $resp);
            $nf = $nf->fresh();

            // joga mensagem recebida da Sefaz para Variaveis de Retorno
            $cStat = $respStd->protNFe->infProt->cStat;
            $xMotivo = $respStd->protNFe->infProt->xMotivo;
        }

        // se veio evento vinculado
        if (isset($respStd->procEventoNFe)) {

            // Se veio somente um evento, forca array de eventos
            if (!is_array($respStd->procEventoNFe)) {
                $respStd->procEventoNFe = [$respStd->procEventoNFe];
            }

            foreach ($respStd->procEventoNFe as $procEventoNFe) {

                // Cancelamento
                if (
                    isset($procEventoNFe->evento->infEvento->tpEvento) &&
                    $procEventoNFe->evento->infEvento->tpEvento == 110111
                ) {

                    // Processa Protocolo para saber se foi autorizada
                    $sucesso = static::processarEventoCancelamento($nf, $procEventoNFe, $resp);
                    $nf = $nf->fresh();

                    // joga mensagem recebida da Sefaz para Variaveis de Retorno
                    $tpEvento = $procEventoNFe->evento->infEvento->tpEvento;
                    $cStat = $procEventoNFe->retEvento->infEvento->cStat;
                    $xMotivo = $procEventoNFe->retEvento->infEvento->xMotivo;
                }

                // Carta de Correcao
                if (
                    isset($procEventoNFe->evento->infEvento->tpEvento) &&
                    $procEventoNFe->evento->infEvento->tpEvento == 110110
                ) {
                    $nfcc = NotaFiscalCartaCorrecao::firstOrNew([
                        'codnotafiscal' => $nf->codnotafiscal,
                        'sequencia' => $procEventoNFe->evento->infEvento->nSeqEvento
                    ]);
                    $nfcc->lote = $procEventoNFe->evento->infEvento->nSeqEvento;
                    $nfcc->data = Carbon::parse($procEventoNFe->retEvento->infEvento->dhRegEvento);
                    $nfcc->texto = $procEventoNFe->evento->infEvento->detEvento->xCorrecao;
                    $nfcc->protocolo = $procEventoNFe->retEvento->infEvento->nProt;
                    $nfcc->protocolodata = Carbon::parse($procEventoNFe->retEvento->infEvento->dhRegEvento);
                    $nfcc->save();
                }
            }
        }

        return (object) [
            'sucesso' => $sucesso,
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'tpEvento' => $tpEvento,
            'nfeautorizacao' => $nf->nfeautorizacao,
            'nfedataautorizacao' => ($nf->nfedataautorizacao) ? $nf->nfedataautorizacao->toW3cString() : null,
            'nfecancelamento' => $nf->nfecancelamento,
            'nfedatacancelamento' => ($nf->nfedatacancelamento) ? $nf->nfedatacancelamento->toW3cString() : null,
            'nfeinutilizacao' => $nf->nfeinutilizacao,
            'nfedatainutilizacao' => ($nf->nfedatainutilizacao) ? $nf->nfedatainutilizacao->toW3cString() : null,
            'justificativa' => $nf->justificativa,
            'resp' => $resp
        ];
    }

    public static function danfe(NotaFiscal $nf)
    {
        // busca XML autorizado
        $path = NFePHPPathService::pathNFeAutorizada($nf);
        if (!file_exists($path)) {

            // busca XML Assinado
            $path = NFePHPPathService::pathNFeAssinada($nf);
            if (!file_exists($path)) {
                throw new \Exception("Não foi Localizado o arquivo da NFe ($path)");
            }

            // Carrega XML Assinado
            $xml = file_get_contents($path);
            $st = new Standardize();
            $r = $st->toStd($xml);

            // Só deixa passar se for modo OffLine
            if (isset($r->infNFe->ide->tpEmis) && $r->infNFe->ide->tpEmis != Empresa::MODOEMISSAONFCE_OFFLINE) {
                throw new \Exception("Nota Fiscal ainda não está autorizada!");
            }

            // Se arquivo XML Autorizado nao existir
        } elseif (!file_exists($path)) {
            throw new \Exception("Não foi Localizado o arquivo da NFe ($path)");

            // Carrega XML Autorizado
        } else {
            $xml = file_get_contents($path);
        }

        if ($nf->modelo == NotaFiscal::MODELO_NFE) {

            // Logo somente na Migliorini
            if ($nf->Filial->codempresa == 1) {
                $logo = 'data://text/plain;base64,' . base64_encode(file_get_contents(public_path('MGPapelariaLogo.jpeg')));
            } else {
                $logo = null;
            }

            $danfe = new Danfe($xml);
            $danfe->debugMode(false);
            $danfe->setDefaultFont('helvetica');
            //$danfce->creditsIntegratorFooter('MGsis - Powered by NFePHP');
            // Caso queira mudar a configuracao padrao de impressao
            /*  $this->printParameters( $orientacao = '', $papel = 'A4', $margSup = 2, $margEsq = 2 ); */
            //Informe o numero DPEC
            /*  $danfe->depecNumber('123456789'); */
            //Configura a posicao da logo
            /*  $danfe->logoParameters($logo, 'C', false);  */
            //Gera o PDF
            $pdf = $danfe->render($logo);
        } else {

            // Logo somente na Migliorini
            if ($nf->Filial->codempresa == 1) {
                $logo = 'data://text/plain;base64,' . base64_encode(file_get_contents(public_path('MGPapelariaLogoSeloPretoBranco.jpeg')));
            } else {
                $logo = null;
            }

            $danfce = new DanfceMg($xml);
            // $danfce->debugMode(true);//seta modo debug, deve ser false em produção
            $danfce->setPaperWidth(80); //seta a largura do papel em mm max=80 e min=58
            $danfce->setMargins(2); //seta as margens
            $danfce->setDefaultFont('helvetica'); //altera o font pode ser 'times' ou 'arial'
            $danfce->setOffLineDoublePrint(false); //ativa ou desativa a impressão conjunta das via do consumidor e da via do estabelecimento qnado a nfce for emitida em contingência OFFLINE
            //$danfce->setPrintResume(true); //ativa ou desativa a impressao apenas do resumo
            //$danfce->setViaEstabelecimento(); //altera a via do consumidor para a via do estabelecimento, quando a NFCe for emitida em contingência OFFLINE
            //$danfce->setAsCanceled(); //força marcar nfce como cancelada
            $danfce->creditsIntegratorFooter('MGsis - Powered by NFePHP');
            $pdf = $danfce->render($logo);
        }

        $pathDanfe = NFePHPPathService::pathDanfe($nf, true);
        file_put_contents($pathDanfe, $pdf);

        return $pathDanfe;
    }

    public static function imprimir(NotaFiscal $nf, $impressora = null)
    {
        // Valida se o PDF existe
        $pathDanfe = NFePHPPathService::pathDanfe($nf, true);
        if (!file_exists($pathDanfe)) {
            static::danfe($nf);
        }
        if (!file_exists($pathDanfe)) {
            throw new \Exception("Impossível localizar arquivo PDF da DANFe!", 1);
        }

        // Valida se informado nome da Impressora
        if (empty($impressora) && !empty($nf->codusuariocriacao)) {
            $impressora = $nf->UsuarioCriacao->impressoratermica;
        }
        if (empty($impressora)) {
            throw new \Exception("Impressora não informada!", 1);
        }

        // Executa comando de impressao
        $cmd = 'curl -X POST https://rest.ably.io/channels/printing/messages -u "' . env('ABLY_APP_KEY') . '" -H "Content-Type: application/json" --data \'{ "name": "' . $impressora . '", "data": "{\"url\": \"' . env('APP_URL') . 'api/v1/nfe-php/' . $nf->codnotafiscal . '/danfe\", \"method\": \"get\", \"options\": [], \"copies\": 1}" }\'';
        exec($cmd);

        // retorna
        return [
            "sucesso" => true,
            "mensagem" => "Danfe enviada para '$impressora'!",
            "commando" => $cmd
        ];
    }

    public static function xml(NotaFiscal $nf)
    {
        $path = null;
        if (!empty($nf->nfecancelamento)) {
            $path = NFePHPPathService::pathNFeCancelada($nf);
        }
        if ($path == null || !file_exists($path)) {
            $path = NFePHPPathService::pathNFeAutorizada($nf);
        }
        if (!file_exists($path)) {
            $path = NFePHPPathService::pathNFeAssinada($nf);
        }
        if (!file_exists($path)) {
            throw new \Exception("Não existe arquivo XML para esta Nota Fiscal!", 1);
        }
        return file_get_contents($path);
    }

    public static function sefazCadastro(Filial $filial, $uf = 'MT', $cnpj = '', $cpf = '', $iest = '')
    {
        $tools = NFePHPConfigService::instanciaTools($filial);
        $tools->model('55');
        $uf = $uf;
        $cnpj = $cnpj;
        $iest = $iest;
        $cpf = $cpf;
        $response = $tools->sefazCadastro($uf, $cnpj, $iest, $cpf);
        $stdCl = (new Standardize($response))->toStd();
        return $stdCl;
    }
}
