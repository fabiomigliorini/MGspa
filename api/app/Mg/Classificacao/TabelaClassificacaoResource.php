<?php

namespace Mg\Classificacao;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class TabelaClassificacaoResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // remove relações em snake_case que o parent injeta — reexpostas em PascalCase abaixo.
        unset(
            $ret['cultura'],
            $ret['tabela_classificacao_item_s'],
        );

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        // relações em PascalCase (whenLoaded — chaves ausentes somem do JSON)
        $ret['Cultura'] = $this->whenLoaded('Cultura');

        if ($this->relationLoaded('TabelaClassificacaoItemS')) {
            $ret['TabelaClassificacaoItemS'] = TabelaClassificacaoItemResource::collection($this->TabelaClassificacaoItemS);
        }

        return $ret;
    }
}
