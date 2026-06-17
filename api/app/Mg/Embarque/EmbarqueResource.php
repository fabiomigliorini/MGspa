<?php

namespace Mg\Embarque;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class EmbarqueResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // remove relações em snake_case que o parent injeta (Laravel serializa
        // relação carregada em snake por padrão) — reexpostas em PascalCase abaixo.
        unset(
            $ret['embarque_contrato_s'],
            $ret['embarque_origem_s'],
        );

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        // relações em PascalCase (whenLoaded — chaves ausentes somem do JSON)
        if ($this->relationLoaded('EmbarqueContratoS')) {
            $ret['EmbarqueContratoS'] = EmbarqueContratoResource::collection($this->EmbarqueContratoS);
        }

        if ($this->relationLoaded('EmbarqueOrigemS')) {
            $ret['EmbarqueOrigemS'] = EmbarqueOrigemResource::collection($this->EmbarqueOrigemS);
        }

        return $ret;
    }
}
