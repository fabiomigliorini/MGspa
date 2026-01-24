<?php

namespace Mg\Cidade\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CidadeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codcidade' => $this->codcidade,
            'codestado' => $this->codestado,
            'cidade' => $this->cidade,
            'sigla' => $this->sigla,
            'codigooficial' => $this->codigooficial,

            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }
}
