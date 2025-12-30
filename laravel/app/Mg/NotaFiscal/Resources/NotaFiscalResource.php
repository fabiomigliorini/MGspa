<?php

namespace Mg\NotaFiscal\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Mg\NotaFiscal\NotaFiscalService;

class NotaFiscalResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codnotafiscal' => $this->codnotafiscal,
            'codfilial' => $this->codfilial,
            'filial' => $this->formatFilial(),
            'codpessoa' => $this->codpessoa,
            'pessoa' => $this->formatPessoa(),
            'codnaturezaoperacao' => $this->codnaturezaoperacao,
            'naturezaOperacao' => $this->formatNaturezaOperacao(),
            'codoperacao' => $this->codoperacao,
            'operacao' => $this->formatOperacao(),

            'emitida' => $this->emitida,
            'modelo' => $this->modelo,
            'serie' => $this->serie,
            'numero' => $this->numero,
            'nfechave' => $this->nfechave,

            'emissao' => $this->emissao,
            'saida' => $this->saida,

            'valorprodutos' => $this->valorprodutos,
            'valorfrete' => $this->valorfrete,
            'valorseguro' => $this->valorseguro,
            'valoroutros' => $this->valoroutros,
            'valordesconto' => $this->valordesconto,
            'valortotal' => $this->valortotal,

            'status' => $this->status,

            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }

    private function formatFilial(): ?array
    {
        if (!$this->relationLoaded('Filial')) {
            return null;
        }

        return $this->Filial?->only(['codfilial', 'filial']);
    }

    private function formatPessoa(): ?array
    {
        if (!$this->relationLoaded('Pessoa')) {
            return null;
        }

        return $this->Pessoa?->only(['codpessoa', 'pessoa', 'fantasia', 'cnpj', 'ie']);
    }

    private function formatNaturezaOperacao(): ?array
    {
        if (!$this->relationLoaded('NaturezaOperacao')) {
            return null;
        }

        return $this->NaturezaOperacao?->only(['codnaturezaoperacao', 'naturezaoperacao']);
    }

    private function formatOperacao(): ?array
    {
        if (!$this->relationLoaded('Operacao')) {
            return null;
        }

        return $this->Operacao?->only(['codoperacao', 'operacao']);
    }
}
