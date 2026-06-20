<?php

namespace Mg\Contrato;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class ContratoNotaResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // remove relações em snake_case que o parent injeta
        unset($ret['contrato'], $ret['natureza_operacao'], $ret['pessoa_nf'], $ret['pai']);

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        // relações em PascalCase
        $ret['NaturezaOperacao'] = $this->whenLoaded('NaturezaOperacao');
        $ret['PessoaNf'] = $this->whenLoaded('PessoaNf');

        return $ret;
    }
}
