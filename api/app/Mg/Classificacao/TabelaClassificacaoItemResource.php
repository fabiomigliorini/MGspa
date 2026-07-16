<?php

namespace Mg\Classificacao;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class TabelaClassificacaoItemResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // remove relação em snake_case que o parent injeta — reexposta em PascalCase abaixo.
        unset($ret['parametro_classificacao'], $ret['tabela_classificacao']);

        // relações em PascalCase (whenLoaded — chaves ausentes somem do JSON)
        $ret['ParametroClassificacao'] = $this->whenLoaded('ParametroClassificacao');

        return $ret;
    }
}
