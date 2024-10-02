<?php

namespace Mg\Meta;

use Illuminate\Http\Resources\Json\JsonResource as Resource;


class MetaListagemResource extends Resource
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
        return $ret;
    }

}
