<?php


namespace Mg\Colaborador;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use Mg\Pessoa\PessoaGoogleDriveService;

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

        // TODO: Colocar essa logica no Colaborador::create
        // salvar URL da Pasta no Colaborador.folderurl
        $drive = new PessoaGoogleDriveService();
        $result = $drive->createColaboradorFolder(
            $this->Filial->Empresa->empresa,
            $this->Pessoa->pessoa
        );
        $ret['drive'] = $result;


        return $ret;
    }
}
