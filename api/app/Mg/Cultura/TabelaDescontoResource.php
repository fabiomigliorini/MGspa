<?php

namespace Mg\Cultura;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class TabelaDescontoResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // remove relação em snake_case que o parent injeta — reexposta em PascalCase abaixo.
        unset($ret['cultura']);

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        // relações em PascalCase (whenLoaded — chaves ausentes somem do JSON)
        $ret['Cultura'] = $this->whenLoaded('Cultura');

        return $ret;
    }
}
