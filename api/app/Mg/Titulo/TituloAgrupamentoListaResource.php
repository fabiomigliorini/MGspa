<?php

namespace Mg\Titulo;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class TituloAgrupamentoListaResource extends Resource
{
    public function toArray($request)
    {
        $debito = (float)$this->debito;
        $credito = (float)$this->credito;
        $valor = $debito - $credito;
        $operacao = ($valor < 0) ? 'CR' : 'DB';

        return [
            'codtituloagrupamento' => (int)$this->codtituloagrupamento,
            'codpessoa'            => (int)$this->codpessoa,
            'fantasia'             => optional($this->Pessoa)->fantasia,
            'emissao'              => $this->emissao,
            'criacao'              => $this->criacao,
            'cancelamento'         => $this->cancelamento,
            'codusuariocriacao'    => $this->codusuariocriacao,
            'debito'               => $debito,
            'credito'              => $credito,
            'valor'                => abs($valor),
            'operacao'             => $operacao,
            'observacao'           => $this->observacao,
        ];
    }
}
