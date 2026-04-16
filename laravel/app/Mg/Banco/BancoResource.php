<?php

namespace Mg\Banco;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class BancoResource extends Resource
{
    public function toArray($request)
    {
        return [
            'codbanco' => $this->codbanco,
            'banco' => $this->banco,
            'sigla' => $this->sigla,
            'numerobanco' => $this->numerobanco,
            'inativo' => $this->inativo,
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
            'codusuariocriacao' => $this->codusuariocriacao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
        ];
    }
}
