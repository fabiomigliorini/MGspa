<?php

namespace Mg\Conferencia;

use Mg\Pagamento\PagamentoListaResource;
use Mg\Pagamento\PagamentoService;

// Linha da listagem unica com o que a conferencia corrige (M9 doc-3)
class ConferenciaPagamentoResource extends PagamentoListaResource
{
    public function toArray($request)
    {
        return array_merge(parent::toArray($request), [
            'principal' => (float) $this->principal,
            'bandeira' => $this->bandeira,
            'bandeiradescricao' => PagamentoService::BANDEIRAS[$this->bandeira] ?? null,
            'autorizacao' => $this->autorizacao,
            'integrado' => $this->ehIntegrado(),
            'indevido' => (bool) $this->indevido,
            'codmaquinetalote' => $this->codmaquinetalote,
            'codmaquinetalotecancelamento' => $this->codmaquinetalotecancelamento,
            'codportadorperiodo' => $this->codportadorperiodo,
            'conferencia' => $this->conferencia,
            'justificativa' => $this->justificativa,
            'correcoes' => $this->PagamentoCorrecaoS()->count(),
        ]);
    }
}
