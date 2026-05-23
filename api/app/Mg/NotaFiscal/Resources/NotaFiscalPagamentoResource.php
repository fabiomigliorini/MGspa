<?php

namespace Mg\NotaFiscal\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use Mg\Negocio\NegocioFormaPagamentoService;
use Mg\NotaFiscal\NotaFiscalPagamento;

class NotaFiscalPagamentoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codnotafiscalpagamento' => $this->codnotafiscalpagamento,
            'codnotafiscal' => $this->codnotafiscal,
            'codpessoa' => $this->codpessoa,
            'fantasia' => $this->Pessoa?->fantasia,

            // Dados do Pagamento
            'tipo' => $this->tipo,
            'tipodescricao' => NegocioFormaPagamentoService::TIPOS[$this->tipo] ?? null,
            'descricao' => $this->descricao,
            'valorpagamento' => $this->valorpagamento,
            'avista' => $this->avista,
            'troco' => $this->troco,

            // Cartão
            'bandeira' => $this->bandeira,
            'bandeiradescricao' => NegocioFormaPagamentoService::BANDEIRAS[$this->bandeira] ?? null,
            'autorizacao' => $this->autorizacao,
            'integracao' => $this->integracao,

            // Timestamps
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }
}
