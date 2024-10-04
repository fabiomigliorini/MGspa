<?php

namespace Mg\Delivery\Services\Delivery\Implementations;

use Illuminate\Support\Facades\Http;
use Mg\Delivery\Services\Delivery\DeliveryServiceInterface;
use Mg\Delivery\Services\Delivery\SalesOrder;


// TODO: Criar botão para consultar o estado da entrega do pedido.
// TODO: Criar cronnn a cada 10 min para atualizar o estado da entrega.
class MeNuvemDeliveryService implements DeliveryServiceInterface
{
    const API_BASE = 'https://novopainel.menuvem.com.br/app-api';

    public function request(SalesOrder $order): string
    {
        $orderCookie = $this->createOrder();

        $updatedOrder = $this->updateOrder($orderCookie, [
            "nomecompleto" => $order->customer->name,
            "numerocelular" => $order->customer->phone,
            "cidade" => $order->address->city,
            "estado" => $order->address->state,
            "endereco" => $order->address->street,
            "numero" => $order->address->number,
            "bairro" => [
                "label" => $order->address->neighborhood,
            ],
            "complemento" => $order->address->additional_info,
            "metodopagamento_descrito" => $order->payment_method,
            "observacoes" => $order->observations,
            "codigo_pedido_status" => 3,
            "tipopedido" => "e",
            "subtotal" => 57.82,
            "total" => 57.82
        ]);

        return $orderCookie;
    }

    public function cancel(string $orderCookie): void
    {
        $order = $this->getOrder($orderCookie);

        $orderId = $order['pedido']['codigo_pedido'];

        $updatedOrder = Http::withHeaders([
            'Authorization' => $this->getAuthToken()
        ])
            ->post(self::API_BASE . "/pedidos/salvar/$orderId", [
                "acao" => 4,
                "codigo_motivo" => null,
                "observacao_motivo" => "",
            ])
            ->json();

        if (!$updatedOrder['status']) {
            throw new \Exception('Erro ao cancelar pedido!');
        }
    }

    private function getOrder(string $orderCookie): array
    {
        $order = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getAuthToken(),
        ])
            ->get(self::API_BASE . "/gerenciador/detalhes/$orderCookie")
            ->json();

        return $order;
    }

    private function createOrder(): string
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getAuthToken(),
        ])
            ->post(self::API_BASE . '/gerenciador/novo-pedido')
            ->throw()
            ->json('cookie');
    }

    private function updateOrder(string $orderCookie, array $data): array
    {
        return Http::withHeaders([
            'Authorization' => $this->getAuthToken(),
        ])
            ->post(self::API_BASE . "/gerenciador/$orderCookie/salvar", $data)
            ->json();
    }

    /**
     * Retorna o token de autentica o para a API.
     *
     * Primeiramente, verifica se o token já está definido em uma variável de ambiente.
     * Se não, faz um request para a rota de autenticação da API e retorna o token.
     * O token é salvo em uma variável de ambiente e expira em 6 meses.
     *
     * @return string
     */
    private function getAuthToken(): string
    {
        $token = env('MENUVEM_TOKEN');

        // TODO: Verificar se o token não está expirado.
        if ($token) {
            return $token;
        }

        $user = env('MENUVEM_USER');
        $password = env('MENUVEM_PASSWORD');

        $body = Http::post(
            self::API_BASE . '/oauth',
            [
                'email' => $user,
                'password' => $password,
            ]
        )->throw()->json();


        $token = $body['data']['token'];

        $this->setEnvToken($token);

        return $token;
    }

    private function setEnvToken(string $token)
    {
        $path = base_path('.env');

        if (file_exists($path)) {
            $content = file_get_contents($path);

            $contentUpdated = preg_replace(
                "/^MENUVEM_TOKEN=.*/m",
                "MENUVEM_TOKEN={$token}",
                $content
            );

            file_put_contents($path, $contentUpdated);
        }
    }
}
