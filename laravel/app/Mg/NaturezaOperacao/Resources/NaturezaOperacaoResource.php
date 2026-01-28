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
            'operacao' => $this->formatOperacao(),
            'emitida' => $this->emitida,
            'finnfe' => $this->finnfe,
            'finnfeDescricao' => NaturezaOperacao::FINNFE_DESCRICOES[$this->finnfe] ?? null,
        ];
    }

    private function formatOperacao(): ?array
    {
        if (!$this->relationLoaded('Operacao')) {
            return null;
        }

        return $this->Operacao?->only(['codoperacao', 'operacao']);
    }
}
