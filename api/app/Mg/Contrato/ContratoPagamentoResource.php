<?php

namespace Mg\Contrato;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class ContratoPagamentoResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // remove relações em snake_case que o parent injeta
        unset($ret['contrato'], $ret['portador']);

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        // relações em PascalCase
        $ret['Portador'] = $this->whenLoaded('Portador');

        return $ret;
    }
}
