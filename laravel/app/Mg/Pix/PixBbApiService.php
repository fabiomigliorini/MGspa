<?php

namespace Mg\Pix;

use Carbon\Carbon;

use Mg\Pix\PixCob;
use Mg\Pix\PixCobStatus;
use Mg\Pix\PixService;
use Mg\Portador\Portador;

class PixBbApiService
{


    /*
    * Esta rotina consome um endpoid PUT da Gerencianet para emissão da cobrança
    */
    public static function transmitirPixCob(
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
            "expiracao" => $expiracao // [opcional] Tempo de vida da cobrança, especificado em segundos a partir da data de criação. Caso não definido o padrão será de 86400 segundos ( 24 horas)
          ],
          "valor" => [
            "original" => number_format($valorOriginal, 2, '.', '') // [obrigatório] Valor original da cobrança.string \d{1,10}.\d{2} Obs: Para QR Code dinâmico, valor mínimo é de 0.01. Para QR Code poderá ser 0.00 (Ficará aberto para o pagador definir o valor)
          ],
          "chave" => $chave, // [obrigatório] Determina a chave Pix registrada no DICT que será utilizada para a cobrança.
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
        $url = env('BB_URL_PIX') . '/cobqrcode/' . $txid . '?gw-dev-app-key=' . $gwDevAppKey; // Monta a url para a requisição que gera a cobrança
        $auth = "Authorization: Bearer {$token}";
        $curl = curl_init();
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            // CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                $auth,
                "Content-Type: application/json"
            ],
        ];
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
    * Esta rotina consome um endpoid GET da Gerencianet para consultar uma cobrança
    */
    public static function consultarPixCob(
        $token,
        $gwDevAppKey,
        $txid
    )
    {
        $url = env('BB_URL_PIX') . '/cob/' . $txid . '?gw-dev-app-key=' . $gwDevAppKey; // Monta a url para a requisição que gera a cobrança
        $auth = "Authorization: Bearer {$token}";

        $curl = curl_init();
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                $auth,
                "Content-Type: application/json"
            ],
        ];
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
    * Esta rotina consome um endpoid GET da BB para consultar os pix recebidos
    * nos periodo de 5 dias no maximo entre o inicio e fim
    */
    public static function consultarPix(
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
            $data['paginaAtual'] = $paginaAtual;
        }

        // monta URL
        $url = env('BB_URL_PIX') . '/?' . http_build_query($data); // Monta a url para a requisição que gera a cobrança

        // Token Auth
        $auth = "Authorization: Bearer {$token}";

        $curl = curl_init();
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                $auth,
                "Content-Type: application/json"
            ],
        ];

        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);
        if ($response === false) {
            throw new \Exception(curl_error($curl), curl_errno($curl));
        }
        curl_close($curl);
        // file_put_contents('/tmp/host/ret.json', $response);
        $response = preg_replace('/[\x00-\x1F\x7F]/', '', $response);
        $ret = json_decode($response, true);
        return $ret;
    }

    public static function qrCode($qrcode)
    {

        // $url = 'https://chart.googleapis.com/chart?chs=513x513&cht=qr&chl=' .
        //     urlencode($qrcode);

        $url = 'https://api.qrserver.com/v1/create-qr-code/?size=513x513&data=' .
        urlencode($qrcode);

        $curl = curl_init();
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ];
        curl_setopt_array($curl, $opt);
        $img = curl_exec($curl);
        return $img;
    }

}
