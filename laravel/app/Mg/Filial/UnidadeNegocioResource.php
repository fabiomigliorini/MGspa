<?php

namespace Mg\Filial;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class UnidadeNegocioResource extends Resource
{
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
