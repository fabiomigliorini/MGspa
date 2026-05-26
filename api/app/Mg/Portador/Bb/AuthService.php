<?php

namespace Mg\Portador\Bb;

use Exception;
use Illuminate\Support\Facades\Cache;
use Mg\Portador\Portador;

class AuthService
{
    private const SCOPE = 'cobrancas.boletos-info+cobrancas.boletos-requisicao+cob.read+cob.write+pix.read+pix.write+extrato-info';

    public static function verificaTokenValido(Portador $portador): string
    {
        $cacheKey = "bb_token_{$portador->codportador}";
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }
        $token = static::token($portador);
        $ttl = intval($token['expires_in'] * 0.5);
        Cache::put($cacheKey, $token['access_token'], $ttl);
        return $token['access_token'];
    }

    private static function token(Portador $portador): array
    {
        $curl = curl_init();
        $url = env('BB_URL_OAUTH') . '/token';
        $authorization = base64_encode("{$portador->bbclientid}:{$portador->bbclientsecret}");
        $body = 'grant_type=client_credentials&scope=' . self::SCOPE;
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic {$authorization}",
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);
        $response = curl_exec($curl);
        if ($response === false) {
            $err = curl_error($curl);
            $errno = curl_errno($curl);
            curl_close($curl);
            throw new Exception($err, $errno);
        }
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($httpcode < 200 || $httpcode >= 300) {
            throw new Exception("Erro {$httpcode} - {$response} ao autenticar na API do BB!", $httpcode);
        }
        $ret = json_decode($response, true);
        if (!isset($ret['access_token'], $ret['expires_in'])) {
            throw new Exception("Resposta inesperada ao autenticar na API do BB: {$response}");
        }
        return $ret;
    }
}
