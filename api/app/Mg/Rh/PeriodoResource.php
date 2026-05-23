<?php

namespace Mg\Rh;

use Illuminate\Http\Resources\Json\JsonResource;

class PeriodoResource extends JsonResource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);
        $ret['usuariocriacao'] = @$this->UsuarioCriacao->usuario;
        $ret['usuarioalteracao'] = @$this->UsuarioAlteracao->usuario;
        $ret['diasuteiscalculados'] = PeriodoService::calcularDiasUteis(
            $this->periodoinicial,
            $this->periodofinal
        );
        return $ret;
    }
}
