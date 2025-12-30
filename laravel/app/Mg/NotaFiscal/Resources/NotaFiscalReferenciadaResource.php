<?php

namespace Mg\NotaFiscal\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotaFiscalReferenciadaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codnotafiscalreferenciada' => $this->codnotafiscalreferenciada,
            'codnotafiscal' => $this->codnotafiscal,

            // Dados da Nota Referenciada
            'nfechave' => $this->nfechave,

            // Timestamps
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }
}
