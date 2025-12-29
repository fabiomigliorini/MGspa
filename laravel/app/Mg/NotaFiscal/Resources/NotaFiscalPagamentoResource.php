<?php

namespace Mg\NotaFiscal\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotaFiscalPagamentoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codnotafiscalpagamento' => $this->codnotafiscalpagamento,
            'codnotafiscal' => $this->codnotafiscal,
            'codpessoa' => $this->codpessoa,

            // Dados do Pagamento
            'tipo' => $this->tipo,
            'descricao' => $this->descricao,
            'valorpagamento' => $this->valorpagamento,
            'avista' => $this->avista,
            'troco' => $this->troco,

            // Cartão
            'bandeira' => $this->bandeira,
            'autorizacao' => $this->autorizacao,
            'integracao' => $this->integracao,

            // Relacionamentos
            'pessoa' => $this->formatPessoa(),

            // Timestamps
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }

    private function formatPessoa(): ?array
    {
        if (!$this->relationLoaded('Pessoa')) {
            return null;
        }

        return $this->Pessoa?->only([
            'codpessoa',
            'pessoa',
            'fantasia',
            'cnpj',
        ]);
    }
}
