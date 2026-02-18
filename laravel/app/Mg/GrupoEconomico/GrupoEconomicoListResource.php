<?php

namespace Mg\GrupoEconomico;

use Illuminate\Http\Resources\Json\JsonResource;

class GrupoEconomicoListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'codgrupoeconomico' => $this->codgrupoeconomico,
            'grupoeconomico' => $this->grupoeconomico,
            'observacoes' => $this->observacoes,
            'inativo' => $this->inativo,
            'PessoasdoGrupo' => $this->PessoaS()
                ->where('codgrupoeconomico', $this->codgrupoeconomico)
                ->whereNull('inativo')
                ->get(['codpessoa', 'fantasia', 'pessoa', 'fisica']),
        ];
    }
}
