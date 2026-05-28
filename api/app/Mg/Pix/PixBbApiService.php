<?php

namespace Mg\Pix;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

use Mg\Filial\Filial;
use Mg\Filial\CertificadoService;

class PixBbApiService
{

    /*
    * Esta rotina consome o endpoint PUT /cob do BB para emissão da cobrança Pix (v2)
    */
    public static function transmitirPixCob(
        Filial $filial,
        $token,
        $gwDevAppKey,
        $chave,
        $txid,
        $expiracao,
        $valorOriginal,
        $solicitacaoPagador,
        $nome,
        $cpf,
        $cnpj
        )
    {
        // informações da cobrança
        $arr = [
          "calendario" => [
            "expiracao" => $expiracao
          ],
          "valor" => [
            "original" => number_format($valorOriginal, 2, '.', '')
          ],
          "chave" => $chave,
        ];
        if (!empty($solicitacaopagador)) {
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
        $url = env('BB_URL_PIX') . '/cob/' . $txid . '?gw-dev-app-key=' . $gwDevAppKey;
        $auth = "Authorization: Bearer {$token}";
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
                $auth,
                "Content-Type: application/json"
            ],
        ] + CertificadoService::opcoesCurlMTls($filial);
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

    /*
    * Esta rotina consome o endpoint GET /cob do BB para consultar uma cobrança
    */
    public static function consultarPixCob(
        Filial $filial,
        $token,
        $gwDevAppKey,
        $txid
    )
    {
        $url = env('BB_URL_PIX') . '/cob/' . $txid . '?gw-dev-app-key=' . $gwDevAppKey;
        $auth = "Authorization: Bearer {$token}";

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
                $auth,
                "Content-Type: application/json"
            ],
        ] + CertificadoService::opcoesCurlMTls($filial);
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

    /*
    * Esta rotina consome o endpoint GET /pix do BB para consultar os pix recebidos
    * no periodo de 5 dias no maximo entre o inicio e fim
    */
    public static function consultarPix(
        Filial $filial,
        string $token,
        string $gwDevAppKey,
        string $inicio = null,
        string $fim = null,
        int $paginaAtual = 0
    ){

        // monta parametros da URL
        $data = [
            'gw-dev-app-key' => $gwDevAppKey
        ];
        if (!empty($inicio)) {
            $data['inicio'] = $inicio;
        }
        if (!empty($fim)) {
            $data['fim'] = $fim;
        }
        if (!empty($paginaAtual)) {
            $data['paginacao.paginaAtual'] = $paginaAtual;
        }

        // monta URL
        $url = env('BB_URL_PIX') . '/pix?' . http_build_query($data);

        // Token Auth
        $auth = "Authorization: Bearer {$token}";

        Log::info('PixBbApiService::consultarPix', [
            'url' => $url,
            'filial' => $filial->codfilial,
            'gwDevAppKey' => $gwDevAppKey,
            'pfxPath' => CertificadoService::pfxPath($filial),
        ]);

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
                $auth,
                "Content-Type: application/json"
            ],
        ] + CertificadoService::opcoesCurlMTls($filial);

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

    public static function qrCode($qrcode)
    {

        $url = 'https://api.qrserver.com/v1/create-qr-code/?size=513x513&data=' .
        urlencode($qrcode);

        $curl = curl_init();
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ];
        curl_setopt_array($curl, $opt);
        $img = curl_exec($curl);
        return $img;
    }

}
