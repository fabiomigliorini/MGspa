<?php

namespace Mg\Pessoa;

use Illuminate\Http\Resources\Json\JsonResource;

class DependenteResource extends JsonResource
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

        $ret['dependente'] = @$this->Pessoa->pessoa;
        $ret['responsavel'] = @$this->PessoaResponsavel->pessoa;
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;
        $ret['tipdepdescricao'] = DependenteService::TIPDEP_LABELS[$this->tipdep] ?? null;

        return $ret;
    }
}
