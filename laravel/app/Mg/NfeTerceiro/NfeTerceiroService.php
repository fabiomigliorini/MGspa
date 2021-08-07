<?php

namespace Mg\NfeTerceiro;

use Mg\NFePHP\NFePHPManifestacaoService;
use Mg\NotaFiscal\NotaFiscal;
use Mg\NotaFiscal\NotaFiscalProdutoBarra;

class NfeTerceiroService
{
    public static function manifestacao(NfeTerceiro $nfeTerceiro, $indmanifestacao, $justificativa = null)
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
                        'indmanifestacao'=>$indmanifestacao
                    ]);
                    break;
            }
        }
        return $ret;
    }

    public static function vincularNotaFiscal (NfeTerceiro $nfeTerceiro)
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

    public static function vincularNegocio (NfeTerceiro $nfeTerceiro)
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

}
