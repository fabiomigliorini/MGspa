<?php

namespace App\Mg\Portador;

use Carbon\Carbon;
use Mg\Portador\Portador;

class ExtratoBbApiService
{
    //TODO: esse código esta replicado em BoletoBbApiService que também é usado em PixBbService. Manter em único lugar depois
    public static function token (Portador $portador)
    {
        $curl = curl_init();
        $url = env('BB_URL_OAUTH') . '/token';
        $authorization = base64_encode("{$portador->bbclientid}:{$portador->bbclientsecret}");
        $auth = "Authorization: Basic {$authorization}";
        $body = 'grant_type=client_credentials&scope=extrato-info';
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => 0,
            // CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                $auth,
                'Content-Type: application/x-www-form-urlencoded'
            ],
        ];
        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);
        if ($response === false) {
            throw new \Exception(curl_error($curl), curl_errno($curl));
        }
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($httpcode == 401) {
            throw new \Exception("Erro {$httpcode} - {$response} ao Autenticar na API do BB!", $httpcode);
        }
        curl_close($curl);
        //print_r($response);
        $ret = json_decode($response, true);
        return $ret;
    }

    public static function contaCorrente (
        $token,
        $gwDevAppKey,
        $agenciaBeneficiario,
        $contaBeneficiario,
        Carbon $dataInicioSolicitacao = null,
        Carbon $dataFimSolicitacao = null,
        $numeroPaginaSolicitacao = 1
    ){
        $url = env('BB_URL_EXTRATO') . '/conta-corrente/agencia/' . $agenciaBeneficiario . '/conta/' . $contaBeneficiario
            . '?gw-dev-app-key=' . $gwDevAppKey
            . '&numeroPaginaSolicitacao=' . $numeroPaginaSolicitacao;

        if($dataInicioSolicitacao && $dataFimSolicitacao){
            $url = $url . '&dataInicioSolicitacao=' . (int)$dataInicioSolicitacao->format('dmY')
                . '&dataFimSolicitacao=' . (int)$dataFimSolicitacao->format('dmY');
        }

        $auth = "Authorization: Bearer {$token}";

        $curl = curl_init();
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                $auth,
                "x-br-com-bb-ipa-mciteste: 178961031", //Todo somente no ambiente de homologação
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
}