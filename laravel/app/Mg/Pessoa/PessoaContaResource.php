<?php

namespace Mg\Pessoa;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class PessoaContaResource extends JsonResource
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

        $ret['usuariocriacao'] = @$this->UsuarioCriacao->usuario;
        $ret['usuarioalteracao'] = @$this->UsuarioAlteracao->usuario;
        $ret['nomeBanco'] = @$this->Banco->banco;

        return $ret;
    }
}


