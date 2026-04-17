<?php

namespace Mg\Titulo;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class TipoTituloResource extends Resource
{
    public function toArray($request)
    {
        return [
            'codtipotitulo' => $this->codtipotitulo,
            'tipotitulo' => $this->tipotitulo,
            'pagar' => (bool) $this->pagar,
            'receber' => (bool) $this->receber,
            'debito' => (bool) $this->debito,
            'credito' => (bool) $this->credito,
            'observacoes' => $this->observacoes,
            'inativo' => $this->inativo,
            'codtipomovimentotitulo' => $this->codtipomovimentotitulo,
            'tipomovimentotitulo' => $this->whenLoaded('TipoMovimentoTitulo', function () {
                return $this->TipoMovimentoTitulo->tipomovimentotitulo;
            }),
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
            'codusuariocriacao' => $this->codusuariocriacao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
        ];
    }
}
