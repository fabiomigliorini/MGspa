<?php

namespace Mg\Mdfe;

use Mg\Mdfe\Mdfe;
use Mg\Dfe\DistribuicaoDfe;
use Mg\Filial\Filial;

class MdfeNfePhpPathService
{

    /**
     * Diretorio Raiz das Mdfes da Filial
     */
    public static function pathMdfe(Filial $filial)
    {
        $ambiente = ($filial->nfeambiente == 1)?'producao':'homologacao';
        return env('NFE_PHP_PATH') . "Mdfe/{$filial->codfilial}/{$ambiente}/";
    }

    public static function pathMdfeCriado(Mdfe $mdfe, bool $criar = false)
    {
        $path = static::pathMdfe($mdfe->Filial) . "criado/" . $mdfe->emissao->format('Y/m');
        if ($criar) {
            @mkdir($path, 0775, true);
        }
        $path .= "/{$mdfe->chmdfe}-MDFe.xml";
        return $path;
    }

    public static function pathMdfeRetorno(Mdfe $mdfe, bool $criar = false)
    {
        $path = static::pathMdfe($mdfe->Filial) . "retorno/" . $mdfe->emissao->format('Y/m');
        if ($criar) {
            @mkdir($path, 0775, true);
        }
        $path .= "/{$mdfe->chmdfe}-" . date('Y-m-d.H-i-s') . "-Retorno.xml";
        return $path;
    }

    public static function pathMdfeAutorizado(Mdfe $mdfe, bool $criar = false)
    {
        $path = static::pathMdfe($mdfe->Filial) . "autorizado/" . $mdfe->emissao->format('Y/m');
        if ($criar) {
            @mkdir($path, 0775, true);
        }
        $path .= "/{$mdfe->chmdfe}-MDFe.xml";
        return $path;
    }

    public static function pathDamdfe(Mdfe $mdfe, bool $criar = false)
    {
        $path = static::pathMdfe($mdfe->Filial) . "damdfe/" . $mdfe->emissao->format('Y/m');
        if ($criar) {
            @mkdir($path, 0775, true);
        }
        $path .= "/{$mdfe->chmdfe}-MDFe.pdf";
        return $path;
    }

    /*
    public static function pathMdfeDenegada(Mdfe $mdfe, bool $criar = false)
    {
        $path = static::pathMdfe($mdfe->Filial) . "enviadas/denegadas/" . $mdfe->emissao->format('Y/m');
        if ($criar) {
            @mkdir($path, 0775, true);
        }
        $path .= "/{$mdfe->chmdfe}-Mdfe.xml";
        return $path;
    }

    public static function pathMdfeCancelada(Mdfe $mdfe, bool $criar = false)
    {
        $path = static::pathMdfe($mdfe->Filial) . "canceladas/" . $mdfe->emissao->format('Y/m');
        if ($criar) {
            @mkdir($path, 0775, true);
        }
        $path .= "/{$mdfe->chmdfe}-Mdfe.xml";
        return $path;
    }

    public static function pathCartaCorrecao(Mdfe $mdfe, bool $criar = false)
    {
        $path = static::pathMdfe($mdfe->Filial) . "cartacorrecao/" . $mdfe->emissao->format('Y/m');
        if ($criar) {
            @mkdir($path, 0775, true);
        }
        $path .= "/{$mdfe->chmdfe}-CCe.xml";
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
    */
}
