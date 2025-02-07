<?php

namespace Mg\Saurus\S2Pay;

use Illuminate\Support\Facades\Log;

class ApiService 
{
    public static function functionAutorizacao($chavePDV, $cnpj) {
        $chave = base64_encode($chavePDV . '|' . str_pad($cnpj, 14, '0', STR_PAD_LEFT));

        try {
            $curl = curl_init();
            $url = env('SAURUS_S2PAY_URL') . '/api/FunctionAutorizacao';
            $headers = [
                'Chave: ' . $chave,
                'Credencial: ' . env('SAURUS_S2PAY_CREDENTIAL'),
                'Content-Type: application/json',
            ];

            $opt = [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,	    
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => $headers,
            ];


            curl_setopt_array($curl, $opt);
            $response = curl_exec($curl);
            curl_close($curl);

            if ($response === false) {
                throw new \Exception('Falha ao acessar API da Saurus!', 1);
            }

            return json_decode($response);
        } catch (\Exception $e) {
            Log::info('Falha ao acessar API da Saurus!' . $e->getMessage());
            throw new \Exception('Falha ao acessar API da Saurus!', 1);
        }
    }

    public static function functionPdvRegistrar($chavePDV, $pessoa, $numero) {
    
        $autorizacao = self::functionAutorizacao($chavePDV, $pessoa->cnpj);

        $curl = curl_init();
        $url = env('SAURUS_S2PAY_URL') . '/api/FunctionPdv';
        $headers = [
            'Authorization: ' . $autorizacao->response->chavePublica,
            'Content-Type: application/json'
        ];

        $data = [
            'id' => $chavePDV,
            'dominio' => str_pad($pessoa->cnpj, 14, '0', STR_PAD_LEFT), #pessoa-filial
            'numero' => $numero,
            'razaoSocial' => $pessoa->pessoa,#pessoa-filial
            'documento' => str_pad($pessoa->cnpj, 14, '0', STR_PAD_LEFT),#pessoa-filial
            'chavePublica' => env('SAURUS_S2PAY_CREDENTIAL'), #env
        ];

        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,	    
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
        ];

        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);
        curl_close($curl);

        
        if ($response === false) {
            throw new \Exception('Falha ao acessar API da Saurus!', 1);
        }

        $response = (object) [
            'autorizacao' => $autorizacao,
            'pdv' => json_decode($response)
        ];

        return $response;
    }

    public static function functionPdvVerificar($autorizacao) {
        $curl = curl_init();
        $url = env('SAURUS_S2PAY_URL') . '/api/FunctionPdv';
        $headers = [
            'Authorization: ' . $autorizacao,
            'Content-Type: application/json'
        ];

        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,	    
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => $headers,
        ];

        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);
        curl_close($curl);

        if ($response === false) {
            throw new \Exception('Falha ao acessar API da Saurus!', 1);
        }

        return json_decode($response);
    }

    public static function functionPedidoCriar($ped, $pdv, $pos) {
        $curl = curl_init();
        $url = env('SAURUS_S2PAY_URL') . '/api/FunctionPedido?substituir=1';
        $headers = [
            'Authorization: ' . $pdv->autorizacao,
            'Content-Type: application/json'
        ];


        $data = [
            "ChavePDV" => $pdv->id,
            "ChavePublica" => $pdv->chavepublica,
            "Cliente" => (object)[],
            "FormaPagamento" => [
                "TpTransacao" => 5,
                "idFaturaPag" => $ped->idfaturapag,
                "ModPagamento" => $ped->modpagamento,
                "QtdParcelas" => $ped->parcelas,
                "ValorPagamento" => $ped->valortotal,
                "ExternalId" => null
            ],
            "IdPedido" => $ped->idpedido,
            "IdPinPad" => $pos->serial,
            "IndStatus" => 0,
            "Itens" => [
                [
                    "Codigo" => "1",
                    "Descricao" => "COMPRAS DIVERSAS",
                ]
            ]

        ];


        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,	    
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
        ];

        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);

        if ($response === false) {
            throw new \Exception('Falha ao acessar API da Saurus!', 1);
        }

        return json_decode($response);
    }

    public static function functionPagamentoConsultar($idfaturapag, $autorizacao) {
        $curl = curl_init();
        $url = env('SAURUS_S2PAY_URL') . '/api/FunctionPagamento?idFaturaPag='. $idfaturapag .'&type=pagamento';
        $headers = [
            'Authorization: ' . $autorizacao,
            'Content-Type: application/json'
        ];

        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,	    
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => $headers,
        ];

        curl_setopt_array($curl, $opt);

        $response = curl_exec($curl);

        if ($response === false) {
            throw new \Exception('Falha ao acessar API da Saurus!', 1);
        }

        return json_decode($response);

    }
}
