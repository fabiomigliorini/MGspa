<?php

namespace Mg\Saurus\S2Pay;

use Illuminate\Support\Facades\Log;

class ApiService 
{

    const TIMEOUT = 5;

    public static function functionAutorizacao($chavePDV, $cnpj) {
        $chave = base64_encode($chavePDV . '|' . cnpjLimpo($cnpj));

        try {
            $curl = curl_init();
            $url = config('services.saurus.url') . '/api/FunctionAutorizacao';
            $headers = [
                'Chave: ' . $chave,
                'Credencial: ' . config('services.saurus.credential'),
                'Content-Type: application/json',
            ];

            $opt = [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => static::TIMEOUT,
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
        $url = config('services.saurus.url') . '/api/FunctionPdv';
        $headers = [
            'Authorization: ' . $autorizacao->response->chavePublica,
            'Content-Type: application/json'
        ];

        $data = [
            'id' => $chavePDV,
            'dominio' => config('services.saurus.dominio'),
            'numero' => "$numero",
            'razaoSocial' => $pessoa->pessoa,#pessoa-filial
            'documento' => cnpjLimpo($pessoa->cnpj),#pessoa-filial
            'chavePublica' => config('services.saurus.credential'), #env
        ];

        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => static::TIMEOUT,
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
        $url = config('services.saurus.url') . '/api/FunctionPdv';
        $headers = [
            'Authorization: ' . $autorizacao,
            'Content-Type: application/json'
        ];

        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => static::TIMEOUT,
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
        $url = config('services.saurus.url') . '/api/FunctionPedido?substituir=1';
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
            // o aparelho ativo do PDV Saurus (uuid da Saurus); quem recebe a cobranca e' o PDV (ChavePDV)
            "IdPinPad" => $pos->id,
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
            CURLOPT_TIMEOUT => static::TIMEOUT,
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
        $url = config('services.saurus.url') . '/api/FunctionPagamento?idFaturaPag='. $idfaturapag .'&type=pagamento';
        $headers = [
            'Authorization: ' . $autorizacao,
            'Content-Type: application/json'
        ];

        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => static::TIMEOUT,
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
