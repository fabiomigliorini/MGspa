<?php


namespace Mg\Colaborador;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ColaboradorResource extends JsonResource
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
        $ret['Ferias'] = $this->FeriasS()->orderBy('criacao')->get();
        $ret['Filial'] = @$this->Filial->filial;
        $ret['ColaboradorCargo'] = ColaboradorCargoResource::collection($this->ColaboradorCargoS()->orderBy('criacao')->get());

        return $ret;
    }
}
