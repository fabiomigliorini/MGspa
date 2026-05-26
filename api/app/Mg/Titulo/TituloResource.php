<?php

namespace Mg\Titulo;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class TituloResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $ret = parent::toArray($request);
        $ret['fantasia'] = $this->Pessoa->fantasia;
        $ret['tipotitulo'] = $this->TipoTitulo->tipotitulo;
        $ret['portador'] = $this->Portador->portador??null;
        return $ret;
    }
}
