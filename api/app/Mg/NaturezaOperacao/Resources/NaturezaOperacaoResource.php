<?php

namespace Mg\NaturezaOperacao\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Mg\NaturezaOperacao\NaturezaOperacao;

class NaturezaOperacaoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codnaturezaoperacao' => $this->codnaturezaoperacao,
            'naturezaoperacao' => $this->naturezaoperacao,
            'codoperacao' => $this->codoperacao,
            'operacao' => $this->relationLoaded('Operacao') ? $this->Operacao?->only(['codoperacao', 'operacao']) : null,
            'emitida' => $this->emitida,
            'finnfe' => $this->finnfe,
            'finnfeDescricao' => NaturezaOperacao::FINNFE_DESCRICOES[$this->finnfe] ?? null,
        ];
    }
}
