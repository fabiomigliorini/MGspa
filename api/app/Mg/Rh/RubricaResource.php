<?php

namespace Mg\Rh;

use Illuminate\Http\Resources\Json\JsonResource;

class RubricaResource extends JsonResource
{
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
