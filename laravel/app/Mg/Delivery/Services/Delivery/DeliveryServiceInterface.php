<?php

namespace Mg\Delivery\Services\Delivery;

interface DeliveryServiceInterface
{
    public function requestDelivery(SalesOrder $order): string;
}
