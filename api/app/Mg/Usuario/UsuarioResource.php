<?php

namespace Mg\Usuario;

use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    public function toArray($request): array
    {
        $ret = parent::toArray($request);
        unset($ret['senha'], $ret['remember_token']);

        if ($this->codpessoa) {
            $ret['Pessoa'] = $this->Pessoa?->only([
                'codpessoa',
                'pessoa',
                'fantasia',
                'cnpj',
                'cpf',
                'email',
            ]);
        }

        $ret['portador'] = $this->Portador->portador ?? null;

        $guus = GrupoUsuarioUsuario::with(['GrupoUsuario', 'Filial'])
            ->where('codusuario', $this->codusuario)
            ->orderBy('codfilial')
            ->get();

        $usuarios = collect([]);
        foreach ($guus as $guu) {
            if (!isset($usuarios[$guu->codgrupousuario])) {
                $usuarios[$guu->codgrupousuario] = (object) [
                    'codgrupousuariousuario' => $guu->codgrupousuariousuario,
                    'grupousuario' => $guu->GrupoUsuario->grupousuario,
                    'codgrupousuario' => $guu->codgrupousuario,
                    'filiais' => [],
                ];
            }
            $usuarios[$guu->codgrupousuario]->filiais[] = [
                'codfilial' => $guu->codfilial,
                'filial' => $guu->Filial->filial ?? null,
            ];
        }

        $ret['permissoes'] = $usuarios->sortBy('grupousuario', SORT_NATURAL | SORT_FLAG_CASE)->toArray();

        if ($this->codfilial) {
            $ret['filial'] = $this->Filial->filial ?? null;
        }

        return $ret;
    }
}
