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

    public $type_description = [
        1 => 'debit',
        2 => 'credit',
        3 => 'voucher',
        4 => 'prepaid',
    ];

    /**
     * Construtor
     */
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);

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
            throw new \Exception($this->response, 1);
        }

        return $this->status == 201;


        //

    }

    // public function postProdutos (
    //     $nome,
    //     $preco_tabela,
    //     $preco_minimo,
    //     $codigo,
    //     $comissao = null,
    //     $ipi = null,
    //     $tipo_ipi = 'P',
    //     $st = null,
    //     $moeda = 0,
    //     $unidade,
    //     $saldo_estoque,
    //     $observacoes,
    //     $grade_cores = null,
    //     $grade_tamanhos = null,
    //     $excluido = false,
    //     $ativo = true,
    //     // $categoria_id = null,
    //     $codigo_ncm,
    //     $multiplo = null,
    //     $peso_bruto = null,
    //     $largura = null,
    //     $altura = null,
    //     $comprimento = null,
    //     $peso_dimensoes_unitario = true,
    //     $exibir_no_b2b = true)
    // {
    //     // monta Array com dados
    //     $data = (object) [
    //         'nome' => $nome,
    //         'preco_tabela' => $preco_tabela,
    //         'preco_minimo' => $preco_minimo,
    //         'codigo' => $codigo,
    //         'comissao' => $comissao,
    //         'ipi' => $ipi,
    //         'tipo_ipi' => $tipo_ipi,
    //         'st' => $st,
    //         'moeda' => $moeda,
    //         'unidade' => $unidade,
    //         'saldo_estoque' => $saldo_estoque,
    //         'observacoes' => $observacoes,
    //         'grade_cores' => $grade_cores,
    //         'grade_tamanhos' => $grade_tamanhos,
    //         'excluido' => $excluido,
    //         'ativo' => $ativo,
    //         // 'categoria_id' => $categoria_id,
    //         'codigo_ncm' => $codigo_ncm,
    //         'multiplo' => $multiplo,
    //         'peso_bruto' => $peso_bruto,
    //         'largura' => $largura,
    //         'altura' => $altura,
    //         'comprimento' => $comprimento,
    //         'peso_dimensoes_unitario' => $peso_dimensoes_unitario,
    //         'exibir_no_b2b' => $exibir_no_b2b
    //     ];
    //
    //     // monta URL
    //     $url = $this->url . "api/v1/produtos";
    //
    //     // aborta caso erro no put
    //     if (!$this->post($url, $data)) {
    //         throw new \Exception($this->response, 1);
    //     }
    //
    //     return $this->status == 201;
    // }
    //
    // public function putProdutos (
    //     $id,
    //     $nome,
    //     $preco_tabela,
    //     $preco_minimo,
    //     $codigo,
    //     $comissao = null,
    //     $ipi = null,
    //     $tipo_ipi = 'P',
    //     $st = null,
    //     $moeda = 0,
    //     $unidade,
    //     $saldo_estoque,
    //     $observacoes,
    //     $grade_cores = null,
    //     $grade_tamanhos = null,
    //     $excluido = false,
    //     $ativo = true,
    //     // $categoria_id = null,
    //     $codigo_ncm,
    //     $multiplo = null,
    //     $peso_bruto = null,
    //     $largura = null,
    //     $altura = null,
    //     $comprimento = null,
    //     $peso_dimensoes_unitario = true,
    //     $exibir_no_b2b = true)
    // {
    //     // monta Array com dados
    //     $data = (object) [
    //         'id' => $id,
    //         'nome' => $nome,
    //         'preco_tabela' => $preco_tabela,
    //         'preco_minimo' => $preco_minimo,
    //         'codigo' => $codigo,
    //         'comissao' => $comissao,
    //         'ipi' => $ipi,
    //         'tipo_ipi' => $tipo_ipi,
    //         'st' => $st,
    //         'moeda' => $moeda,
    //         'unidade' => $unidade,
    //         'saldo_estoque' => $saldo_estoque,
    //         'observacoes' => $observacoes,
    //         'grade_cores' => $grade_cores,
    //         'grade_tamanhos' => $grade_tamanhos,
    //         'excluido' => $excluido,
    //         'ativo' => $ativo,
    //         // 'categoria_id' => $categoria_id,
    //         'codigo_ncm' => $codigo_ncm,
    //         'multiplo' => $multiplo,
    //         'peso_bruto' => $peso_bruto,
    //         'largura' => $largura,
    //         'altura' => $altura,
    //         'comprimento' => $comprimento,
    //         'peso_dimensoes_unitario' => $peso_dimensoes_unitario,
    //         'exibir_no_b2b' => $exibir_no_b2b
    //     ];
    //
    //     // monta URL
    //     $url = $this->url . "api/v1/produtos/{$id}";
    //
    //     // aborta caso erro no put
    //     if (!$this->put($url, $data)) {
    //         throw new \Exception($this->response, 1);
    //     }
    //
    //     return $this->status == 201;
    // }
    //
    // public function getProdutos (Carbon $alterado_apos)
    // {
    //
    //     $data = [];
    //     if (!empty($alterado_apos)) {
    //         $alt = clone $alterado_apos;
    //         $alt->setTimezone('America/Sao_Paulo');
    //         $data ['alterado_apos'] = $alt->format('Y-m-d H:i:s');
    //     }
    //
    //     // monta URL
    //     $url = $this->url . "api/v1/produtos";
    //
    //     // aborta caso erro no put
    //     if (!$this->get($url, $data)) {
    //         throw new \Exception($this->response, 1);
    //     }
    //
    //     if ($this->status != 200) {
    //         return false;
    //     }
    //
    //     return $this->responseObject;
    // }
    //
    // public function postImagensProduto (
    //     $produto_id,
    //     $ordem,
    //     $imagem_base64)
    // {
    //     // monta Array com dados
    //     $data = (object) [
    //         'produto_id' => $produto_id,
    //         'ordem' => $ordem,
    //         'imagem_base64' => $imagem_base64,
    //     ];
    //
    //     // monta URL
    //     $url = $this->url . "api/v1/imagens_produto";
    //
    //     // aborta caso erro no put
    //     if (!$this->post($url, $data)) {
    //         throw new \Exception($this->response, 1);
    //     }
    //
    //     return $this->status == 201;
    // }
    //
    // public function getPedidos (Carbon $alterado_apos)
    // {
    //
    //     $data = [];
    //     if (!empty($alterado_apos)) {
    //         $alt = clone $alterado_apos;
    //         $alt->setTimezone('America/Sao_Paulo');
    //         $data ['alterado_apos'] = $alt->format('Y-m-d H:i:s');
    //     }
    //
    //     // monta URL
    //     $url = $this->url . "api/v1/pedidos";
    //
    //     // aborta caso erro no put
    //     if (!$this->get($url, $data)) {
    //         throw new \Exception($this->response, 1);
    //     }
    //
    //     if ($this->status != 200) {
    //         return false;
    //     }
    //
    //     return $this->responseObject;
    // }
    //
    // public function postFaturamento (
    //     $pedido_id,
    //     $valor_faturado,
    //     Carbon $data_faturamento,
    //     $numero_nf,
    //     $informacoes_adicionais)
    // {
    //     // monta Array com dados
    //     $data = (object) [
    //         "pedido_id" => $pedido_id,
    //         "valor_faturado" => $valor_faturado,
    //         "data_faturamento" => $data_faturamento->format('Y-m-d'),
    //         "numero_nf" => $numero_nf,
    //         "informacoes_adicionais" => $informacoes_adicionais,
    //     ];
    //
    //     // monta URL
    //     $url = $this->url . "api/v1/faturamento";
    //
    //     // aborta caso erro no put
    //     if (!$this->post($url, $data)) {
    //         throw new \Exception($this->response, 1);
    //     }
    //
    //     return $this->status == 201;
    // }
    //
    // public function putFaturamento (
    //     $faturamento_id,
    //     $pedido_id,
    //     $valor_faturado,
    //     Carbon $data_faturamento,
    //     $numero_nf,
    //     $informacoes_adicionais)
    // {
    //     // monta Array com dados
    //     $data = (object) [
    //         "pedido_id" => $pedido_id,
    //         "valor_faturado" => $valor_faturado,
    //         "data_faturamento" => $data_faturamento->format('Y-m-d'),
    //         "numero_nf" => $numero_nf,
    //         "informacoes_adicionais" => $informacoes_adicionais,
    //     ];
    //
    //     // monta URL
    //     $url = $this->url . "api/v1/faturamento/{$faturamento_id}";
    //
    //     // aborta caso erro no put
    //     if (!$this->put($url, $data)) {
    //         throw new \Exception($this->response, 1);
    //     }
    //
    //     return $this->status == 201;
    // }
    //
    // public function getClientes (Carbon $alterado_apos)
    // {
    //
    //     $data = [];
    //     if (!empty($alterado_apos)) {
    //         $alt = clone $alterado_apos;
    //         $alt->setTimezone('America/Sao_Paulo');
    //         $data ['alterado_apos'] = $alt->format('Y-m-d H:i:s');
    //     }
    //
    //     // monta URL
    //     $url = $this->url . "api/v1/clientes";
    //
    //     // aborta caso erro no put
    //     if (!$this->get($url, $data)) {
    //         throw new \Exception($this->response, 1);
    //     }
    //
    //     if ($this->status != 200) {
    //         return false;
    //     }
    //
    //     return $this->responseObject;
    // }
    //
    // public function getCliente ($cliente_id)
    // {
    //
    //     // monta URL
    //     $url = $this->url . "api/v1/clientes/{$cliente_id}";
    //
    //     // aborta caso erro no put
    //     if (!$this->get($url)) {
    //         throw new \Exception($this->response, 1);
    //     }
    //
    //     if ($this->status != 200) {
    //         return false;
    //     }
    //
    //     return $this->responseObject;
    // }


}
