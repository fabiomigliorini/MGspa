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
            'operacao' => $this->Operacao?->only(['codoperacao', 'operacao']),
            'emitida' => $this->emitida,
            'observacoesnf' => $this->observacoesnf,
            'mensagemprocom' => $this->mensagemprocom,
            'codnaturezaoperacaodevolucao' => $this->codnaturezaoperacaodevolucao,
            'naturezaOperacaoDevolucao' => $this->NaturezaOperacaoDevolucao?->only(['codnaturezaoperacao', 'naturezaoperacao']),
            'codtipotitulo' => $this->codtipotitulo,
            'tipoTitulo' => $this->TipoTitulo?->only(['codtipotitulo', 'tipotitulo']),
            'codcontacontabil' => $this->codcontacontabil,
            'contaContabil' => $this->ContaContabil?->only(['codcontacontabil', 'contacontabil']),
            'finnfe' => $this->finnfe,
            'finnfeDescricao' => NaturezaOperacao::FINNFE_DESCRICOES[$this->finnfe] ?? null,
            'ibpt' => $this->ibpt,
            'codestoquemovimentotipo' => $this->codestoquemovimentotipo,
            'estoqueMovimentoTipo' => $this->EstoqueMovimentoTipo?->only(['codestoquemovimentotipo', 'estoquemovimentotipo']),
            'estoque' => $this->estoque,
            'financeiro' => $this->financeiro,
            'compra' => $this->compra,
            'venda' => $this->venda,
            'vendadevolucao' => $this->vendadevolucao,
            'transferencia' => $this->transferencia,
            'preco' => $this->preco,
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }
}
