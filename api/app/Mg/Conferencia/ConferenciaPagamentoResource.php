<?php

namespace Mg\Conferencia;

use Mg\Auditoria\AuditoriaService;
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
            'nsu' => $this->nsu,
            'integrado' => $this->ehIntegrado(),
            'indevido' => (bool) $this->indevido,
            'codmaquinetalote' => $this->codmaquinetalote,
            'codmaquinetalotecancelamento' => $this->codmaquinetalotecancelamento,
            'cancelamento' => $this->cancelamento,
            'codportadorperiodo' => $this->codportadorperiodo,
            'conferencia' => $this->conferencia,
            'justificativa' => $this->justificativa,
            // o selo "corrigido": so' o que mudou valor ou meio
            'correcoes' => $this->AuditoriaS()->whereIn('tipo', [
                AuditoriaService::TIPO_CORRIGIDO_CONFERENCIA,
                AuditoriaService::TIPO_REGISTRO_INDEVIDO,
            ])->count(),
        ]);
    }
}
