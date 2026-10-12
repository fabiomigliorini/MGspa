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
            'natureza' => $this->natureza,
            'movimentaportador' => (bool) $this->movimentaportador,
            'pagar' => (bool) $this->pagar,
            'receber' => (bool) $this->receber,
            'observacoes' => $this->observacoes,
            'inativo' => $this->inativo,
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
            'codusuariocriacao' => $this->codusuariocriacao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
        ];
    }
}
