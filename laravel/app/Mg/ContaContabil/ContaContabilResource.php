<?php

namespace Mg\ContaContabil;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class ContaContabilResource extends Resource
{
    public function toArray($request)
    {
        return [
            'codcontacontabil' => $this->codcontacontabil,
            'contacontabil' => $this->contacontabil,
            'numero' => $this->numero,
            'inativo' => $this->inativo,
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
            'codusuariocriacao' => $this->codusuariocriacao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
        ];
    }
}
