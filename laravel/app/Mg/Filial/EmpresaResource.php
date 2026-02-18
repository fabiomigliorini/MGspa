<?php

namespace Mg\Filial;

use Illuminate\Http\Resources\Json\JsonResource;

class EmpresaResource extends JsonResource
{
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
