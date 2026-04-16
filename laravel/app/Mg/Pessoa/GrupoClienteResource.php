<?php

namespace Mg\Pessoa;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class GrupoClienteResource extends Resource
{
    public function toArray($request)
    {
        return [
            'codgrupocliente' => $this->codgrupocliente,
            'grupocliente' => $this->grupocliente,
            'inativo' => $this->inativo,
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
            'codusuariocriacao' => $this->codusuariocriacao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
        ];
    }
}
