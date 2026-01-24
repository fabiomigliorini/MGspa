<?php

namespace Mg\Cidade\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaisResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codpais' => $this->codpais,
            'pais' => $this->pais,
            'sigla' => $this->sigla,
            'codigooficial' => $this->codigooficial,
            'inativo' => $this->inativo,

            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }
}
