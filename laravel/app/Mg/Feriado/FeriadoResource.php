<?php

namespace Mg\Feriado;

use Illuminate\Http\Resources\Json\JsonResource;

class FeriadoResource extends JsonResource
{
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
