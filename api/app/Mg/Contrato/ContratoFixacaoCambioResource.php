<?php

namespace Mg\Contrato;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class ContratoFixacaoCambioResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // remove relação em snake_case que o parent injeta
        unset($ret['contrato_fixacao']);

        // valor da fatia convertido em R$ (trivial, mas evita recalcular no front)
        $ret['valorbrl'] = round((float) $this->valor * (float) $this->cotacao, 2);

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        return $ret;
    }
}
