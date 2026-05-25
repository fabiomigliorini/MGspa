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
            'operacao' => $this->rel('Operacao', ['codoperacao', 'operacao']),
            'emitida' => $this->emitida,
            'observacoesnf' => $this->observacoesnf,
            'mensagemprocom' => $this->mensagemprocom,
            'codnaturezaoperacaodevolucao' => $this->codnaturezaoperacaodevolucao,
            'naturezaOperacaoDevolucao' => $this->rel('NaturezaOperacaoDevolucao', ['codnaturezaoperacao', 'naturezaoperacao']),
            'codtipotitulo' => $this->codtipotitulo,
            'tipoTitulo' => $this->rel('TipoTitulo', ['codtipotitulo', 'tipotitulo']),
            'codcontacontabil' => $this->codcontacontabil,
            'contaContabil' => $this->rel('ContaContabil', ['codcontacontabil', 'contacontabil']),
            'finnfe' => $this->finnfe,
            'finnfeDescricao' => NaturezaOperacao::FINNFE_DESCRICOES[$this->finnfe] ?? null,
            'ibpt' => $this->ibpt,
            'codestoquemovimentotipo' => $this->codestoquemovimentotipo,
            'estoqueMovimentoTipo' => $this->rel('EstoqueMovimentoTipo', ['codestoquemovimentotipo', 'estoquemovimentotipo']),
            'estoque' => $this->estoque,
            'financeiro' => $this->financeiro,
            'compra' => $this->compra,
            'venda' => $this->venda,
            'vendadevolucao' => $this->vendadevolucao,
            'transferencia' => $this->transferencia,
            'preco' => $this->preco,
            'usuarioCriacao' => $this->rel('UsuarioCriacao', ['codusuario', 'usuario']),
            'usuarioAlteracao' => $this->rel('UsuarioAlteracao', ['codusuario', 'usuario']),
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }

    private function rel(string $name, array $fields): ?array
    {
        return $this->relationLoaded($name) ? $this->$name?->only($fields) : null;
    }
}
