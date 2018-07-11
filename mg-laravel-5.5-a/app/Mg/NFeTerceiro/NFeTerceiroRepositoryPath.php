<?php

namespace Mg\NFeTerceiro;

use Mg\NFeTerceiro\NotaFiscalTerceiroDistribuicaoDfe;
use Mg\Filial\Filial;

class NFeTerceiroRepositoryPath
{

    /**
     * Diretorio Raiz das NFes da Filial
     */
    public static function pathNFe(Filial $filial) {
      return env('NFE_TERCEIRO_PATH') . "{$filial->codfilial}/";
    }

    public static function pathNFeTerceiro ( $filial, $chave, bool $criar = false)
    {
        $path = static::pathNFe($filial) ;
        if ($criar) {
            @mkdir($path, 0775, true);
        }
        $path .= "{$chave}-distDfe.xml";
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
