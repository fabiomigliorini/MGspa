<?php

namespace Mg\Grao;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class CargaPontoResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        unset(
            $ret['carga'],
            $ret['plantio'],
            $ret['unidade_armazenadora'],
            $ret['contrato'],
        );

        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        $ret['Plantio'] = $this->whenLoaded('Plantio');
        $ret['UnidadeArmazenadora'] = $this->whenLoaded('UnidadeArmazenadora');
        $ret['Contrato'] = $this->whenLoaded('Contrato');

        return $ret;
    }
}
