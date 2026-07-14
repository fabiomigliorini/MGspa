<?php

namespace Mg\Classificacao;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class ParametroClassificacaoResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        return $ret;
    }
}
