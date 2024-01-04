<?php


namespace Mg\GrupoEconomico;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use Mg\Pessoa\PessoaResource;

class GrupoEconomicoResource extends JsonResource
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
        $ret['usuariocriacao'] = @$this->UsuarioCriacao->usuario;
        $ret['usuarioalteracao'] = @$this->UsuarioAlteracao->usuario;


        $ret['PessoasdoGrupo'] = PessoaResource::collection(@$this->PessoaS()
        ->where('codgrupoeconomico', @$this->codgrupoeconomico)->get());
       
        return $ret;
    }
}
