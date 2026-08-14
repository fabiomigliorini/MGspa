<?php

namespace Mg\Classificacao;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class ParametroClassificacaoResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // remove a relação em snake_case que o parent injeta — reexposta em
        // PascalCase abaixo. Seguro: tblparametroclassificacao não tem coluna
        // `cultura` (o nome da cultura mora em tblcultura).
        unset($ret['cultura']);

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        $ret['Cultura'] = $this->whenLoaded('Cultura');

        return $ret;
    }
}
