<?php

namespace Mg\Cultura;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class CulturaTributoResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // remove relações em snake_case que o parent injeta — reexpostas em PascalCase abaixo.
        unset($ret['tributo'], $ret['unidade_referencia'], $ret['cultura']);

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        // relações em PascalCase (whenLoaded — chaves ausentes somem do JSON)
        $ret['Tributo'] = $this->whenLoaded('Tributo');
        $ret['UnidadeReferencia'] = $this->whenLoaded('UnidadeReferencia');

        return $ret;
    }
}
