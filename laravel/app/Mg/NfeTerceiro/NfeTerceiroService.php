<?php

namespace Mg\NfeTerceiro;

use Mg\NFePHP\NFePHPManifestacaoService;

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

}
