<?php

namespace Mg\Produto;

use Illuminate\Http\Resources\Json\Resource;

class ProdutoImagemResource extends Resource
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
        $ret['url'] = $this->Imagem->url;
        return $ret;
    }
}
