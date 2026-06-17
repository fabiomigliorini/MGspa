<?php

namespace Mg\Fazenda;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class FazendaResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // remove relações em snake_case que o parent injeta (Laravel serializa
        // relação carregada em snake por padrão) — reexpostas em PascalCase abaixo.
        unset(
            $ret['pessoa'],
            $ret['talhao_s'],
        );

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        // relações em PascalCase (whenLoaded — chaves ausentes somem do JSON)
        $ret['Pessoa'] = $this->whenLoaded('Pessoa');

        if ($this->relationLoaded('TalhaoS')) {
            $ret['TalhaoS'] = TalhaoResource::collection($this->TalhaoS);
        }

        return $ret;
    }
}
