<?php

namespace Mg\Contrato;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class ContratoFixacaoResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // remove relações em snake_case que o parent injeta
        unset($ret['contrato'], $ret['moeda'], $ret['contrato_pagamento_s'], $ret['contrato_fixacao_cambio_s']);

        // Moeda (FK codmoeda): objeto p/ o front + iso/símbolo de conveniência.
        $ret['Moeda'] = $this->whenLoaded('Moeda');
        $ret['moeda'] = $this->Moeda->iso ?? 'BRL';     // iso (BRL/USD/…) p/ display
        $ret['estrangeira'] = $this->estrangeira;       // predicado "é ≠ Real?"

        // Travas de câmbio desta fixação (data · valor · cotação · valorbrl).
        if ($this->relationLoaded('ContratoFixacaoCambioS')) {
            $ret['ContratoFixacaoCambioS'] = ContratoFixacaoCambioResource::collection(
                $this->ContratoFixacaoCambioS,
            );
        }

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        return $ret;
    }
}
