<?php

namespace Mg\Pessoa;

use Illuminate\Http\Resources\Json\JsonResource;

class PessoaEnderecoResource extends JsonResource
{
    public function toArray($request): array
    {
        $ret = parent::toArray($request);
        $ret['cidade'] = $this->Cidade->cidade ?? null;
        $ret['uf'] = $this->Cidade->Estado->sigla ?? null;
        return $ret;
    }
}
