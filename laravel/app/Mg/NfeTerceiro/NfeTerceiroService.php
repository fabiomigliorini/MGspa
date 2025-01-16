<?php

namespace Mg\NfeTerceiro;

use Exception;
use Mg\NFePHP\NFePHPManifestacaoService;
use Mg\NotaFiscal\NotaFiscal;
use Mg\NotaFiscal\NotaFiscalProdutoBarra;
use Mg\NFePHP\NFePHPConfigService;
use Mg\NFePHP\NFePHPDistDfeService;
use NFePHP\NFe\Common\Standardize;

class NfeTerceiroService
{
    public static function manifestacao(NfeTerceiro $nfeTerceiro, $indmanifestacao, string $justificativa = '')
    {
        $resp = NFePHPManifestacaoService::manifestacao(
            $nfeTerceiro->Filial,
            $nfeTerceiro->nfechave,
            $indmanifestacao,
            $justificativa,
            1
        );
        $ret = [
            'cStat' => $resp->cStat,
            'xMotivo' => $resp->xMotivo,
        ];
        if ($infEvento = $resp->retEvento->infEvento) {
            $ret['cStat'] = $infEvento->cStat;
            $ret['xMotivo'] = $infEvento->xMotivo;
            switch ($infEvento->cStat) {
                case '135': // Evento registrado e vinculado a NF-e
                case '136': // Evento registrado, mas não vinculado a NF-e
                case '573': // Rejeicao: Duplicidade de evento
                    $nfeTerceiro->update([
                        'indmanifestacao' => $indmanifestacao
                    ]);
                    break;
            }
        }
        return $ret;
    }

    public static function vincularNotaFiscal(NfeTerceiro $nfeTerceiro)
    {
        if (!empty($nfeTerceiro->codnotafiscal)) {
            return $nfeTerceiro;
        }
        $notaFiscal = NotaFiscal::where('emitida', false)->where('nfechave', $nfeTerceiro->nfechave)->first();
        if ($notaFiscal) {
            $nfeTerceiro->codnotafiscal = $notaFiscal->codnotafiscal;
        }
        return $nfeTerceiro;
    }

    public static function vincularNegocio(NfeTerceiro $nfeTerceiro)
    {
        if (!empty($nfeTerceiro->codnegocio)) {
            return $nfeTerceiro;
        }
        if (empty($nfeTerceiro->codnotafiscal)) {
            return $nfeTerceiro;
        }
        $notaFiscalProdutoBarra = NotaFiscalProdutoBarra::where('codnotafiscal', $nfeTerceiro->codnotafiscal)
            ->whereNotNull('codnegocioprodutobarra')
            ->orderBy('codnotafiscalprodutobarra')
            ->first();
        if ($notaFiscalProdutoBarra) {
            $nfeTerceiro->codnegocio = $notaFiscalProdutoBarra->NegocioProdutoBarra->codnegocio;
        }
        return $nfeTerceiro;
    }

    public static function download($nfeTerceiro)
    {
        // consulta nfe na sefaz
        $tools = NFePHPConfigService::instanciaTools($nfeTerceiro->Filial);
        // $tools->setEnvironment(1);
        $resp = $tools->sefazDownload($nfeTerceiro->nfechave);

        // converte resposta em objeto
        $stz = new Standardize($resp);
        $std = $stz->toStd();

        // Se != 138 - "Documento localizado"
        if ($std->cStat != 138) {
            throw new Exception("{$std->cStat} - {$std->xMotivo}", 1);
        }

        // Impota o XML
        if (!NFePHPDistDfeService::processarProcNFe(null, base64_decode($std->loteDistDFeInt->docZip))) {
            throw new Exception('Falha ao importar o XML!');
        }

        // retorna
        return true;

    }
}
