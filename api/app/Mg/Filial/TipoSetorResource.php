<?php

namespace Mg\Filial;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class TipoSetorResource extends Resource
{
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
