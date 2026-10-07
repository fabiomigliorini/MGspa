<?php

namespace Mg\Caixa;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class CaixaItemResource extends Resource
{
    public function toArray($request)
    {
        return [
            'codcaixaitem' => $this->codcaixaitem,
            'item' => $this->item,
            'modo' => $this->modo,
            'codpessoa' => $this->codpessoa,
            'pessoa' => optional($this->Pessoa)->fantasia,
            'codfilial' => $this->codfilial,
            'filial' => optional($this->Filial)->filial,
            'codcontacontabil' => $this->codcontacontabil,
            'contacontabil' => optional($this->ContaContabil)->contacontabil,
            'saldo' => $this->saldo,
            'saldoquantidade' => $this->saldoquantidade,
            'inativo' => $this->inativo,
            'criacao' => $this->criacao,
            'codusuariocriacao' => $this->codusuariocriacao,
            'usuariocriacao' => $this->usuariocriacao,
            'alteracao' => $this->alteracao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
            'usuarioalteracao' => $this->usuarioalteracao,
        ];
    }
}
