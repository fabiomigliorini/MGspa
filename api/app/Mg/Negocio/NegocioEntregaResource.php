<?php

namespace Mg\Negocio;

use Illuminate\Http\Resources\Json\JsonResource;

class NegocioEntregaResource extends JsonResource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);
        unset($ret['usuario_criacao']);
        $ret['resultadodescricao'] = NegocioEntrega::RESULTADOS[$this->resultado] ?? null;
        $ret['motivodescricao'] = NegocioEntrega::MOTIVOS[$this->resultado][$this->motivo] ?? null;
        $ret['usuariocriacao'] = $this->UsuarioCriacao->usuario ?? null;
        return $ret;
    }
}
