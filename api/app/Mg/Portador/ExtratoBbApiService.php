<?php

namespace App\Mg\Portador;

use Carbon\Carbon;

class ExtratoBbApiService
{
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
                //"x-br-com-bb-ipa-mciteste: 178961031", //Todo somente no ambiente de homologação
                "Content-Type: application/json"
            ],
        ];
        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);

        if ($response === false) {
            $err = curl_error($curl);
            $errno = curl_errno($curl);
            curl_close($curl);
            throw new \Exception($err, $errno);
        }
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $response = preg_replace('/[\x00-\x1F\x7F]/', '', $response);
        if ($httpcode < 200 || $httpcode >= 300) {
            throw new \Exception("Erro {$httpcode} ao consultar extrato na API do BB: {$response}", $httpcode);
        }
        return json_decode($response, true);
    }
}