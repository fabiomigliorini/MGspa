<?php

namespace Mg\GrupoEconomico;

use Illuminate\Http\Resources\Json\JsonResource;

class GrupoEconomicoResource extends JsonResource
{
    public function toArray($request): array
    {
        $ret = parent::toArray($request);
        $ret['usuariocriacao'] = $this->UsuarioCriacao->usuario ?? null;
        $ret['usuarioalteracao'] = $this->UsuarioAlteracao->usuario ?? null;

        // PessoasdoGrupo enxuto até PessoaResource ser portado integralmente
        $ret['PessoasdoGrupo'] = $this->PessoaS()
            ->where('codgrupoeconomico', $this->codgrupoeconomico)
            ->get(['codpessoa', 'fantasia', 'pessoa', 'fisica', 'cnpj', 'inativo']);

        return $ret;
    }
}
