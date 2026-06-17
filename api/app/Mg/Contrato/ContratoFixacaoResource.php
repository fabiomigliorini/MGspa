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

        // preço líquido (deduções sobre o precoreal, na data da fixação) — o agro
        // é dono do cálculo. Só calcula quando há contexto de cultura/contrato.
        $ret['precoliquido'] = $this->precoLiquido();

        return $ret;
    }

    /**
     * Líquido por saca da fixação, usando a cultura/isenção/funrural do contrato.
     * Retorna null quando não dá pra resolver o contexto (sem contrato carregado).
     */
    protected function precoLiquido(): ?float
    {
        $contrato = $this->whenLoaded('Contrato');
        if (!$contrato instanceof Contrato || empty($contrato->codcultura) || $this->precoreal === null) {
            return null;
        }

        $funruralvenda = $contrato->codfilial && $contrato->Filial
            ? (bool) $contrato->Filial->funruralvenda
            : false;

        $calc = ContratoCalculoService::calcular([
            'codcultura' => (int) $contrato->codcultura,
            'bruto' => (float) $this->precoreal,
            'data' => $this->data,
            'isentofethab' => (bool) $contrato->isentofethab,
            'funruralvenda' => $funruralvenda,
        ]);

        return $calc['liquido'];
    }
}
