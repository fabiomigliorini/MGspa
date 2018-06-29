<?php

namespace Mg\NFePHP;

use Mg\NotaFiscal\NotaFiscal;

class NFePHPRepositoryRobo
{

    public static function resolverPendentes()
    {
        // carrega notas pendentes
        $pendentes = NFePHPRepository::pendentes();

        // percorre as pendentes e chama metodo para resolver cada uma das notas
        foreach ($pendentes as $pendente) {
            $nf = NotaFiscal::findOrFail($pendente->codnotafiscal)
            static::resolver($nf);
        }

    }

    public static function resolvido (NotaFiscal $nf)
    {
        if (!empty($nf->nfeautorizacao)) {
            return true;
        }
        if (!empty($nf->nfeinutilizacao)) {
          return true;
        }
        if (!empty($nf->nfecancelamento)) {
            return true;
        }
        return false;
    }

    public static function resolver(NotaFiscal $nf)
    {
        if (static::resolvido($nf)) {
            return true;
        }

        $resEnvioSincrono = NFePHPRepository::enviarSincrono($nf);
        $nf = $nf->fresh();

        if (static::resolvido($nf)) {
            NFePHPRepositoryMail::mail($nf);
            return true;
        }

        // 204 - duplicidade
        // 218 - ja cancelada
        if (in_array($resEnvioSincrono->cStat, [204, 218])) {
            $resConsulta = NFePHPRepository::consultar($nf);
            $nf = $nf->fresh();
            if (static::resolvido($nf)) {
                if (empty($nf->nfecancelamento)) {
                    NFePHPRepositoryMail::mail($nf);
                }
                return true;
            }
        } else {
            NFePHPRepository::criar($nf);
            $resEnvioSincrono = NFePHPRepository::enviarSincrono($nf);
            if (static::resolvido($nf)) {
                NFePHPRepositoryMail::mail($nf);
                return true;
            }
        }
        return false;

    }


    public static function consulta() {

    }

    public static function criaXml() {

    }

    public static function percorrer() {

    }


}
