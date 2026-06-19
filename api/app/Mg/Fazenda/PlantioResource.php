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
            $ret['movimento_grao_s'],
        );

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        // `talhao` é COLUNA (nome/numero do talhão nesta safra) E também o nome da
        // relação Talhao() — colidem na mesma chave. O unset acima tira a relação;
        // aqui restauramos a STRING da coluna (o front usa p.talhao como rótulo).
        $ret['talhao'] = $this->resource->talhao;

        // relações em PascalCase (whenLoaded — chaves ausentes somem do JSON)
        $ret['Safra'] = $this->whenLoaded('Safra');
        $ret['Fazenda'] = $this->whenLoaded('Fazenda');
        $ret['Talhao'] = $this->whenLoaded('Talhao');
        $ret['Variedade'] = $this->whenLoaded('Variedade');

        if ($this->relationLoaded('MovimentoGraoS')) {
            $ret['MovimentoGraoS'] = $this->MovimentoGraoS;
        }

        return $ret;
    }
}
