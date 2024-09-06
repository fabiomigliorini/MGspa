<?php
namespace Tests\Unit;

use Mg\Delivery\Services\Delivery\Address;
use Mg\Delivery\Services\Delivery\Customer;
use Mg\Delivery\Services\Delivery\Implementations\MeNuvemDeliveryService;
use Mg\Delivery\Services\Delivery\SalesOrder;
use Tests\TestCase;

class MeNuvemDeliveryServiceTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testRequestDelivery()
    {
        $data = (object) [
            'sales_id' => random_int(1, 100),
            'name' => 'Everton de Souza Andrade',
            'phone' => '(11) 99999-9999',
            'street' => 'Rua das Tamareiras',
            'number' => '1840',
            'neighborhood' => 'Jardim Botânico',
            'city' => 'Sinop',
            'state' => 'Mato Grosso',
            'additional_info' => 'Perto do posto.',
            'payment_method' => 'PIX',
            'observations' => '2 Pacote de Folha A4'
        ];

        $customer = new Customer(
            $data->name,
            $data->phone
        );

        $address = new Address(
            $data->street,
            $data->number,
            $data->neighborhood,
            $data->city,
            $data->state,
            $data->additional_info,
        );

        $salesOrder = new SalesOrder(
            $customer,
            $address,
            $data->payment_method,
            $data->observations
        );

        $deliveryService = new MeNuvemDeliveryService();

        $response = $deliveryService->requestDelivery($salesOrder);

        print_r("delivery.ref => $response");

        $this->assertIsString($response);
    }
}
