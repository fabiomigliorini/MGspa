<?php
namespace Mg\Delivery\Services\Delivery;

class SalesOrder
{
    public function __construct(
        public Customer $customer,
        public Address $address,
        public string $payment_method,
        public ?string $observations = null,
    ) {
    }
}
