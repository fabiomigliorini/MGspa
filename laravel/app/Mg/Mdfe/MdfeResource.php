<?php

namespace Mg\Mdfe;

use Illuminate\Http\Resources\Json\Resource;

class MdfeResource extends Resource
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
        $ret['filial'] = $this->Filial->filial;
        $ret['cidadecarregamento'] = $this->CidadeCarregamento->cidade;
        $ret['estadofim'] = $this->EstadoFim->estado;
        $ret['mdfestatussigla'] = $this->MdfeStatus->sigla;
        $ret['mdfestatus'] = $this->MdfeStatus->mdfestatus;
        $ret['usuariocriacao'] = $this->codusuariocriacao?$this->UsuarioCriacao->usuario:null;
        $ret['usuarioalteracao'] = $this->codusuarioalteracao?$this->UsuarioAlteracao->usuario:null;

        $ret['MdfeEstadoS'] = [];
        foreach ($this->MdfeEstadoS as $mdfeEstado) {
            $retEstado = $mdfeEstado->toArray();
            $retEstado['estado'] = $mdfeEstado->Estado->estado;
            $ret['MdfeEstadoS'][] = $retEstado;
        }

        $ret['MdfeVeiculoS'] = [];
        foreach ($this->MdfeVeiculoS as $mdfeVeiculo) {
            $retVeiculo = $mdfeVeiculo->toArray();
            $retVeiculo['placa'] = $mdfeVeiculo->Veiculo->placa;
            $retVeiculo['proprietario'] = null;
            if (!empty($mdfeVeiculo->Veiculo->codpessoaproprietario)) {
                $retVeiculo['proprietario'] = $mdfeVeiculo->Veiculo->PessoaProprietario->fantasia;
            }
            $retVeiculo['condutor'] = null;
            if (!empty($mdfeVeiculo->codpessoacondutor)) {
                $retVeiculo['condutor'] = $mdfeVeiculo->PessoaCondutor->fantasia;
            }
            $ret['MdfeVeiculoS'][] = $retVeiculo;
        }

        $ret['MdfeNfeS'] = [];
        foreach ($this->MdfeNfeS as $mdfeNfe) {
            $retNfe = $mdfeNfe->toArray();
            $retNfe['cidadedescarga'] = (!empty($mdfeNfe->codcidadedescarga))?$mdfeNfe->CidadeDescarga->cidade:null;
            $ret['MdfeNfeS'][] = $retNfe;
        }

        $ret['MdfeEnvioSefazS'] = [];
        foreach ($this->MdfeEnvioSefazS as $envioSefaz) {
            $retEnvioSefaz = $envioSefaz->toArray();
            $ret['MdfeEnvioSefazS'][] = $retEnvioSefaz;
        }

        return $ret;
    }
}
