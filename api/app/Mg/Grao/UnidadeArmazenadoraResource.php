<?php

namespace Mg\Grao;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class UnidadeArmazenadoraResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        unset($ret['pessoa']);

        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        $ret['Pessoa'] = $this->whenLoaded('Pessoa');

        return $ret;
    }
}
