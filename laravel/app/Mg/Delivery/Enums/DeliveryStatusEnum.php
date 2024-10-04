<?php

namespace Mg\Delivery\Enums;

enum DeliveryStatusEnum: int
{
    case PENDING = 1;
    case IN_TRANSIT = 2;
    case DELIVERED = 3;
    case CANCELLED = 4;
}