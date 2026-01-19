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
            'usuarioCriacao' => $this->formatUsuarioCriacao(),
            'usuarioAlteracao' => $this->formatUsuarioAlteracao(),

            // Timestamps
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }

    private function formatUsuarioCriacao(): ?array
    {
        if (!$this->relationLoaded('UsuarioCriacao')) {
            return null;
        }

        return $this->UsuarioCriacao?->only(['codusuario', 'usuario']);
    }

    private function formatUsuarioAlteracao(): ?array
    {
        if (!$this->relationLoaded('UsuarioAlteracao')) {
            return null;
        }

        return $this->UsuarioAlteracao?->only(['codusuario', 'usuario']);
    }
}
