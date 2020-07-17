<?php

namespace Mg\NFePHP;

use Mg\NotaFiscal\NotaFiscal;
use Mg\Dfe\DistribuicaoDfe;
use Mg\Filial\Filial;

class NFePHPPathService
{

    /**
     * Diretorio Raiz das NFes da Filial
     */
    public static function pathNFe(Filial $filial)
    {
        $ambiente = ($filial->nfeambiente == 1)?'producao':'homologacao';
        return env('NFE_PHP_PATH') . "NFe/{$filial->codfilial}/{$ambiente}/";
    }

    public static function pathNFeAssinada(NotaFiscal $nf, bool $criar = false)
    {
        $path = static::pathNFe($nf->Filial) . "assinadas/" . $nf->emissao->format('Ym');
        if ($criar) {
            @mkdir($path, 0775, true);
        }
        $path .= "/{$nf->nfechave}-NFe.xml";
        return $path;
    }

    public static function pathNFeAutorizada(NotaFiscal $nf, bool $criar = false)
    {
        $path = static::pathNFe($nf->Filial) . "enviadas/aprovadas/" . $nf->emissao->format('Ym');
        if ($criar) {
            @mkdir($path, 0775, true);
        }
        $path .= "/{$nf->nfechave}-protNFe.xml";
        return $path;
    }

    public static function pathNFeDenegada(NotaFiscal $nf, bool $criar = false)
    {
        $path = static::pathNFe($nf->Filial) . "enviadas/denegadas/" . $nf->emissao->format('Ym');
        if ($criar) {
            @mkdir($path, 0775, true);
        }
        $path .= "/{$nf->nfechave}-NFe.xml";
        return $path;
    }

    public static function pathDanfe(NotaFiscal $nf, bool $criar = false)
    {
        $path = static::pathNFe($nf->Filial) . "pdf/" . $nf->emissao->format('Ym');
        if ($criar) {
            @mkdir($path, 0775, true);
        }
        $path .= "/{$nf->nfechave}-NFe.pdf";
        return $path;
    }

    public static function pathNFeCancelada(NotaFiscal $nf, bool $criar = false)
    {
        $path = static::pathNFe($nf->Filial) . "canceladas/" . $nf->emissao->format('Ym');
        if ($criar) {
            @mkdir($path, 0775, true);
        }
        $path .= "/{$nf->nfechave}-NFe.xml";
        return $path;
    }

    public static function pathCartaCorrecao(NotaFiscal $nf, bool $criar = false)
    {
        $path = static::pathNFe($nf->Filial) . "cartacorrecao/" . $nf->emissao->format('Ym');
        if ($criar) {
            @mkdir($path, 0775, true);
        }
        $path .= "/{$nf->nfechave}-CCe.xml";
        return $path;
    }

    public static function pathDfeGz(DistribuicaoDfe $dfe, bool $criar = false)
    {
        $ambiente = ($dfe->Filial->nfeambiente == 1)?'producao':'homologacao';
        $path = env('NFE_PHP_PATH') . "DFe/{$dfe->codfilial}/{$ambiente}/" . $dfe->criacao->format('Y/m');
        if ($criar) {
            @mkdir($path, 0775, true);
        }
        $path .= "/{$dfe->coddistribuicaodfe}.xml.gz";
        return $path;
    }
}
