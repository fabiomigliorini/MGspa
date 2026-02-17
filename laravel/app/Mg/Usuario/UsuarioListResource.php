<?php

namespace Mg\Usuario;

use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioListResource extends JsonResource
{
    /**
     * Transform the resource into an array for listing purposes.
     * Only includes fields needed for the users listing screen.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $permissoes = $this->GrupoUsuarioUsuarioS
            ->groupBy('codgrupousuario')
            ->map(fn ($items) => [
                'codgrupousuario' => $items->first()->codgrupousuario,
                'grupousuario'    => $items->first()->GrupoUsuario->grupousuario,
            ])
            ->sortBy('grupousuario', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return [
            'codusuario' => $this->codusuario,
            'usuario'    => $this->usuario,
            'inativo'    => $this->inativo,
            'Pessoa'     => $this->codpessoa ? [
                'pessoa' => $this->Pessoa->pessoa ?? null,
            ] : null,
            'permissoes' => $permissoes,
        ];
    }
}
