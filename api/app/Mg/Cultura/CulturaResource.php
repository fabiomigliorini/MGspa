<?php

namespace Mg\Cultura;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class CulturaResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // remove relações em snake_case que o parent injeta (Laravel serializa
        // relação carregada em snake por padrão) — reexpostas em PascalCase abaixo.
        unset(
            $ret['safra_s'],
            $ret['tabela_classificacao'],
            $ret['tabela_classificacao_s'],
            $ret['variedade_s'],
            $ret['cultura_tributo_s'],
        );

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        // relações filhas (hasMany — guardadas p/ não disparar N+1 na listagem)
        if ($this->relationLoaded('VariedadeS')) {
            $ret['VariedadeS'] = VariedadeResource::collection($this->VariedadeS);
        }

        if ($this->relationLoaded('TabelaClassificacao')) {
            $ret['TabelaClassificacao'] = $this->whenLoaded('TabelaClassificacao');
        }

        if ($this->relationLoaded('TabelaClassificacaoS')) {
            $ret['TabelaClassificacaoS'] = \Mg\Classificacao\TabelaClassificacaoResource::collection($this->TabelaClassificacaoS);
        }

        return $ret;
    }
}
