<?php

namespace Mg\UnidadeReferencia;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class UnidadeReferenciaValorResource extends Resource
{
    public function toArray($request)
    {
        return [
            'codunidadereferenciavalor' => (int) $this->codunidadereferenciavalor,
            'codunidadereferencia' => (int) $this->codunidadereferencia,
            'competencia' => optional($this->competencia)->toDateString(),
            'valor' => (float) $this->valor,
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
            'codusuariocriacao' => $this->codusuariocriacao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
        ];
    }
}
