<?php

namespace Mg\NFePHP;

use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;

use Mg\Filial\Filial;

class NFePHPRepositoryRobo
{

    public static function modoAutomatico() {

        // carrega notas pendentes
        $pendentes = NFePHPRepository::pendentes();

        // percorre as pendentes e envia sincrono
        foreach ($pendentes->codnotafiscal as $codnotafiscal) {

            $res = NFePHPRepository::enviarSincrono($codnotafiscal);

            // se o resultado for duplicidade 204 ou ja cancelada 218 faz uma consulta
            if ($res->cStat == 204 || $res->cStat == 218 ) {
                $consulta = NFePHPRepository::consulta($codnotafiscal);
                
                // se a consulta rtornar false cria o xml
                if ($consulta->sucesso == false){
                    $criar = NFePHPRepository::criar($codnotafiscal);
                    $res = NFePHPRepository::enviarSincrono($codnotafiscal);
                }else {
                    dd('aqui');
                }
            }

        }

    }


    public static function consulta() {

    }

    public static function criaXml() {

    }

    public static function percorrer() {

    }


}
