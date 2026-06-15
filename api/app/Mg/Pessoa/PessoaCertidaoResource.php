<?php

namespace Mg\Pessoa;

use Illuminate\Http\Resources\Json\JsonResource;

class PessoaCertidaoResource extends JsonResource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['certidaotipo'] = @$this->CertidaoTipo->certidaotipo;
        $ret['certidaoemissor'] = @$this->CertidaoEmissor->certidaoemissor;
        return $ret;
    }
}
