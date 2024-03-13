<?php

namespace Mg\Usuario;

use Illuminate\Http\Resources\Json\JsonResource;
use Mg\Pessoa\PessoaResource;

class GrupoUsuarioResource extends JsonResource
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

        // Busca no banco todos os relacionamentos com usuarios ativos
        $guus = GrupoUsuarioUsuario::with(['usuario' => function ($query) {
            $query->whereNull('inativo');
        }])->where('codgrupousuario', $this->codgrupousuario)->orderBy('codfilial')->get();

        // inicializa uma collection com os usuarios
        $usuarios = collect([]);

        // percorre os relacionamentos
        foreach ($guus as $guu) {

            // se e a primeira vez que passa pelo usuario
            if (!isset($usuarios[$guu->codusuario])) {
                $usuarios[$guu->codusuario] = (object) [
                    'codusuario' => $guu->codusuario,
                    'usuario' => $guu->Usuario->usuario,
                    'filiais' => [],
                ];
            }

            // adiciona a filial
            $usuarios[$guu->codusuario]->filiais[] = [
                'codfilial' => $guu->codfilial,
                'filial' => $guu->Filial->filial
            ];
        }

        // ordena alfabetico
        $ret['Usuarios'] = $usuarios->sortBy('usuario', SORT_NATURAL | SORT_FLAG_CASE)->toArray();

        return $ret;
    }
}
