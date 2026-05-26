<?php

namespace Mg\Pessoa;

use Illuminate\Http\Resources\Json\JsonResource;

class PessoaEmailResource extends JsonResource
{
    public function toArray($request): array
    {
        $ret = parent::toArray($request);
        $ret['usuariocriacao'] = $this->UsuarioCriacao->usuario ?? null;
        $ret['usuarioalteracao'] = $this->UsuarioAlteracao->usuario ?? null;
        return $ret;
    }
}
