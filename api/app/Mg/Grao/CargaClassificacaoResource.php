<?php

namespace Mg\Grao;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class CargaClassificacaoResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // remove relação em snake_case que o parent injeta — reexposta em PascalCase abaixo.
        unset($ret['carga'], $ret['parametro_classificacao']);

        $ret['ParametroClassificacao'] = $this->whenLoaded('ParametroClassificacao');

        return $ret;
    }
}
