<?php

namespace Mg\Maquineta;

use Illuminate\Http\Resources\Json\JsonResource;

class MaquinetaResource extends JsonResource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);
        unset($ret['filial'], $ret['pessoa'], $ret['saurus_pin_pad']);
        $ret['filial'] = $this->Filial->filial ?? null;
        $ret['adquirente'] = $this->Pessoa->fantasia ?? null;
        $ret['codsauruspdv'] = $this->SaurusPinPad->codsauruspdv ?? null;
        return $ret;
    }
}
