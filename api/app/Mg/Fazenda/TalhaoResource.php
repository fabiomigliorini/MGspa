<?php

namespace Mg\Fazenda;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class TalhaoResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // remove relações em snake_case que o parent injeta (Laravel serializa
        // relação carregada em snake por padrão) — reexpostas em PascalCase abaixo.
        unset(
            $ret['fazenda'],
            $ret['plantio_s'],
        );

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        // relações em PascalCase (whenLoaded — chaves ausentes somem do JSON)
        $ret['Fazenda'] = $this->whenLoaded('Fazenda');

        if ($this->relationLoaded('PlantioS')) {
            $ret['PlantioS'] = PlantioResource::collection($this->PlantioS);
        }

        return $ret;
    }
}
