<?php

namespace Mg\Feriado;

use Illuminate\Http\Resources\Json\JsonResource;

class FeriadoGerarAnoResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request)
    {
        return [
            'ano' => $this->resource['ano'],
            'duplicados' => $this->resource['duplicados'],
            'moveis_atualizados' => $this->resource['moveis_atualizados'],
            'moveis' => $this->resource['moveis'],
            'preexistentes' => $this->resource['preexistentes'],
        ];
    }
}
