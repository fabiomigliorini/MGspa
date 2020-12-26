<?php

namespace Mg\Pix\GerenciaNet;

use Carbon\Carbon;

use Mg\Pix\PixCob;
use Mg\Pix\PixStatus;

class GerenciaNetService
{
    public static function getAccessToken()
    {
        /*
         # Esta rotina consome um endpoid POST da Gerencianet para realizar a geração do AccessToken
         */
        $curl = curl_init();
        $authorization = env('PIX_GERENCIANET_CLIENT_ID').':'.env('PIX_GERENCIANET_CLIENT_SECRET');
        $authorization = base64_encode($authorization);
        $opt = [
            CURLOPT_URL => env('PIX_GERENCIANET_URL_AUTH'), // Rota base, desenvolvimento ou produção
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => '{"grant_type": "client_credentials"}',
            CURLOPT_SSLCERT => env('PIX_GERENCIANET_CERTIFICADO'), // Caminho do certificado
            CURLOPT_SSLCERTPASSWD => "",
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic $authorization",
                "Content-Type: application/json"
            ],
        ];
        curl_setopt_array($curl, $opt);

        // curl_setopt($curl, CURLOPT_VERBOSE, true);
        // $verbose = fopen('php://temp', 'w+');
        // curl_setopt($curl, CURLOPT_STDERR, $verbose);

        $response = curl_exec($curl);

        // if ($response === FALSE) {
        //     printf("cUrl error (#%d): %s<br>\n", curl_errno($curl),
        //            htmlspecialchars(curl_error($curl)));
        // }
        // rewind($verbose);
        // $verboseLog = stream_get_contents($verbose);
        // echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";

        // dd($response);
        curl_close($curl);

        if ($response === false) {
            throw new \Exception('Falha ao autenticar na GerenciaNet!', 1);
        }

        return json_decode($response, true);
    }

    public static function verficarFalhas($dadosPix)
    {
        if (isset($dadosPix["mensagem"]) || !$dadosPix) { // Se apresentar falha, irá mostrar a mensagem
            $mensagemException = (!$dadosPix) ? "Falha ao gerar a cobrança, tente novamente" : "Falha PIX\nMensagem: " . $dadosPix["mensagem"];
            if (isset($dadosPix["erros"])) { // Se apresentar erro, irá mostrar o erro e o campo
                foreach ($dadosPix["erros"] as $key => $erro) {
                    $mensagemException .= "\nErro " . ($key + 1) . "\nCaminho: " . $erro["caminho"] . "\nMensagem de erro: " . $erro["mensagem"] . "\n\n";
                }
            }
            throw new \Exception($mensagemException, 1);
        }
    }

    /*
    * Esta rotina consome um endpoid PUT da Gerencianet para emissão da cobrança
    */
    public static function transmitirPixCob(PixCob $cob)
    {

        // Busca informações do token de autenticação de acordo com suas credencias e certificado
        $dadosToken = static::getAccessToken();
        $tokenType = $dadosToken['token_type'];
        $accessToken = $dadosToken['access_token'];

        $pix_url_cob = env('PIX_GERENCIANET_URL_COB') . '/' . $cob->txid; // Monta a url para a requisição que gera a cobrança

        // Insira as informações da cobrança
        $body = [
          "calendario" => [
            "expiracao" => $cob->expiracao // [opcional] Tempo de vida da cobrança, especificado em segundos a partir da data de criação. Caso não definido o padrão será de 86400 segundos ( 24 horas)
          ],
          "valor" => [
            "original" => number_format($cob->valororiginal, 2, '.', '') // [obrigatório] Valor original da cobrança.string \d{1,10}.\d{2} Obs: Para QR Code dinâmico, valor mínimo é de 0.01. Para QR Code poderá ser 0.00 (Ficará aberto para o pagador definir o valor)
          ],
          "chave" => $cob->Portador->pixdict, // [obrigatório] Determina a chave Pix registrada no DICT que será utilizada para a cobrança.
        ];
        if (!empty($cob->solicitacaopagador)) {
            $body['solicitacaoPagador'] = $cob->solicitacaopagador;
        }

        if (!empty($cob->cpf)) {
            $body['devedor']['nome'] = $cob->nome;
            $body['devedor']['cpf'] = str_pad(number_format($cob->cpf, 0, '.', ''), 11, '0', STR_PAD_LEFT);
        } elseif (!empty($cob->cnpj)) {
            $body['devedor']['nome'] = $cob->nome;
            $body['devedor']['cnpj'] = str_pad(number_format($cob->cnpj, 0, '.', ''), 14, '0', STR_PAD_LEFT);
        }

        $curl = curl_init();
        $opt = [
            CURLOPT_URL => $pix_url_cob,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_SSLCERT => env('PIX_GERENCIANET_CERTIFICADO'),
            CURLOPT_SSLCERTPASSWD => "",
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => [
                "authorization: $tokenType $accessToken",
                "Content-Type: application/json"
            ],
        ];
        curl_setopt_array($curl, $opt);
        $dadosPix = json_decode(curl_exec($curl), true);
        curl_close($curl);
        static::verficarFalhas($dadosPix); // Se encontrar falhas, apresentará a mensagem de erro e encerrará a execução

        $status = PixStatus::firstOrCreate([
            'pixstatus' => $dadosPix['status']
        ]);

        $ret = $cob->update([
            'location' => $dadosPix['location'],
            'codpixstatus' => $status->codpixstatus
        ]);

        $cob = $cob->fresh();
        return $cob;
    }
    /*
    * Esta rotina consome um endpoid PUT da Gerencianet para emissão da cobrança
    */
    public static function consultarPixCob(PixCob $cob)
    {

        // Busca informações do token de autenticação de acordo com suas credencias e certificado
        $dadosToken = static::getAccessToken();
        $tokenType = $dadosToken['token_type'];
        $accessToken = $dadosToken['access_token'];

        $pix_url_cob = env('PIX_GERENCIANET_URL_COB') . '/' . $cob->txid; // Monta a url para a requisição que gera a cobrança

        $curl = curl_init();
        $opt = [
            CURLOPT_URL => $pix_url_cob,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_SSLCERT => env('PIX_GERENCIANET_CERTIFICADO'),
            CURLOPT_SSLCERTPASSWD => "",
            CURLOPT_HTTPHEADER => [
                "authorization: $tokenType $accessToken",
                "Content-Type: application/json"
            ],
        ];
        curl_setopt_array($curl, $opt);
        $dadosPix = json_decode(curl_exec($curl), true);
        curl_close($curl);
        static::verficarFalhas($dadosPix); // Se encontrar falhas, apresentará a mensagem de erro e encerrará a execução

        $status = PixStatus::firstOrCreate([
            'pixstatus' => $dadosPix['status']
        ]);

        $ret = $cob->update([
            'location' => $dadosPix['location'],
            'codpixstatus' => $status->codpixstatus
        ]);

        $cob = $cob->fresh();
        return $cob;
    }
}
