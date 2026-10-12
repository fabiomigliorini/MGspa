<?php

namespace Mg\Titulo;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class TituloAgrupamentoListaResource extends Resource
{
    public function toArray($request)
    {
        $valor = (float)$this->valor;
        $operacao = ($valor < 0) ? 'CR' : 'DB';

        return [
            'codtituloagrupamento' => (int)$this->codtituloagrupamento,
            'codpessoa'            => (int)$this->codpessoa,
            'fantasia'             => optional($this->Pessoa)->fantasia,
            'emissao'              => $this->emissao,
            'criacao'              => $this->criacao,
            'cancelamento'         => $this->cancelamento,
            'codusuariocriacao'    => $this->codusuariocriacao,
            'valor'                => abs($valor),
            'operacao'             => $operacao,
            'observacao'           => $this->observacao,
        ];
    }
}
