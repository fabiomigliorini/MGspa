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

            // Relacionamento
            'pais' => $this->formatPais(),

            // Auditoria
            'usuarioCriacao' => $this->formatUsuarioCriacao(),
            'usuarioAlteracao' => $this->formatUsuarioAlteracao(),

            // Timestamps
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }

    private function formatPais(): ?array
    {
        if (!$this->relationLoaded('Pais')) {
            return null;
        }

        return $this->Pais?->only(['codpais', 'pais', 'sigla']);
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
