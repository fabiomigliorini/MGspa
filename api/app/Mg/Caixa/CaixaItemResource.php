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
