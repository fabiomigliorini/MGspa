<?php

namespace Mg\Safra;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class CargaColheitaResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // remove relações em snake_case que o parent injeta (Laravel serializa
        // relação carregada em snake por padrão) — reexpostas em PascalCase abaixo.
        unset(
            $ret['safra'],
            $ret['veiculo'],
            $ret['pessoa_motorista'],
            $ret['carga_colheita_plantio_s'],
        );

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        // relações em PascalCase (whenLoaded — chaves ausentes somem do JSON)
        $ret['Safra'] = $this->whenLoaded('Safra');
        $ret['Veiculo'] = $this->whenLoaded('Veiculo');
        $ret['PessoaMotorista'] = $this->whenLoaded('PessoaMotorista');

        if ($this->relationLoaded('CargaColheitaPlantioS')) {
            $ret['CargaColheitaPlantioS'] = CargaColheitaPlantioResource::collection($this->CargaColheitaPlantioS);
        }

        return $ret;
    }
}
