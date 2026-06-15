<?php

namespace Mg\Rh;

use Illuminate\Http\Resources\Json\JsonResource;

class ColaboradorRubricaResource extends JsonResource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;
        return $ret;
    }
}
