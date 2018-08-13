<?php

namespace Mg\NFeTerceiro;

use Mg\NFeTerceiro\NotaFiscalTerceiroDistribuicaoDfe;
use Mg\Filial\Filial;

class NotaFiscalTerceiroRepositoryPath
{

    /**
     * Diretorio Raiz das NFes da Filial
     * DFE_PATH=/var/www/NFePHP/Arquivos/DistDFe/
     * NFE_TERCEIRO_PATH=/var/www/NFePHP/Arquivos/NFeTerceiro/
     */
    public static function pathNFe(Filial $filial) {
      return env('NFE_PHP_PATH') . "DistDFe/{$filial->codfilial}/";
    }

    public static function pathDFe ( $filial, $numnsu, bool $criar = false)
    {
        $path = static::pathNFe($filial) . "DFe/" ;
        if ($criar) {
            @mkdir($path, 0775, true);
        }
        $path .= "{$numnsu}-distDfe.xml";
        return $path;
    }

    public static function pathNFeTerceiro ( $filial, $chave, bool $criar = false)
    {
        $path = static::pathNFe($filial) . "NFeTerceiro/";
        if ($criar) {
            @mkdir($path, 0775, true);
        }
        $path .= "{$chave}-NFE.xml";
        return $path;
    }

    // public static function pathDanfe (NotaFiscal $nf, bool $criar = false)
    // {
    //     $path = static::pathNFe($nf->Filial) . "pdf/" . $nf->emissao->format('Ym');
    //     if ($criar) {
    //         @mkdir($path, 0775, true);
    //     }
    //     $path .= "/{$nf->nfechave}-NFe.pdf";
    //     return $path;
    // }
}
