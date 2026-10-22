<?php

namespace Mg\Impressora;

use Illuminate\Http\Resources\Json\JsonResource;

class ImpressoraResource extends JsonResource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);
        $ret['tipodescricao'] = Impressora::TIPOS[$this->tipo] ?? null;
        return $ret;
    }
}
