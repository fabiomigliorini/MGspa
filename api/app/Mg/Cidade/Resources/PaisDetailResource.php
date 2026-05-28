<?php

namespace Mg\Cidade\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaisDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codpais' => $this->codpais,
            'pais' => $this->pais,
            'sigla' => $this->sigla,
            'codigooficial' => $this->codigooficial,
            'inativo' => $this->inativo,

            // Auditoria
            'usuarioCriacao' => $this->relationLoaded('UsuarioCriacao') ? $this->UsuarioCriacao?->only(['codusuario', 'usuario']) : null,
            'usuarioAlteracao' => $this->relationLoaded('UsuarioAlteracao') ? $this->UsuarioAlteracao?->only(['codusuario', 'usuario']) : null,

            // Timestamps
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }
}
