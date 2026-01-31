<?php

namespace Mg\Pessoa;

use Illuminate\Http\Resources\Json\JsonResource;

class GrauInstrucaoResource extends JsonResource
{
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
