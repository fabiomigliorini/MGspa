<?php

namespace Mg\Delivery\Services\Delivery\Implementations;

use Illuminate\Support\Facades\Http;
use Mg\Delivery\Services\Delivery\DeliveryServiceInterface;
use Mg\Delivery\Services\Delivery\SalesOrder;

class MeNuvemDeliveryService implements DeliveryServiceInterface
{
    const API_BASE = 'https://novopainel.menuvem.com.br/app-api';

    public function requestDelivery(SalesOrder $order): string
    {
        $orderCookie = $this->createOrder();

        $this->updateOrder($orderCookie, [
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
        ]);

        return $orderCookie;
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

    private function updateOrder(string $orderCookie, array $data): bool
    {
        return Http::withHeaders([
            'Authorization' => $this->getAuthToken(),
        ])
            ->post(self::API_BASE . "/gerenciador/$orderCookie/salvar", $data)
            ->throw()
            ->ok();
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
