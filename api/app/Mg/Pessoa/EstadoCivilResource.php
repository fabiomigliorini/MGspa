<?php

namespace Mg\Pessoa;

use Illuminate\Http\Resources\Json\JsonResource;

class EstadoCivilResource extends JsonResource
{
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
