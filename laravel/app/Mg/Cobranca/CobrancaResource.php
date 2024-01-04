<?php

namespace Mg\Cobranca;

use Illuminate\Http\Resources\Json\JsonResource;

class CobrancaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {   
        $ret = parent::toArray($request);
        $ret['usuariocriacao'] = @$this->UsuarioCriacao->usuario;
        return $ret;
    }
}
