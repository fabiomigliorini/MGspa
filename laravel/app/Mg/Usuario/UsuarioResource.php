<?php

namespace Mg\Usuario;

use Illuminate\Http\Resources\Json\JsonResource;

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
        $ret['permissoes'] = UsuarioService::buscaGrupoPermissoes($this->codusuario);
        return $ret;
    }
}
