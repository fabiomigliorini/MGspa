<?php

namespace Mg\Colaborador;

use Illuminate\Http\Resources\Json\JsonResource;

class CargoResource extends JsonResource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);
        if ($this->resource instanceof Cargo) {
            $ret['usuariocriacao'] = $this->usuariocriacao;
            $ret['usuarioalteracao'] = $this->usuarioalteracao;
        }
        return $ret;
    }
}
