<?php

namespace Mg\Colaborador;

use Illuminate\Http\Resources\Json\JsonResource;

class CargoResource extends JsonResource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);
        return $ret;
    }
}
