<?php

namespace Mg\Cobranca;

use Illuminate\Http\Resources\Json\JsonResource;

class CobrancaResource extends JsonResource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);
        $ret['usuariocriacao'] = @$this->UsuarioCriacao->usuario;
        return $ret;
    }
}
