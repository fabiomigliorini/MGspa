<?php

namespace Mg\Tributacao\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TributoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'        => $this->codtributo,
            'codigo'    => $this->codigo,
            'descricao' => $this->descricao,
            'ente'      => $this->ente,
        ];
    }
}
