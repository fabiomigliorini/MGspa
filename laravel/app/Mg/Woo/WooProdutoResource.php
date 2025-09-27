<?php

namespace Mg\Woo;

use Illuminate\Http\Resources\Json\JsonResource;

class WooProdutoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            // Chaves Primárias / Estrangeiras
            'codwooproduto' => $this->codwooproduto,
            'codproduto' => $this->codproduto,
            'codprodutobarraunidade' => $this->codprodutobarraunidade,
            'codprodutovariacao' => $this->codprodutovariacao,
            
            // Dados da Integração Woo
            'id' => $this->id,
            'idvariation' => $this->idvariation,
            'integracao' => $this->integracao, // Caractere simples
            
            // Dados de Margem / Quantidade
            'margempacote' => $this->margempacote,
            'margemunidade' => $this->margemunidade,
            'quantidadeembalagem' => $this->quantidadeembalagem,
            'quantidadepacote' => $this->quantidadepacote,

            // Campos de Metadados / Controle (Timestamps e Usuários)
            'criacao' => $this->criacao ? $this->criacao->format('Y-m-d H:i:s') : null,
            'alteracao' => $this->alteracao ? $this->alteracao->format('Y-m-d H:i:s') : null,
            'inativo' => $this->inativo ? $this->inativo->format('Y-m-d H:i:s') : null,
            'exportacao' => $this->exportacao, // Assumindo que este campo é um timestamp

            'codusuariocriacao' => $this->codusuariocriacao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
        ];
    }
}