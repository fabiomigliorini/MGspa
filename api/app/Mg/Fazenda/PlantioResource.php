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
        // ATENÇÃO: NÃO remover 'talhao' — o Plantio tem a COLUNA `talhao` (nome do
        // talhão) cuja chave colide com a relação Talhao(). O WITH do index não
        // carrega a relação, então `talhao` aqui é sempre a coluna (o nome). Tirá-la
        // deixava a lista cair no fallback "Talhão {codplantio}" (só o número).
        unset(
            $ret['safra'],
            $ret['fazenda'],
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
