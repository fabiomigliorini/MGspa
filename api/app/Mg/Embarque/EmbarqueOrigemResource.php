<?php

namespace Mg\Embarque;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class EmbarqueOrigemResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // remove relações em snake_case que o parent injeta — reexpostas em PascalCase abaixo.
        unset(
            $ret['embarque'],
            $ret['plantio'],
        );

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        // relações em PascalCase (whenLoaded — chaves ausentes somem do JSON)
        $ret['Embarque'] = $this->whenLoaded('Embarque');
        $ret['Plantio'] = $this->whenLoaded('Plantio');

        return $ret;
    }
}
