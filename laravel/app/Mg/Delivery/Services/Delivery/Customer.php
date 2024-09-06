<?php
namespace Mg\Delivery\Services\Delivery;

class Customer
{
    public function __construct(public string $name, public string $phone)
    {
    }
}
