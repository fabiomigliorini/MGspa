<?php

namespace Mg\NFePHP;

use Exception;

use NFePHP\Ibpt\RestInterface;

/**
 * Cliente REST do IBPT com timeout curto.
 *
 * O Rest padrão do sped-ibpt usa CONNECTTIMEOUT 40s e TIMEOUT 60s. Como a consulta
 * acontece item a item durante a montagem do XML, uma nota de 10 itens fica presa
 * mais de 6 minutos quando a API está fora - e segura a fila inteira atrás dela.
 * Aqui o teto é de poucos segundos: tributo aproximado não vale uma emissão parada.
 */
class MgIbptRest implements RestInterface
{
    const TIMEOUT_CONEXAO = 3;
    const TIMEOUT_TOTAL = 5;

    public function pull($uri)
    {
        $oCurl = curl_init($uri);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_CONNECTTIMEOUT, self::TIMEOUT_CONEXAO);
        curl_setopt($oCurl, CURLOPT_TIMEOUT, self::TIMEOUT_TOTAL);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($oCurl);
        $erro = curl_error($oCurl);
        $errno = curl_errno($oCurl);
        $httpcode = curl_getinfo($oCurl, CURLINFO_HTTP_CODE);
        curl_close($oCurl);

        if ($errno != 0) {
            throw new Exception("Erro cURL [{$errno}] {$erro}");
        }

        // Mantém o contrato do Rest original: em erro HTTP devolve um JSON com httpcode
        if ($httpcode != 200) {
            return json_encode([
                'error' => empty($erro) ? 'SUCESSO' : $erro,
                'response' => $response,
                'httpcode' => $httpcode,
            ]);
        }

        return $response;
    }
}
