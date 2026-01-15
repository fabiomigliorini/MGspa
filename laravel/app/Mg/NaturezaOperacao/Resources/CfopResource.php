<?php

namespace Mg\NaturezaOperacao\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CfopResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codcfop' => $this->codcfop,
            'descricao' => $this->cfop,

            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }
}
