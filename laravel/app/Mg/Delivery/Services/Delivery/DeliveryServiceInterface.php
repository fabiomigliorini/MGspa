<?php

namespace Mg\Delivery\Services\Delivery;

interface DeliveryServiceInterface
{
    /**
     * @param \Mg\Delivery\Services\Delivery\SalesOrder $order
     * @return string delivery id
     */
    public function request(SalesOrder $order): string;
    public function cancel(string $id): void;
}
