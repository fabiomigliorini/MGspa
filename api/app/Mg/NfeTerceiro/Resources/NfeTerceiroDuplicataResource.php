<?php

namespace Mg\NfeTerceiro\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NfeTerceiroDuplicataResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codnfeterceiroduplicata' => $this->codnfeterceiroduplicata,
            'codnfeterceiro' => $this->codnfeterceiro,
            'ndup' => $this->ndup,
            'dvenc' => $this->dvenc,
            'vdup' => $this->vdup,
            'codtitulo' => $this->codtitulo,
            'titulo' => $this->Titulo?->only(['codtitulo', 'titulo', 'vencimento', 'valor', 'saldo']),
        ];
    }
}
