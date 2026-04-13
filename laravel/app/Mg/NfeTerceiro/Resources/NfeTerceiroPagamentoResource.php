<?php

namespace Mg\NfeTerceiro\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NfeTerceiroPagamentoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codnfeterceiropagamento' => $this->codnfeterceiropagamento,
            'codnfeterceiro' => $this->codnfeterceiro,
            'indpag' => $this->indpag,
            'tpag' => $this->tpag,
            'vpag' => $this->vpag,
            'tband' => $this->tband,
            'caut' => $this->caut,
            'cnpj' => $this->cnpj,
        ];
    }
}
