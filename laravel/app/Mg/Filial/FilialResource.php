<?php

namespace Mg\Filial;

use Illuminate\Http\Resources\Json\JsonResource;
use Mg\Pessoa\PessoaResource;

class FilialResource extends JsonResource
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

        // Campos sensíveis - remover do response
        unset($ret['senhacertificado']);
        unset($ret['pagarmesk']);
        unset($ret['pagarmeid']);
        unset($ret['odbcnumeronotafiscal']);
        unset($ret['acbrnfemonitorbloqueado']);
        unset($ret['acbrnfemonitorcodusuario']);

        // Campos renomeados para o frontend
        $ret['tokennfce'] = $this->nfcetoken;
        $ret['idtokennfce'] = $this->nfcetokenid;
        $ret['tokenibpt'] = $this->tokenibpt;
        $ret['caminhomonitoracbr'] = $this->acbrnfemonitorcaminho;
        $ret['caminhoredeacbr'] = $this->acbrnfemonitorcaminhorede;
        $ret['acbrmonitorip'] = $this->acbrnfemonitorip;
        $ret['acbrmonitorporta'] = $this->acbrnfemonitorporta;
        unset($ret['nfcetoken']);
        unset($ret['nfcetokenid']);
        unset($ret['acbrnfemonitorcaminho']);
        unset($ret['acbrnfemonitorcaminhorede']);
        unset($ret['acbrnfemonitorip']);
        unset($ret['acbrnfemonitorporta']);

        // Relacionamentos com PascalCase (padrão do projeto)
        // parent::toArray() converte para snake_case, corrigimos aqui
        unset($ret['pessoa']);
        unset($ret['empresa']);
        if ($this->relationLoaded('Pessoa')) {
            $ret['Pessoa'] = $this->Pessoa ? [
                'codpessoa' => $this->Pessoa->codpessoa,
                'pessoa' => $this->Pessoa->pessoa,
                'fantasia' => $this->Pessoa->fantasia,
                'cnpj' => $this->Pessoa->cnpj,
                'ie' => $this->Pessoa->ie,
            ] : null;
        }
        if ($this->relationLoaded('Empresa')) {
            $ret['Empresa'] = $this->Empresa ? [
                'codempresa' => $this->Empresa->codempresa,
                'empresa' => $this->Empresa->empresa,
            ] : null;
        }

        return $ret;
    }
}
