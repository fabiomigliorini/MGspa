<?php
namespace Mg\Delivery\Services\Delivery;

class Address
{
    public function __construct(
        public string $street,
        public string $number,
        public string $neighborhood,
        public string $city,
        public string $state,
        public ?string $additional_info = null
    ) {
    }
}