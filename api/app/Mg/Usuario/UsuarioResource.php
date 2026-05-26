<?php

namespace Mg\Usuario;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource pro endpoint v1/auth/user — preserva o contrato que os 4
 * frontends Quasar (pessoas/notas/contas/negocios) consomem em
 * `validarToken()`:
 *
 *  - permissoes: lista de grupos × filiais
 *  - Pessoa: dados básicos (raw model — PessoaResource original do
 *    legacy tem dependências não migradas; quando Pessoa for portada
 *    inteira, trocar pra `new PessoaResource($this->Pessoa)`)
 *  - portador / filial: strings descritivas dos vínculos
 */
class UsuarioResource extends JsonResource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        unset($ret['senha']);
        unset($ret['remember_token']);

        if ($this->codpessoa) {
            $ret['Pessoa'] = $this->Pessoa;
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
                    'grupousuario' => $guu->GrupoUsuario->grupousuario ?? null,
                    'codgrupousuario' => $guu->codgrupousuario,
                    'filiais' => [],
                ];
            }

            $usuarios[$guu->codgrupousuario]->filiais[] = [
                'codfilial' => $guu->codfilial,
                'filial' => $guu->Filial->filial ?? null,
            ];
        }

        $ret['permissoes'] = $usuarios->sortBy('grupousuario', SORT_NATURAL | SORT_FLAG_CASE)->values()->toArray();

        if ($this->codfilial) {
            $ret['filial'] = $this->Filial->filial ?? null;
        }

        return $ret;
    }
}
