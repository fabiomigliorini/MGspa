<?php

namespace Mg\Usuario;

use Illuminate\Http\Resources\Json\JsonResource;

class GrupoUsuarioResource extends JsonResource
{
    public function toArray($request): array
    {
        $ret = parent::toArray($request);

        $guus = GrupoUsuarioUsuario::whereHas('Usuario', function ($query) {
                $query->whereNull('inativo');
            })
            ->where('codgrupousuario', $this->codgrupousuario)
            ->with(['Usuario:codusuario,usuario', 'Filial:codfilial,filial'])
            ->orderBy('codfilial')
            ->get();

        $usuarios = collect([]);
        foreach ($guus as $guu) {
            if (!isset($usuarios[$guu->codusuario])) {
                $usuarios[$guu->codusuario] = (object) [
                    'codusuario' => $guu->codusuario,
                    'usuario' => $guu->Usuario->usuario ?? null,
                    'filiais' => [],
                ];
            }
            $usuarios[$guu->codusuario]->filiais[] = [
                'codfilial' => $guu->codfilial,
                'filial' => $guu->Filial->filial ?? null,
            ];
        }

        $ret['Usuarios'] = $usuarios->sortBy('usuario', SORT_NATURAL | SORT_FLAG_CASE)->toArray();

        return $ret;
    }
}
