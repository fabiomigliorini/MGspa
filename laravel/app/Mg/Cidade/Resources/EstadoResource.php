<?php

namespace Mg\Cidade\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EstadoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codestado' => $this->codestado,
            'codpais' => $this->codpais,
            'estado' => $this->estado,
            'sigla' => $this->sigla,
            'codigooficial' => $this->codigooficial,

            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }
}
