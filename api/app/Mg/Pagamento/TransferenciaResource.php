<?php

namespace Mg\Pagamento;

// Linha de transferencia (M11 doc-3): a da listagem unica, com os dois
// lados e o que o usuario pode fazer com ela
class TransferenciaResource extends PagamentoListaResource
{
    public function toArray($request)
    {
        return array_merge(parent::toArray($request), [
            'codportadororigem' => $this->codportadororigem,
            'portadororigem' => optional($this->PortadorOrigem)->portador,
            'filialorigem' => optional(optional($this->PortadorOrigem)->Filial)->filial,
            'codportadordestino' => $this->codportadordestino,
            'portadordestino' => optional($this->PortadorDestino)->portador,
            'filialdestino' => optional(optional($this->PortadorDestino)->Filial)->filial,
            'efetivacao' => $this->efetivacao,
            'cancelamento' => $this->cancelamento,
            'justificativa' => $this->justificativa,
            'podeConfirmar' => TransferenciaAutorizador::podeConfirmar($this->resource),
            'podeCancelar' => TransferenciaAutorizador::podeCancelar($this->resource),
        ]);
    }
}
