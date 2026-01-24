<?php

namespace Mg\Cidade\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CidadeDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codcidade' => $this->codcidade,
            'codestado' => $this->codestado,
            'cidade' => $this->cidade,
            'sigla' => $this->sigla,
            'codigooficial' => $this->codigooficial,

            // Relacionamentos
            'estado' => $this->formatEstado(),

            // Auditoria
            'usuarioCriacao' => $this->formatUsuarioCriacao(),
            'usuarioAlteracao' => $this->formatUsuarioAlteracao(),

            // Timestamps
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }

    private function formatEstado(): ?array
    {
        if (!$this->relationLoaded('Estado')) {
            return null;
        }

        $estado = $this->Estado;
        if (!$estado) {
            return null;
        }

        $ret = $estado->only(['codestado', 'estado', 'sigla', 'codpais']);

        if ($estado->relationLoaded('Pais') && $estado->Pais) {
            $ret['pais'] = $estado->Pais->only(['codpais', 'pais', 'sigla']);
        }

        return $ret;
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
