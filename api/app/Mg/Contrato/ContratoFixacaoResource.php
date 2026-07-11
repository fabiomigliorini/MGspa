<?php

namespace Mg\Contrato;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class ContratoFixacaoResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // remove relações em snake_case que o parent injeta
        unset($ret['contrato'], $ret['contrato_pagamento_s']);

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        // Impostos: o SNAPSHOT travado na fixação (precoliquido/totaldeducao/
        // tributos digitados no modal) tem prioridade. Sem snapshot (fixação
        // antiga / espelho automático do FIXO), calcula on-the-fly com a config
        // atual e a competência da UPF na data — o agro é dono do cálculo.
        if ($this->precoliquido === null) {
            $calc = $this->calculoFallback();
            $ret['precoliquido'] = $calc['liquido'] ?? null;
            $ret['totaldeducao'] = $calc['totaldeducao'] ?? null;
            $ret['tributos'] = $calc['itens'] ?? null;
        }

        // Ledger POR FIXAÇÃO: quanto desta fixação já foi parcelado/recebido e o
        // saldo. Fecha SEMPRE na moeda da fixação (nunca cruza BRL+US$). Em US$ o
        // R$ recebido é o materializado (Σ valorrecebido); o "a receber" é firme em
        // sacas/US$ e o R$-equivalente só existe quando cada parcela liquida.
        $ret += $this->ledger();

        return $ret;
    }

    /**
     * Agregados das parcelas DESTA fixação (só ativas; recebidas = datarecebido).
     * Usa a relação já eager-loaded quando disponível (evita N+1 no detalhe).
     */
    protected function ledger(): array
    {
        $usd = $this->usd;
        $qtd = (float) $this->quantidade;
        $preco = (float) $this->preco;                                   // na moeda da fixação
        $precoreal = $this->precoreal !== null ? (float) $this->precoreal : null; // R$ (só BRL/USD travado)

        $parcelas = $this->relationLoaded('ContratoPagamentoS')
            ? $this->ContratoPagamentoS
            : $this->ContratoPagamentoS()->get();
        $ativas = $parcelas->filter(fn ($p) => $p->inativo === null);
        $confirmadas = $ativas->filter(fn ($p) => $p->datarecebido !== null);

        $sacasparceladas = (float) $ativas->sum('sacas');
        $sacasrecebidas = (float) $confirmadas->sum('sacas');
        $recebido = (float) $confirmadas->sum('valorrecebido');          // R$ materializado
        $recebidousd = $usd ? round($sacasrecebidas * $preco, 2) : null; // US$ reconhecido
        $cotacaomedia = ($usd && $recebidousd > 0) ? round($recebido / $recebidousd, 4) : null;

        // A receber: US$ é firme (sacas ainda não recebidas × preço); BRL é R$.
        $areceber = $usd
            ? round(($qtd - $sacasrecebidas) * $preco, 2)
            : ($precoreal !== null ? round($qtd * $precoreal - $recebido, 2) : null);

        // Só o que a tela consome (moeda já vem da coluna via parent::toArray):
        return [
            'saldosacas' => max(0, $qtd - $sacasparceladas), // sacas ainda a parcelar
            'recebido' => $recebido,                         // R$ materializado
            'recebidousd' => $recebidousd,                   // US$ reconhecido
            'cotacaomedia' => $cotacaomedia,
            'areceber' => $areceber,                         // saldo a receber na moeda
        ];
    }

    /**
     * Cálculo on-the-fly do líquido (deduções sobre o precoreal, na data da
     * fixação) usando a cultura/isenção/funrural do contrato. Fallback usado só
     * quando a fixação não tem snapshot gravado. Null sem contexto de contrato.
     */
    protected function calculoFallback(): ?array
    {
        $contrato = $this->whenLoaded('Contrato');
        if (!$contrato instanceof Contrato || empty($contrato->codcultura) || $this->precoreal === null) {
            return null;
        }

        $funruralvenda = $contrato->codfilial && $contrato->Filial
            ? (bool) $contrato->Filial->funruralvenda
            : false;

        return ContratoCalculoService::calcular([
            'codcultura' => (int) $contrato->codcultura,
            'bruto' => (float) $this->precoreal,
            'data' => $this->data,
            'isentofethab' => (bool) $this->isentofethab,
            'funruralvenda' => $funruralvenda,
        ]);
    }
}
