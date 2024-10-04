<?php

namespace Mg\Delivery\UseCases;

use Mg\Delivery\Models\Delivery;
use Mg\Delivery\Services\Delivery\Address;
use Mg\Delivery\Services\Delivery\Customer;
use Mg\Delivery\Services\Delivery\Implementations\MeNuvemDeliveryService;
use Mg\Delivery\Services\Delivery\SalesOrder;

class DeliveryRequestUseCase
{
    public function __construct(
        private MeNuvemDeliveryService $deliveryService
    ) {
    }

    public function execute(object $data): bool
    {
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

        $deliveryRef = $this->deliveryService->requestDelivery($salesOrder);

        $delivery = Delivery::create([
            'sales_id' => $data->sales_id,
            'ref' => $deliveryRef,
            'status' => $data->status,
            'name' => $data->name,
            'phone' => $data->phone,
            'street' => $data->street,
            'number' => $data->number,
            'neighborhood' => $data->neighborhood,
            'city' => $data->city,
            'state' => $data->state,
            'additional_info' => $data->additional_info,
            'payment_method' => $data->payment_method,
            'observations' => $data->observations,
        ]);

        $delivery->save();

        return true;
    }
}
