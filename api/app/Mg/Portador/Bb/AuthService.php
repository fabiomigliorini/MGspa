<?php

namespace Mg\Portador\Bb;

use Exception;
use Illuminate\Support\Facades\Cache;
use Mg\Portador\Portador;

class AuthService
{
    // Escopos separados por API.
    // O BB rejeita a requisicao INTEIRA quando um dos escopos pedidos nao esta
    // autorizado para a aplicacao (nao emite token parcial), entao cada API pede
    // somente o que usa. Pedir tudo num token so faz um escopo sem autorizacao
    // derrubar boleto, pix e extrato juntos.
    public const SCOPE_COBRANCA = 'cobrancas.boletos-info cobrancas.boletos-requisicao';
    public const SCOPE_PIX = 'cob.read cob.write pix.read pix.write';
    public const SCOPE_EXTRATO = 'extrato-info';

    public static function verificaTokenValido(Portador $portador, string $scope = self::SCOPE_COBRANCA): string
    {
        // o escopo faz parte da chave, senao o token de uma API seria entregue a outra
        $cacheKey = "bb_token_{$portador->codportador}_" . substr(sha1($scope), 0, 8);
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }
        $token = static::token($portador, $scope);
        $ttl = intval($token['expires_in'] * 0.5);
        Cache::put($cacheKey, $token['access_token'], $ttl);
        return $token['access_token'];
    }

    private static function token(Portador $portador, string $scope): array
    {
        $curl = curl_init();
        $url = config('services.bb.url_oauth') . '/token';
        $authorization = base64_encode("{$portador->bbclientid}:{$portador->bbclientsecret}");
        // o BB documenta os escopos separados por um e somente um espaco
        $body = 'grant_type=client_credentials&scope=' . rawurlencode($scope);
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 15,
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
            throw new Exception(static::mensagemErro($httpcode, $response, $scope), $httpcode);
        }
        $ret = json_decode($response, true);
        if (!isset($ret['access_token'], $ret['expires_in'])) {
            throw new Exception("Resposta inesperada ao autenticar na API do BB: {$response}");
        }
        return $ret;
    }

    /**
     * Traduz os erros de autenticacao do BB, que chegam como JSON cru ate a tela do usuario
     */
    private static function mensagemErro(int $httpcode, string $response, string $scope): string
    {
        $erro = json_decode($response, true)['error'] ?? null;
        switch ($erro) {
            case 'invalid_scope':
                return "A aplicação do Banco do Brasil não possui autorização para os escopos "
                    . "'{$scope}'. Verifique as APIs vinculadas à aplicação no Portal do "
                    . "Desenvolvedor do BB.";
            case 'invalid_client':
                return 'Credenciais do Banco do Brasil inválidas. Confira o Client Id e o '
                    . 'Client Secret cadastrados no Portador.';
        }
        return "Erro {$httpcode} - {$response} ao autenticar na API do BB!";
    }
}
