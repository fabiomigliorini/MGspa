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

        // Recebimentos (dinheiro que entrou) + ledger. O "a receber" é o líquido;
        // quitado = "recebida" mesmo com diferencinha; diferenca = recebido −
        // líquido (+ recebeu a mais, − a menos).
        $recebimentos = $this->relationLoaded('ContratoPagamentoS')
            ? $this->ContratoPagamentoS->filter(fn ($p) => $p->inativo === null)
            : collect();
        $recebido = round((float) $recebimentos->sum('valor'), 2);
        $liquido = (float) $this->liquidobrl;
        $estrangeira = (bool) $this->estrangeira;
        // Só dá pra RECEBER o que já virou R$ (BRL, ou US$ com câmbio travado).
        $podereceber = $liquido > 0;
        $ret['recebido'] = $recebido;
        $ret['saldoreceber'] = round($liquido - $recebido, 2);
        $ret['quitado'] = $this->quitado;
        $ret['diferenca'] = round($recebido - $liquido, 2);
        $ret['podereceber'] = $podereceber;
        // A receber na MOEDA da fixação: US$ = dólar firme (aparece já na fixação,
        // antes do câmbio); BRL = líquido em R$.
        $ret['arecebermoeda'] = $estrangeira ? round((float) $this->totalmoeda, 2) : $liquido;
        $ret['percentualrecebido'] = $podereceber ? min(100, round($recebido / $liquido * 100, 1)) : 0;
        $ret['statusrecebimento'] =
            $this->quitado !== null || ($podereceber && $recebido + 0.005 >= $liquido)
                ? 'RECEBIDA'
                : ($recebido > 0
                    ? 'PARCIAL'
                    : ($estrangeira && !$podereceber ? 'AGUARDANDO_CAMBIO' : 'ABERTO'));
        if ($this->relationLoaded('ContratoPagamentoS')) {
            $ret['ContratoPagamentoS'] = ContratoPagamentoResource::collection(
                $this->ContratoPagamentoS,
            );
        }

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        return $ret;
    }
}
