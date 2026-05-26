<?php

namespace Mg\Cidade\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EstadoDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codestado' => $this->codestado,
            'codpais' => $this->codpais,
            'estado' => $this->estado,
            'sigla' => $this->sigla,
            'codigooficial' => $this->codigooficial,
            'pais' => $this->relationLoaded('Pais') ? $this->Pais?->only(['codpais', 'pais', 'sigla']) : null,
            'usuarioCriacao' => $this->relationLoaded('UsuarioCriacao') ? $this->UsuarioCriacao?->only(['codusuario', 'usuario']) : null,
            'usuarioAlteracao' => $this->relationLoaded('UsuarioAlteracao') ? $this->UsuarioAlteracao?->only(['codusuario', 'usuario']) : null,
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }
}
