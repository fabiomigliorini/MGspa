<?php

namespace Mg\Delivery\Controllers;

use App\Http\Controllers\Controller;
use Mg\Delivery\Enums\DeliveryStatusEnum;
use Mg\Delivery\Models\Delivery;
use Mg\Delivery\Services\Delivery\Address;
use Mg\Delivery\Services\Delivery\Customer;
use Mg\Delivery\Services\Delivery\DeliveryServiceInterface;
use Mg\Delivery\Services\Delivery\SalesOrder;
use Mg\Delivery\Validators\DeliveryRequestValidator;

class DeliveryController extends Controller
{
    /**
     * @return \Illuminate\Http\Response
     */
    public function request(
        DeliveryRequestValidator $request,
        DeliveryServiceInterface $deliveryService
    ) {

        $customer = new Customer(
            $request->name,
            $request->phone
        );

        $address = new Address(
            $request->street,
            $request->number,
            $request->neighborhood,
            $request->city,
            $request->state,
            $request->additional_info,
        );

        $salesOrder = new SalesOrder(
            $customer,
            $address,
            $request->payment_method,
            $request->observations
        );

        $deliveryId = $deliveryService->request($salesOrder);

        $deliveryRecord = Delivery::create([
            'deal_id' => $request->deal_id,
            'ref' => $deliveryId,
            'status' => DeliveryStatusEnum::PENDING->value,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'street' => $address->street,
            'number' => $address->number,
            'neighborhood' => $address->neighborhood,
            'city' => $address->city,
            'state' => $address->state,
            'additional_info' => $address->additional_info,
            'payment_method' => $salesOrder->payment_method,
            'observations' => $salesOrder->observations,
        ]);

        return response()->json($deliveryRecord);
    }

    public function cancel(
        $id,
        DeliveryServiceInterface $deliveryService
    ) {
        $delivery = Delivery::find($id);

        if (!$delivery) {
            throw new \Exception('Entrega não encontrada!');
        }

        $deliveryService->cancel($delivery->ref);

        $delivery->status = DeliveryStatusEnum::CANCELLED->value;
        $delivery->save();

        return response(null);
    }
}
