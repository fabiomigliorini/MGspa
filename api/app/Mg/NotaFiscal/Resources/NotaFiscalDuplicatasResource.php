<?php

namespace Mg\NotaFiscal\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotaFiscalDuplicatasResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codnotafiscalduplicatas' => $this->codnotafiscalduplicatas,
            'codnotafiscal' => $this->codnotafiscal,

            // Dados da Duplicata
            'fatura' => $this->fatura,
            'valor' => $this->valor,
            'vencimento' => $this->vencimento,

            // Timestamps
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }
}
