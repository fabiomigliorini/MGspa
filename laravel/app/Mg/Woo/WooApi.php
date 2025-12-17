<?php

namespace Mg\Woo;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Description of MercosApi
 *
 * @author escmig98
 * @property boolean $debug Modo debug - mostra log erros
 */
class WooApi
{
    const PER_PAGE = 20;

    protected $debug = false;
    protected $url;
    protected $key;
    protected $secret;
    protected $languagePTBR;

    public $token;

    public $response;
    public $responseObject;
    public $status;

    public $error;
    public $errno;
    public $headers;

    /**
     * Construtor
     */
    public function __construct($debug = false, $url = null, $key = null, $secret = null, $language_ptbr = null)
    {
        // Traz variaves de ambiente
        $this->debug = $debug;
        $this->url = (!empty($url)) ? $url : env('WOO_API_URL');
        $this->key = (!empty($key)) ? $key : env('WOO_API_KEY');
        $this->secret = (!empty($secret)) ? $secret : env('WOO_API_SECRET');
        // $this->languagePTBR = (!empty($language_ptbr))?$language_ptbr:env('WOO_LANGUAGE_PTBR');
    }

    public function get($url, $data = [], $http_header = null, $data_as_json = true)
    {
        return $this->curl('GET', $url, $data, $http_header, $data_as_json);
    }

    public function post($url, $data = [], $http_header = null, $data_as_json = true)
    {
        return $this->curl('POST', $url, $data, $http_header, $data_as_json);
    }

    public function put($url, $data = [], $http_header = null, $data_as_json = true)
    {
        return $this->curl('PUT', $url, $data, $http_header, $data_as_json);
    }

    public function delete($url, $data = [], $http_header = null, $data_as_json = true)
    {
        return $this->curl('DELETE', $url, $data, $http_header, $data_as_json);
    }

    public function curl($request, $url, $data = [], $http_header = null, $data_as_json = true)
    {
        // Padrao de autorizacao como Bearer $this->token
        if (empty($http_header)) {
            $http_header = [
                'Content-Type: application/json',
            ];
        }

        // codifica como json os dados
        $data_string = null;
        if (!empty($data)) {
            $data_string = ($data_as_json) ? json_encode($data) : $data;
        } else {
            $url = $url . '?' . http_build_query($data);
            // curl_setopt($ch, CURLOPT_URL, $url);
        }

        // Loga Execucao
        if ($this->debug) {
            Log::debug(class_basename($this) . " - $request - $url - " . ($data_as_json ? "$data_string - " : '') . json_encode($http_header));
        }

        // Monta Chamada CURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request);
        if ($request == 'GET') {
            $url = $url . '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        }
        if (!empty($data_string)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            if ($data_as_json) {
                $http_header[] = 'Content-Length: ' . strlen($data_string);
            }
        }
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);
        curl_setopt($ch, CURLOPT_USERPWD, $this->key . ":" . $this->secret); // Autenticação Basic
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);

        // Executa
        do {
            $this->error = null;
            $this->errno = null;
            $this->status = null;
            $this->headers = null;
            $this->response = curl_exec($ch);
            if ($this->response === false) {
                $this->error = curl_error($ch);
                $this->errno = curl_errno($ch);
            } else {
                $this->status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            }
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

            $headerStr = substr($this->response, 0, $headerSize);
            $this->headers = $this->parseHeader($headerStr);

            // Loga Reotrno
            if ($this->debug) {
                Log::debug(class_basename($this) . " - $this->status - $this->response");
            }

            // Limpa responseObject
            $this->responseObject = null;
            $ret = true;

            // decodifica Json
            if (!empty($this->response)) {
                $bodyStr = substr($this->response, $headerSize);
                $this->responseObject = json_decode($bodyStr);
            }

            // Caso de Throttle espera os segundos que o
            if ($this->status == 429) {
                $segundos = isset($this->headers['retry-after']) ? $this->headers['retry-after'] : 5;
                sleep($segundos);
            }

            // Enquanto nao der Throttle
        } while ($this->status == 429);

        curl_close($ch);

        // Se nao retornou 200 retorna erro
        if (!in_array($this->status, [200, 201])) {
            $ret = false;
        }

