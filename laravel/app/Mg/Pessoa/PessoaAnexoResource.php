<?php

namespace Mg\Pessoa;

use Illuminate\Http\Resources\Json\JsonResource;

class PessoaAnexoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $ret = PessoaAnexoService::listagem($this->codpessoa);
        return $ret;
    }
}
