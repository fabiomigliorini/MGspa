<?php

namespace Mg\Safra;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class SafraResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // remove relações em snake_case que o parent injeta (Laravel serializa
        // relação carregada em snake por padrão) — reexpostas em PascalCase abaixo.
        unset(
            $ret['cultura'],
            $ret['plantio_s'],
        );

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        // relações em PascalCase (whenLoaded — chaves ausentes somem do JSON)
        $ret['Cultura'] = $this->whenLoaded('Cultura');

        if ($this->relationLoaded('PlantioS')) {
            $ret['PlantioS'] = $this->PlantioS;
        }

        return $ret;
    }
}
