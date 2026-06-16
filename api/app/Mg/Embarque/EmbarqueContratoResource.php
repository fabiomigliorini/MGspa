<?php

namespace Mg\Embarque;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class EmbarqueContratoResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // remove relações em snake_case que o parent injeta — reexpostas em PascalCase abaixo.
        unset(
            $ret['contrato'],
            $ret['embarque'],
            $ret['nota_fiscal'],
        );

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        // relações em PascalCase (whenLoaded — chaves ausentes somem do JSON)
        $ret['Contrato'] = $this->whenLoaded('Contrato');
        $ret['Embarque'] = $this->whenLoaded('Embarque');
        $ret['NotaFiscal'] = $this->whenLoaded('NotaFiscal');

        return $ret;
    }
}
