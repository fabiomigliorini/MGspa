<?php

namespace Mg\Filial;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class SetorResource extends Resource
{
    public function toArray($request)
    {
        $data = parent::toArray($request);
        $data['UnidadeNegocio'] = $this->UnidadeNegocio ? $this->UnidadeNegocio->only(['codunidadenegocio', 'descricao']) : null;
        $data['TipoSetor'] = $this->TipoSetor ? $this->TipoSetor->only(['codtiposetor', 'tiposetor']) : null;
        return $data;
    }
}
