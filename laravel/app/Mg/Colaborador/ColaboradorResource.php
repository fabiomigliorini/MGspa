<?php


namespace Mg\Colaborador;

use Illuminate\Http\Resources\Json\JsonResource;

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
        $ret['Ferias'] = $this->FeriasS()->orderBy('aquisitivofim', 'desc')->get();
        $ret['Filial'] = @$this->Filial->filial;
        $ret['ColaboradorCargo'] = ColaboradorCargoResource::collection($this->ColaboradorCargoS()->orderBy('inicio', 'desc')->get());
        return $ret;
    }
}
