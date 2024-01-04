<?php

namespace Mg\Pessoa;

use Illuminate\Http\Resources\Json\JsonResource;

class PessoaCertidaoResource extends JsonResource
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
        $ret['certidaotipo'] = $this->CertidaoTipo->certidaotipo;
        $ret['certidaoemissor'] = $this->CertidaoEmissor->certidaoemissor;
        return $ret;
    }
}
