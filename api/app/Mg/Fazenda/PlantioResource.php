<?php

namespace Mg\Fazenda;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class PlantioResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // remove relações em snake_case que o parent injeta (Laravel serializa
        // relação carregada em snake por padrão) — reexpostas em PascalCase abaixo.
        unset(
            $ret['safra'],
            $ret['fazenda'],
            $ret['talhao'],
            $ret['variedade'],
            $ret['carga_colheita_s'],
        );

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        // relações em PascalCase (whenLoaded — chaves ausentes somem do JSON)
        $ret['Safra'] = $this->whenLoaded('Safra');
        $ret['Fazenda'] = $this->whenLoaded('Fazenda');
        $ret['Talhao'] = $this->whenLoaded('Talhao');
        $ret['Variedade'] = $this->whenLoaded('Variedade');

        if ($this->relationLoaded('CargaColheitaS')) {
            $ret['CargaColheitaS'] = $this->CargaColheitaS;
        }

        return $ret;
    }
}
