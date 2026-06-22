<?php

namespace Mg\Contrato;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class ContratoFixacaoResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // remove relação em snake_case que o parent injeta
        unset($ret['contrato']);

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

        return $ret;
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
