<?php

namespace Mg\Pix\Sicredi;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

use Mg\Filial\Filial;
use Mg\Portador\Portador;

class PixSicrediApiService
{

    private static function opcoesCurlMTls(): array
    {
        $cert = env('SICREDI_CERTIFICADO');
        if (empty($cert) || !file_exists($cert)) {
            throw new \Exception('Certificado do Sicredi não configurado ou não encontrado!');
        }
        $opt = [
            CURLOPT_SSLCERT => $cert,
            CURLOPT_SSLCERTTYPE => 'PEM',
        ];
        $key = env('SICREDI_CERTIFICADO_KEY');
        if (!empty($key)) {
            $opt[CURLOPT_SSLKEY] = $key;
            $opt[CURLOPT_SSLKEYTYPE] = 'PEM';
        }
        $senha = env('SICREDI_CERTIFICADO_SENHA');
        if (!empty($senha)) {
            $opt[CURLOPT_SSLCERTPASSWD] = $senha;
        }
        return $opt;
    }

    public static function token(Portador $portador): array
    {
        $url = env('SICREDI_URL_OAUTH');
        $authorization = base64_encode("{$portador->bbclientid}:{$portador->bbclientsecret}");
        $body = 'grant_type=client_credentials&scope=cob.read+cob.write+pix.read+pix.write';

        $curl = curl_init();
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => 1,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic {$authorization}",
                "Content-Type: application/x-www-form-urlencoded"
            ],
        ] + static::opcoesCurlMTls();
        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);
        if ($response === false) {
            throw new \Exception(curl_error($curl), curl_errno($curl));
        }
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($httpcode == 401) {
            throw new \Exception("Erro {$httpcode} - {$response} ao Autenticar na API Pix do Sicredi!", $httpcode);
        }
        if ($httpcode >= 400) {
            throw new \Exception("Erro {$httpcode} ao Autenticar na API Pix do Sicredi: {$response}", $httpcode);
        }
        $ret = json_decode($response, true);
        return $ret;
    }

    public static function transmitirPixCob(
        string $token,
        string $chave,
        string $txid,
        int $expiracao,
        float $valorOriginal,
        string $solicitacaoPagador = null,
        string $nome = null,
        string $cpf = null,
        string $cnpj = null
    ): array {
        $arr = [
            "calendario" => [
                "expiracao" => $expiracao
            ],
            "valor" => [
                "original" => number_format($valorOriginal, 2, '.', '')
            ],
            "chave" => $chave,
        ];
        if (!empty($solicitacaoPagador)) {
            $arr['solicitacaoPagador'] = $solicitacaoPagador;
        }
        if (!empty($cpf)) {
            $arr['devedor']['nome'] = $nome;
            $arr['devedor']['cpf'] = str_pad(number_format($cpf, 0, '.', ''), 11, '0', STR_PAD_LEFT);
        } elseif (!empty($cnpj)) {
            $arr['devedor']['nome'] = $nome;
            $arr['devedor']['cnpj'] = str_pad(number_format($cnpj, 0, '.', ''), 14, '0', STR_PAD_LEFT);
        }

        $body = json_encode($arr);
        $url = env('SICREDI_URL_PIX') . '/cob/' . $txid;
        $curl = curl_init();
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => 1,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$token}",
                "Content-Type: application/json"
            ],
        ] + static::opcoesCurlMTls();
        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);
        if ($response === false) {
            throw new \Exception(curl_error($curl), curl_errno($curl));
        }
        curl_close($curl);
        $response = preg_replace('/[\x00-\x1F\x7F]/', '', $response);
        $ret = json_decode($response, true);
        return $ret;
    }

    public static function consultarPixCob(
        string $token,
        string $txid
    ): array {
        $url = env('SICREDI_URL_PIX') . '/cob/' . $txid;
        $curl = curl_init();
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => 1,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$token}",
                "Content-Type: application/json"
            ],
        ] + static::opcoesCurlMTls();
        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);
        if ($response === false) {
            throw new \Exception(curl_error($curl), curl_errno($curl));
        }
        curl_close($curl);
        $response = preg_replace('/[\x00-\x1F\x7F]/', '', $response);
        $ret = json_decode($response, true);
        return $ret;
    }

    public static function consultarPix(
        string $token,
        string $inicio = null,
        string $fim = null,
        int $paginaAtual = 0
    ): array {
        $data = [];
        if (!empty($inicio)) {
            $data['inicio'] = $inicio;
        }
        if (!empty($fim)) {
            $data['fim'] = $fim;
        }
        if (!empty($paginaAtual)) {
            $data['paginacao.paginaAtual'] = $paginaAtual;
        }

        $url = env('SICREDI_URL_PIX') . '/pix';
        if (!empty($data)) {
            $url .= '?' . http_build_query($data);
        }

        $curl = curl_init();
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => 1,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$token}",
                "Content-Type: application/json"
            ],
        ] + static::opcoesCurlMTls();
        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);
        if ($response === false) {
            throw new \Exception(curl_error($curl), curl_errno($curl));
        }
        curl_close($curl);
        $response = preg_replace('/[\x00-\x1F\x7F]/', '', $response);
        $ret = json_decode($response, true);
        return $ret;
    }

}
