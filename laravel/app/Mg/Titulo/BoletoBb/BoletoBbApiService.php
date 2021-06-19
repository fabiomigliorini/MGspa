<?php

namespace Mg\Titulo\BoletoBb;

use Mg\Portador\Portador;

class BoletoBbApiService
{
    public static function token (Portador $portador)
    {
        $curl = curl_init();
        $url = env('BB_URL_OAUTH') . '/token';
        $authorization = base64_encode("{$portador->bbclientid}:{$portador->bbclientsecret}");
        $auth = "Authorization: Basic {$authorization}";
        $body = json_encode([
            'grant_type' => 'client_credentials',
            'scope' => 'cobrancas.boletos-info+cobrancas.boletos-requisicao',
        ]);

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
            CURLOPT_CUSTOMREQUEST => "POST",
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
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($httpcode == 401) {
            throw new \Exception("Erro {$httpcode} - {$response} ao Autenticar na API do BB!", $httpcode);
        }
        curl_close($curl);
        return json_decode($response, true);
    }

    public static function registrar (
        $token,
        $gwDevAppKey,
        $numeroConvenio,
        $numeroCarteira,
        $numeroVariacaoCarteira,
        $dataEmissao,
        $dataVencimento,
        $valorOriginal,
        $numeroTituloBeneficiario,
        $numeroTituloCliente,
        $tipoInscricao,
        $numeroInscricao,
        $nome,
        $endereco,
        $cep,
        $cidade,
        $bairro,
        $uf,
        $telefone
    )
    {
        $arr = [
            'numeroConvenio' => $numeroConvenio,
            'numeroCarteira' => $numeroCarteira,
            'numeroVariacaoCarteira' => $numeroVariacaoCarteira,
            'codigoModalidade' => 1,
            'dataEmissao' => $dataEmissao->format('d.m.Y'),
            'dataVencimento' => $dataVencimento->format('d.m.Y'),
            'valorOriginal' => $valorOriginal,
            'indicadorAceiteTituloVencido' => 'S',
            'numeroDiasLimiteRecebimento' => 90,
            'codigoAceite' => 'A',
            'codigoTipoTitulo' => 2,
            'indicadorPermissaoRecebimentoParcial' => 'N',
            'numeroTituloBeneficiario' => $numeroTituloBeneficiario,
            'numeroTituloCliente' => $numeroTituloCliente,
            // 'mensagemBloquetoOcorrencia' => 'Aproveite os 2% de desconto se pago ate 25/jun/2021!',
            // 'desconto' => [
            //     'tipo' => 2,
            //     'dataExpiracao' => '25.06.2021',
            //     'porcentagem' => 2,
            //     'valor' => 0
            // ],
            'jurosMora' => [
                'tipo' => 2,
                'porcentagem' => 5,
                // 'valor' => 0
            ],
            'multa' => [
                'tipo' => 2,
                'data' => $dataVencimento->addDay()->format('d.m.Y'),
                'porcentagem' => 5,
                // 'valor' => 0
            ],
            'pagador' => [
                'tipoInscricao' => $tipoInscricao,
                'numeroInscricao' => $numeroInscricao,
                'nome' => $nome,
                'endereco' => $endereco,
                'cep' => $cep,
                'cidade' => $cidade,
                'bairro' => $bairro,
                'uf' => $uf,
                'telefone' => $telefone
            ],
            'indicadorPix' => 'S'
        ];
        // dd($arr);
        $body = json_encode($arr);
        $curl = curl_init();
        $url = env('BB_URL_COBRANCA') . '/boletos?gw-dev-app-key=' . $gwDevAppKey;
        $auth = "Authorization: Bearer {$token}";
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
            CURLOPT_CUSTOMREQUEST => "POST",
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
        return json_decode($response, true);
    }

    public static function consultar ($bbtoken, $gwDevAppKey, $numeroConvenio, $nossoNumero)
    {
        $curl = curl_init();
        $url = env('BB_URL_COBRANCA') . '/boletos/' . $nossoNumero;
        $url .= '?gw-dev-app-key=' . $gwDevAppKey;
        $url .= '&numeroConvenio=' . $numeroConvenio;
        $auth = "Authorization: Bearer {$bbtoken}";
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
        return json_decode($response, true);
    }

}
