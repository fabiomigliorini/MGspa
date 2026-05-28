<?php

namespace Mg\Produto;

use Illuminate\Http\Resources\Json\JsonResource;

class ProdutoEmbalagemResource extends JsonResource
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
