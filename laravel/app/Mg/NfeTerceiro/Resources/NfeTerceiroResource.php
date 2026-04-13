<?php

namespace Mg\NfeTerceiro\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NfeTerceiroResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codnfeterceiro' => $this->codnfeterceiro,

            // Filial
            'codfilial' => $this->codfilial,
            'filial' => $this->formatFilial(),

            // Pessoa (Emitente)
            'codpessoa' => $this->codpessoa,
            'pessoa' => $this->formatPessoa(),
            'emitente' => $this->emitente,
            'cnpj' => $this->cnpj,
            'ie' => $this->ie,

            // Natureza de Operacao
            'codnaturezaoperacao' => $this->codnaturezaoperacao,
            'naturezaOperacao' => $this->NaturezaOperacao?->only(['codnaturezaoperacao', 'naturezaoperacao']),
            'natureza' => $this->natureza,

            // Negocio / Nota Fiscal
            'codnegocio' => $this->codnegocio,
            'codnotafiscal' => $this->codnotafiscal,

            // Dados da Nota
            'modelo' => $this->modelo,
            'serie' => $this->serie,
            'numero' => $this->numero,
            'nfechave' => $this->nfechave,
            'tipo' => $this->tipo,
            'finalidade' => $this->finalidade,

            // Datas
            'emissao' => $this->emissao,
            'entrada' => $this->entrada,
            'nfedataautorizacao' => $this->nfedataautorizacao,

            // Valores
            'valorprodutos' => $this->valorprodutos,
            'valorfrete' => $this->valorfrete,
            'valorseguro' => $this->valorseguro,
            'valordesconto' => $this->valordesconto,
            'valoroutras' => $this->valoroutras,
            'valortotal' => $this->valortotal,

            // Impostos
            'icmsbase' => $this->icmsbase,
            'icmsvalor' => $this->icmsvalor,
            'icmsstbase' => $this->icmsstbase,
            'icmsstvalor' => $this->icmsstvalor,
            'ipivalor' => $this->ipivalor,

            // Situacao e Manifestacao
            'indsituacao' => $this->indsituacao,
            'indmanifestacao' => $this->indmanifestacao,
            'ignorada' => $this->ignorada,
            'justificativa' => $this->justificativa,

            // Observacoes
            'informacoes' => $this->informacoes,
            'observacoes' => $this->observacoes,

            // Revisao
            'revisao' => $this->revisao,
            'codusuariorevisao' => $this->codusuariorevisao,
            'usuarioRevisao' => $this->UsuarioRevisao?->only(['codusuario', 'usuario']),

            // Conferencia
            'conferencia' => $this->conferencia,
            'codusuarioconferencia' => $this->codusuarioconferencia,
            'usuarioConferencia' => $this->UsuarioConferencia?->only(['codusuario', 'usuario']),

            // NSU
            'nsu' => $this->nsu,

            // Timestamps
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
            'usuarioAlteracao' => $this->UsuarioAlteracao?->only(['codusuario', 'usuario']),

            // Relações filhas (carregadas apenas no show)
            'itens' => $this->when(
                $this->relationLoaded('NfeTerceiroItemS'),
                fn () => NfeTerceiroItemResource::collection($this->NfeTerceiroItemS)
            ),
            'duplicatas' => $this->when(
                $this->relationLoaded('NfeTerceiroDuplicataS'),
                fn () => NfeTerceiroDuplicataResource::collection($this->NfeTerceiroDuplicataS)
            ),
            'pagamentos' => $this->when(
                $this->relationLoaded('NfeTerceiroPagamentoS'),
                fn () => NfeTerceiroPagamentoResource::collection($this->NfeTerceiroPagamentoS)
            ),
        ];
    }

    private function formatFilial(): ?array
    {
        if (!$this->relationLoaded('Filial')) {
            return null;
        }
        return $this->Filial?->only(['codfilial', 'filial', 'cnpj']);
    }

    private function formatPessoa(): ?array
    {
        if (!$this->relationLoaded('Pessoa')) {
            return null;
        }
        return $this->Pessoa?->only(['codpessoa', 'pessoa', 'fantasia', 'cnpj', 'ie']);
    }
}
