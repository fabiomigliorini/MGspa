<?php

namespace Mg\Titulo;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class TipoMovimentoTituloResource extends Resource
{
    public function toArray($request)
    {
        return [
            'codtipomovimentotitulo' => $this->codtipomovimentotitulo,
            'tipomovimentotitulo' => $this->tipomovimentotitulo,
            'estorno' => in_array((int) $this->codtipomovimentotitulo, MovimentoTituloService::TIPOS_ESTORNO),
            'observacao' => $this->observacao,
            'inativo' => $this->inativo,
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
            'codusuariocriacao' => $this->codusuariocriacao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
        ];
    }
}
