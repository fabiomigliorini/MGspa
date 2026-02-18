<?php

namespace Mg\Filial;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class UnidadeNegocioResource extends Resource
{
    public function toArray($request)
    {
        $data = parent::toArray($request);
        $data['Filial'] = $this->codfilial ? $this->Filial->only(['codfilial', 'filial']) : null;
        return $data;
    }
}
