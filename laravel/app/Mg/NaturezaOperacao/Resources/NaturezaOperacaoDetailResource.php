<?php

namespace Mg\NaturezaOperacao\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Mg\NaturezaOperacao\NaturezaOperacao;

class NaturezaOperacaoDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codnaturezaoperacao' => $this->codnaturezaoperacao,
            'naturezaoperacao' => $this->naturezaoperacao,
            'codoperacao' => $this->codoperacao,
            'operacao' => $this->formatOperacao(),
            'emitida' => $this->emitida,
            'observacoesnf' => $this->observacoesnf,
            'mensagemprocom' => $this->mensagemprocom,
            'codnaturezaoperacaodevolucao' => $this->codnaturezaoperacaodevolucao,
            'naturezaOperacaoDevolucao' => $this->formatNaturezaOperacaoDevolucao(),
            'codtipotitulo' => $this->codtipotitulo,
            'tipoTitulo' => $this->formatTipoTitulo(),
            'codcontacontabil' => $this->codcontacontabil,
            'contaContabil' => $this->formatContaContabil(),
            'finnfe' => $this->finnfe,
            'finnfeDescricao' => NaturezaOperacao::FINNFE_DESCRICOES[$this->finnfe] ?? null,
            'ibpt' => $this->ibpt,
            'codestoquemovimentotipo' => $this->codestoquemovimentotipo,
            'estoqueMovimentoTipo' => $this->formatEstoqueMovimentoTipo(),
            'estoque' => $this->estoque,
            'financeiro' => $this->financeiro,
            'compra' => $this->compra,
            'venda' => $this->venda,
            'vendadevolucao' => $this->vendadevolucao,
            'transferencia' => $this->transferencia,
            'preco' => $this->preco,

            // Auditoria
            'usuarioCriacao' => $this->formatUsuarioCriacao(),
            'usuarioAlteracao' => $this->formatUsuarioAlteracao(),

            // Timestamps
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }

    private function formatOperacao(): ?array
    {
        if (!$this->relationLoaded('Operacao')) {
            return null;
        }

        return $this->Operacao?->only(['codoperacao', 'operacao']);
    }

    private function formatNaturezaOperacaoDevolucao(): ?array
    {
        if (!$this->relationLoaded('NaturezaOperacaoDevolucao')) {
            return null;
        }

        return $this->NaturezaOperacaoDevolucao?->only(['codnaturezaoperacao', 'naturezaoperacao']);
    }

    private function formatTipoTitulo(): ?array
    {
        if (!$this->relationLoaded('TipoTitulo')) {
            return null;
        }

        return $this->TipoTitulo?->only(['codtipotitulo', 'tipotitulo']);
    }

    private function formatContaContabil(): ?array
    {
        if (!$this->relationLoaded('ContaContabil')) {
            return null;
        }

        return $this->ContaContabil?->only(['codcontacontabil', 'contacontabil']);
    }

    private function formatEstoqueMovimentoTipo(): ?array
    {
        if (!$this->relationLoaded('EstoqueMovimentoTipo')) {
            return null;
        }

        return $this->EstoqueMovimentoTipo?->only(['codestoquemovimentotipo', 'estoquemovimentotipo']);
    }

    private function formatUsuarioCriacao(): ?array
    {
        if (!$this->relationLoaded('UsuarioCriacao')) {
            return null;
        }

        return $this->UsuarioCriacao?->only(['codusuario', 'usuario']);
    }

    private function formatUsuarioAlteracao(): ?array
    {
        if (!$this->relationLoaded('UsuarioAlteracao')) {
            return null;
        }

        return $this->UsuarioAlteracao?->only(['codusuario', 'usuario']);
    }
}
