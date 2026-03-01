<?php

namespace Mg\Rh;

use Illuminate\Http\Resources\Json\JsonResource;

class AcertoEfetivadoResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'status'      => $this->resource['status'],
            'liquidacoes' => $this->resource['liquidacoes'],
            'financeiro'  => $this->resource['financeiro'],
            'folha'       => $this->resource['folha'],
        ];
    }
}
