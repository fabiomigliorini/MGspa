<?php

namespace Mg\NaturezaOperacao\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CfopDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codcfop' => $this->codcfop,
            'descricao' => $this->cfop,
            'usuarioCriacao' => $this->relationLoaded('UsuarioCriacao') ? $this->getRelationValue('UsuarioCriacao')?->only(['codusuario', 'usuario']) : null,
            'usuarioAlteracao' => $this->relationLoaded('UsuarioAlteracao') ? $this->getRelationValue('UsuarioAlteracao')?->only(['codusuario', 'usuario']) : null,
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }
}
