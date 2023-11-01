<?php

namespace Mg\Pessoa;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class PessoaEnderecoResource extends JsonResource
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

        // Chave Extrangeira
        $ret['cidade'] = @$this->Cidade->cidade;
        $ret['uf'] = @$this->Cidade->Estado->sigla;
        $ret['usuariocriacao'] = @$this->UsuarioCriacao->usuario;
        $ret['usuarioalteracao'] = @$this->UsuarioAlteracao->usuario;

        return $ret;
    }
}


