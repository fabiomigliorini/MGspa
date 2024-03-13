<?php

namespace Mg\Usuario;

use Illuminate\Http\Resources\Json\JsonResource;
use Mg\Pessoa\PessoaResource;

class UsuarioResource extends JsonResource
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
        unset($ret['senha']);
        unset($ret['remember_token']);

        if ($this->codpessoa) {
            $ret['Pessoa'] = new PessoaResource($this->Pessoa);
        }
        $ret['permissoes'] = UsuarioService::buscaGrupoPermissoes($this->codusuario);
        $ret['filial'] = $this->Filial->filial;
        return $ret;
    }
}
