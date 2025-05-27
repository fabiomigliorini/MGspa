<?php

namespace App\Mg\Portador;

use Illuminate\Http\Resources\Json\JsonResource;

class SomatorioSaldoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'filiais'       => $this->resource['filiais'],
            'totalPorBanco' => $this->resource['totalPorBanco'],
            'totalGeral'    => $this->resource['totalGeral'],
        ];
    }
}