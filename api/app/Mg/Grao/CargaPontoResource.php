<?php

namespace Mg\Grao;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class CargaPontoResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        unset(
            $ret['carga'],
            $ret['plantio'],
            $ret['unidade_armazenadora'],
            $ret['contrato'],
        );

        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        $ret['Plantio'] = $this->whenLoaded('Plantio');
        $ret['UnidadeArmazenadora'] = $this->whenLoaded('UnidadeArmazenadora');
        $ret['Contrato'] = $this->whenLoaded('Contrato');

        // Nome legivel da conta ("Talhao 12 — TMG 7262"). As telas online nao
        // tem o cache Dexie que o patio usa pra montar isso no front.
        $ret['rotulo'] = CargaPontoService::rotulo($this->resource);

        return $ret;
    }
}
