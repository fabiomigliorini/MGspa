<?php

namespace Mg\Delivery\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'deal_id',
        'ref',
        'status',
        'name',
        'phone',
        'street',
        'number',
        'neighborhood',
        'city',
        'state',
        'additional_info',
        'payment_method',
        'observations',
    ];
}
