<?php

namespace Mg\Negocio;

use Illuminate\Http\Resources\Json\JsonResource;

class NegocioParcelaResource extends JsonResource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);
        $ret['condicaodescricao'] = NegocioParcelaService::CONDICOES[$this->condicao] ?? null;
        $ret['titulonumero'] = $this->Titulo->numero ?? null;
        $ret['titulosaldo'] = $this->Titulo->saldo ?? null;
        return $ret;
    }
}
