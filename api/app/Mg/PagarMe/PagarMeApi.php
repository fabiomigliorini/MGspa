<?php


namespace Mg\PagarMe;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Description of PagarMeApi
 *
 * @author Fabio Migliorini
 * @property boolean $debug Modo debug - mostra log erros
 */
class PagarMeApi {

    protected $url;
    protected $secretKey;

    public $responseText;
    public $response;
    public $header;
    public $status;

    public $error;
    public $errno;


    public function __construct($secret_key)
    {
        // Traz variaves de ambiente
        $this->url = env('PAGAR_ME_API_BASE_URL');
        $this->secretKey = $secret_key;
    }

    public function get($url, $data = [])
    {
        return $this->curl('GET', $url, $data);
    }

    public function post($url, $data = [])
    {
        return $this->curl('POST', $url, $data);
    }

    public function put($url, $data = [])
    {
        return $this->curl('PUT', $url, $data);
    }

    public function patch($url, $data = [])
    {
        return $this->curl('PATCH', $url, $data);
    }

    public function delete($url, $data = [])
    {
        return $this->curl('DELETE', $url, $data);
    }

    public function curl($request, $url, $data = [])
    {
        // Autorizacao
        $http_header = [
            'Content-Type: application/json',
            'Authorization: Basic '. base64_encode("{$this->secretKey}:") // <---
        ];

        // codifica como json os dados

        // Loga Execucao
        // Log::debug(class_basename($this) . " - $request - $url - " . ($data_as_json?"$data_string - ":'') . json_encode($http_header));

        // Monta Chamada CURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request);
        $data_string = null;
        if ($request == 'GET') {
            $url = $url . '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            $data_string = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            $http_header[] = 'Content-Length: ' . strlen($data_string);
        }
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        // inicializa variaveis do resultado da execucao
        $this->error = null;
        $this->errno = null;
        $this->status = null;
        $this->headers = null;
        $this->response = null;

        // Executa
        $this->responseText = curl_exec($ch);

        // se erro pega codigo e descricao de erro
        if ($this->responseText === false) {
            $this->error = curl_error($ch);
            $this->errno = curl_errno($ch);
        } else {
            $this->status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }

        // monta objeto do header
        $headerSize = curl_getinfo($ch , CURLINFO_HEADER_SIZE);
        $headerStr = substr($this->responseText , 0 , $headerSize);
        $this->headers = $this->parseHeader($headerStr);

        // monta objeto do response
        $bodyStr = substr($this->responseText, $headerSize);
        $this->response = json_decode($bodyStr);

        curl_close($ch);

        // Loga Reotrno
        // Log::debug(class_basename($this) . " - $this->status - $this->responseText");

        // Se nao retornou 200 retorna erro
        if (!in_array($this->status, [200, 201])) {
            return false;
        }

        // retorna
        return true;

    }

    function parseHeader($response)
    {
        if (!preg_match_all('/([A-Za-z\-]{1,})\:(.*)\\r/', $response, $matches)
                || !isset($matches[1], $matches[2])){
            return false;
        }
        $headers = [];
        foreach ($matches[1] as $index => $key){
            $headers[trim($key)] = trim($matches[2][$index]);
        }
        return $headers;
    }

    public function postOrders (
        string $name,
        string $email,
        float $amount,
        string $description,
        int $quantity,
        bool $closed,
        bool $visible,
        string $serial_number,
        string $type,
        int $installments,
        string $installment_type
    ) {

        $data = [
            'customer' => [
                 'name' => $name,
                 'email' => $email
            ],
            'items' => [[
                'amount' => (int) round($amount * 100, 0),
                'description' => $description,
                'quantity' => $quantity
            ]],
            'closed'=> $closed,
            'poi_payment_settings' => [
                 'visible' => $visible,
                 'devices_serial_number' => [
                      $serial_number
                 ],
                 'payment_setup' => [
                      'type' => $type,
                      'installments' => $installments,
                      'installment_type' => $installment_type
                 ],
            ]
        ];

        // monta URL
        $url = $this->url . "v5/orders";

        // aborta caso erro no put
        if (!$this->post($url, $data)) {
            if (!empty($this->errno)) {
                throw new \Exception("Error {$this->errno}: {$this->error}");
            }
            if (isset($this->response->message)) {
                throw new \Exception("Status {$this->status}: {$this->response->message}");
            }
            throw new \Exception("Status {$this->status}: {$this->responseText}");
        }

        return $this->status == 201;

    }

    public function patchOrdersClosed (
        string $id,
        string $status
    ) {

        $data = [
            'status' => $status
        ];

        // monta URL
        $url = $this->url . "v5/orders/{$id}/closed";

        // aborta caso erro no put
        if (!$this->patch($url, $data)) {
            if (!empty($this->errno)) {
                throw new \Exception("Error {$this->errno}: {$this->error}");
            }                
            if (isset($this->response->message)) {
                throw new \Exception("Status {$this->status}: {$this->response->message}");
            }
            throw new \Exception("Status {$this->status}: {$this->responseText}");
        }

        return $this->status == 200;

    }

    public function getOrdersId (string $id)
    {

        // monta URL
        $url = $this->url . "v5/orders/{$id}";

        // aborta caso erro no put
        if (!$this->get($url)) {
            if (!empty($this->errno)) {
                throw new \Exception("Error {$this->errno}: {$this->error}");
            }                
            if (isset($this->response->message)) {
                throw new \Exception("Status {$this->status}: {$this->response->message}");
            }
            throw new \Exception("Status {$this->status}: {$this->responseText}");
        }

        return $this->status == 200;

    }

    public function getOrders ($status = 'pending')
    {
        // filtro pelo status
        $data = [
            'status' => $status,
        ];

        // monta URL
        $url = $this->url . "v5/orders";

        // aborta caso erro no put
        if (!$this->get($url, $data)) {
            if (!empty($this->errno)) {
                throw new \Exception("Error {$this->errno}: {$this->error}");
            }                
            if (isset($this->response->message)) {
                throw new \Exception("Status {$this->status}: {$this->response->message}");
            }
            throw new \Exception("Status {$this->status}: {$this->responseText}");
        }

        return $this->status == 200;

    }

}
