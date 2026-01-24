<?php

namespace Mg\Tributacao\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TributacaoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codtributacao' => $this->codtributacao,
            'tributacao' => $this->tributacao,
            'aliquotaicmsecf' => $this->aliquotaicmsecf !== null ? (float) $this->aliquotaicmsecf : null,
        ];
    }
}
