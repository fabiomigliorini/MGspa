<?php

namespace Mg\NfeTerceiro\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NfeTerceiroResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codnfeterceiro' => $this->codnfeterceiro,
            // 'codnotafiscal' => $this->codnotafiscal,

            // Filial
            'codfilial' => $this->codfilial,
            'filial' => $this->Filial?->only(['codfilial', 'filial', 'cnpj']),

            // Pessoa (Emitente)
            'codpessoa' => $this->codpessoa,
            // 'pessoa' => $this->Pessoa?->only(['codpessoa', 'pessoa', 'fantasia', 'cnpj', 'ie']),
            'emitente' => $this->emitente,
            'cnpj' => $this->cnpj,
            'ie' => $this->ie,

            // Natureza de Operacao
            // 'codnaturezaoperacao' => $this->codnaturezaoperacao,
            // 'naturezaOperacao' => $this->NaturezaOperacao?->only(['codnaturezaoperacao', 'naturezaoperacao', 'cfop']),
            'natureza' => $this->natureza,

            // Operacao
            // 'codoperacao' => $this->codoperacao,
            // 'operacao' => $this->Operacao?->only(['codoperacao', 'operacao']),

            // Dados da Nota
            'modelo' => $this->modelo,
            'serie' => $this->serie,
            'numero' => $this->numero,
            'nfechave' => $this->nfechave,
            'tipo' => $this->tipo,
            'finalidade' => $this->finalidade,

            // Datas
            'emissao' => $this->emissao,
            // 'entrada' => $this->entrada,
            'nfedataautorizacao' => $this->nfedataautorizacao,

            // Valores
            // 'valorprodutos' => $this->valorprodutos,
            // 'valorfrete' => $this->valorfrete,
            // 'valorseguro' => $this->valorseguro,
            // 'valordesconto' => $this->valordesconto,
            // 'valoroutras' => $this->valoroutras,
            'valortotal' => $this->valortotal,

            // Impostos
            // 'icmsbase' => $this->icmsbase,
            // 'icmsvalor' => $this->icmsvalor,
            // 'icmsstbase' => $this->icmsstbase,
            // 'icmsstvalor' => $this->icmsstvalor,
            // 'ipivalor' => $this->ipivalor,

            // Situacao e Manifestacao
            // 'indsituacao' => $this->indsituacao,
            // 'indmanifestacao' => $this->indmanifestacao,
            // 'ignorada' => $this->ignorada,

            // Observacoes
            // 'informacoes' => $this->informacoes,
            'observacoes' => $this->observacoes,
            // 'justificativa' => $this->justificativa,

            // Revisao
            // 'revisao' => $this->revisao,
            // 'codusuariorevisao' => $this->codusuariorevisao,

            // Negocio
            // 'codnegocio' => $this->codnegocio,

            // NSU
            'nsu' => $this->nsu,

            // Timestamps
            // 'criacao' => $this->criacao,
            // 'alteracao' => $this->alteracao,
        ];
    }
}
