<?php

namespace Mg\Usuario;

use Illuminate\Http\Resources\Json\JsonResource;
use Mg\Pessoa\PessoaResource;

class UsuarioResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $ret = parent::toArray($request);
        unset($ret['senha']);
        unset($ret['remember_token']);

        if ($this->codpessoa) {
            $ret['Pessoa'] = new PessoaResource($this->Pessoa);
        }

        $guus = GrupoUsuarioUsuario::with(['grupousuario' => function ($query) {
        }])->where('codusuario', $this->codusuario)->orderBy('codfilial')->get();
       
        $usuarios = collect([]);

        // percorre os relacionamentos
        foreach ($guus as $guu) {
        
            // se e a primeira vez que passa pelo grupo do usuario
            if (!isset($usuarios[$guu->codgrupousuario])) {
                $usuarios[$guu->codgrupousuario] = (object) [
                    'codgrupousuariousuario' => $guu->codgrupousuariousuario,
                    'grupousuario' => $guu->GrupoUsuario->grupousuario,
                    'codgrupousuario' => $guu->codgrupousuario,
                    'filiais' => [],
                ];
            }

            // adiciona a filial
            $usuarios[$guu->codgrupousuario]->filiais[] = [
                'codfilial' => $guu->codfilial,
                'filial' => $guu->Filial->filial
            ];
        }

        $ret['permissoes'] = $usuarios->sortBy('grupousuario', SORT_NATURAL | SORT_FLAG_CASE)->toArray();

        $ret['filial'] = $this->Filial->filial;
        return $ret;
    }
}
