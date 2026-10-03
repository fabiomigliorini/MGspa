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
            'mododescricao' => CaixaItem::MODOS[$this->modo] ?? null,
            'codfilial' => $this->codfilial,
            'filial' => optional($this->Filial)->filial,
            'codpessoa' => $this->codpessoa,
            'fantasia' => optional($this->Pessoa)->fantasia,
            'codcontacontabil' => $this->codcontacontabil,
            'contacontabil' => optional($this->ContaContabil)->contacontabil,
            'ordem' => $this->ordem,
            'inativo' => $this->inativo,
            'criacao' => $this->criacao,
            'codusuariocriacao' => $this->codusuariocriacao,
            'usuariocriacao' => optional($this->UsuarioCriacao)->usuario,
            'alteracao' => $this->alteracao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
            'usuarioalteracao' => optional($this->UsuarioAlteracao)->usuario,
        ];
    }
}
