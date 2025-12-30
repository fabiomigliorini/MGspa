<?php

namespace Mg\NotaFiscal\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotaFiscalCartaCorrecaoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codnotafiscalcartacorrecao' => $this->codnotafiscalcartacorrecao,
            'codnotafiscal' => $this->codnotafiscal,

            // Dados da Carta de Correção
            'sequencia' => $this->sequencia,
            'texto' => $this->texto,
            'data' => $this->data,

            // Protocolo
            'lote' => $this->lote,
            'protocolo' => $this->protocolo,
            'protocolodata' => $this->protocolodata,

            // Timestamps
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }
}
