<?php

namespace Mg\Dominio;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class DominioAcumuladorResource extends Resource
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
        $ret['cfop'] = $this->Cfop->cfop;
        return $ret;
    }
}
