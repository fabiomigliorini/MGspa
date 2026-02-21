<?php

namespace Mg\Rh;

use Illuminate\Http\Resources\Json\JsonResource;

class PeriodoColaboradorSetorResource extends JsonResource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);
        $ret['usuariocriacao'] = @$this->UsuarioCriacao->usuario;
        $ret['usuarioalteracao'] = @$this->UsuarioAlteracao->usuario;
        return $ret;
    }
}
