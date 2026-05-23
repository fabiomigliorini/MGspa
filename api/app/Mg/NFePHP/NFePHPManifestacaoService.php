<?php

namespace Mg\NFePHP;

use Mg\NotaFiscal\NotaFiscal;
use Mg\Filial\Filial;

use NFePHP\NFe\Common\Standardize;

class NFePHPManifestacaoService
{
    public static function manifestacao(Filial $filial, $chNFe, $tpEvento, string $justificativa, $nSeqEvento)
    {
        $tools = NFePHPConfigService::instanciaTools($filial);
        $tools->model(55);
        //este serviço somente opera em ambiente de produção
        // $tools->setEnvironment($filial->nfeambiente);
        $tools->setEnvironment(1);
        $response = $tools->sefazManifesta($chNFe, $tpEvento, $justificativa, $nSeqEvento);
        $st = (new Standardize($response))->toStd();
        return $st;
    }

}
