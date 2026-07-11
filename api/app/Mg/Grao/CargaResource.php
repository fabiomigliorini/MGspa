<?php

namespace Mg\Grao;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class CargaResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        unset(
            $ret['safra'],
            $ret['veiculo'],
            $ret['pessoa_motorista'],
            $ret['carga_ponto_s'],
            $ret['movimento_grao_s'],
        );

        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        $ret['Safra'] = $this->whenLoaded('Safra');
        $ret['Veiculo'] = $this->whenLoaded('Veiculo');
        $ret['PessoaMotorista'] = $this->whenLoaded('PessoaMotorista');

        if ($this->relationLoaded('CargaPontoS')) {
            $ret['CargaPontoS'] = CargaPontoResource::collection($this->CargaPontoS);
        }

        return $ret;
    }
}
