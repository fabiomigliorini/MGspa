<?php

namespace Mg\GrupoEconomico;

use Illuminate\Http\Resources\Json\JsonResource;
use Mg\Pessoa\PessoaResource;

class GrupoEconomicoResource extends JsonResource
{
    public function toArray($request): array
    {
        $ret = parent::toArray($request);
        $ret['usuariocriacao'] = $this->UsuarioCriacao->usuario ?? null;
        $ret['usuarioalteracao'] = $this->UsuarioAlteracao->usuario ?? null;

        $ret['PessoasdoGrupo'] = PessoaResource::collection(
            $this->PessoaS()->where('codgrupoeconomico', $this->codgrupoeconomico)->get()
        );

        return $ret;
    }
}