        // retorna
        return $ret;
    }

    function parseHeader($response)
    {
        if (
            !preg_match_all('/([A-Za-z\-]{1,})\:(.*)\\r/', $response, $matches)
            || !isset($matches[1], $matches[2])
        ) {
            return false;
        }
        $headers = [];
        foreach ($matches[1] as $index => $key) {
            $headers[trim($key)] = trim($matches[2][$index]);
        }
        return $headers;
    }

    public function postProduto($produto)
    {
        // monta URL
        $url = $this->url . 'wc/v3/products';

        // aborta caso erro na requisicao
        if (!$this->post($url, $produto)) {
            $this->trataRetorno(null, null, null);
        }
        return $this->status == 201;
    }

    public function putProduto($id, $produto)
    {
        // monta URL
        $url = $this->url . 'wc/v3/products/' . $id;

        // aborta caso erro na requisicao
        if (!$this->put($url, $produto)) {
            $this->trataRetorno($id, null, null);
        }
        return $this->status == 201;
    }

    public function getProduto($id)
    {
        // monta URL
        $url = $this->url . 'wc/v3/products/' . $id;

        // aborta caso erro na requisicao
        if (!$this->get($url)) {
            throw new Exception(json_encode($this->responseObject), 1);
        }
        return $this->status == 201;
    }

    public function getAttributeTerms($attribute_id)
    {
        // monta URL
        $url = $this->url . "wc/v3/products/attributes/{$attribute_id}/terms";

        // aborta caso erro na requisicao
        if (!$this->get($url)) {
            throw new Exception(json_encode($this->responseObject), 1);
        }
        return $this->status == 201;
    }

    public function putProductVariations($product_id, $id, $variation)
    {
        // monta URL
        $url = $this->url . "wc/v3/products/{$product_id}/variations/{$id}";

        // aborta caso erro na requisicao
        if (!$this->post($url, $variation)) {
            $this->trataRetorno($product_id, $id, $variation);
        }
        return $this->status == 201;
    }

    public function trataRetorno($product_id, $id, $variation)
    {
        try {
            $code = $this->responseObject->code;
        } catch (\Throwable $th) {
        }
        switch ($code) {
            case 'woocommerce_rest_product_variation_invalid_id':
                throw new Exception("A combinação de ID produto {$product_id}, variação {$id} não foi encontrada no WOO.", 1);
                break;
            case 'woocommerce_rest_product_invalid_id':
                throw new Exception("O produto ID {$product_id} não foi encontrado no WOO.", 1);
                break;
            default:
                throw new Exception(json_encode($this->responseObject), 1);
                break;
        }
        return true;
    }

    public function postProductVariations($product_id, $variation)
    {
        // monta URL
        $url = $this->url . "wc/v3/products/{$product_id}/variations";

        // aborta caso erro na requisicao
        if (!$this->post($url, $variation)) {
            $this->trataRetorno($product_id, null, $variation);
        }
        return $this->status == 201;
    }

    public function getBrands()
    {
        // monta URL
        $url = $this->url . 'wc/v3/brand';

        // aborta caso erro na requisicao
        if (!$this->get($url)) {
            throw new Exception(json_encode($this->responseObject), 1);
        }
        dd($this->responseObject);
        return $this->status == 201;
    }

    //Pedidos
    public function getOrders($page = 1, $status = null)
    {
        // monta URL
        $url = $this->url . 'wc/v3/orders';

        $data = [
            'page' => $page,
            'per_page' => STATIC::PER_PAGE,
        ];

        if (!empty($status)) {
            $data['status'] = is_array($status) ? implode(',', $status) : $status;
        }

        // aborta caso erro na requisicao
        if (!$this->get($url, $data)) {
            throw new Exception(json_encode($this->responseObject), 1);
        }

        return $this->status == 201;
    }

    public function getOrder($id)
    {
        // monta URL
        $url = $this->url . 'wc/v3/orders/' . $id;

        // aborta caso erro na requisicao
        if (!$this->get($url)) {
            throw new Exception(json_encode($this->responseObject), 1);
        }

        return $this->status == 201;
    }    

    // altera status do pedido
    public function putOrder(int $id, array $data)
    {
        // monta URL
        $url = $this->url . 'wc/v3/orders/' . $id;

        // aborta caso erro na requisicao
        if (!$this->put($url, $data)) {
            throw new Exception(json_encode($this->responseObject), 1);
        }
        
        return $this->status == 201;
    }
}
