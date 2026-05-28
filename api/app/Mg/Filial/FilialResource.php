<?php

namespace Mg\Filial;

use Illuminate\Http\Resources\Json\JsonResource;

class FilialResource extends JsonResource
{
    public function toArray($request): array
    {
        $ret = parent::toArray($request);

        unset(
            $ret['senhacertificado'],
            $ret['pagarmesk'],
            $ret['pagarmeid'],
            $ret['odbcnumeronotafiscal'],
            $ret['acbrnfemonitorbloqueado'],
            $ret['acbrnfemonitorcodusuario'],
        );

        $ret['tokennfce'] = $this->nfcetoken;
        $ret['idtokennfce'] = $this->nfcetokenid;
        $ret['tokenibpt'] = $this->tokenibpt;
        $ret['caminhomonitoracbr'] = $this->acbrnfemonitorcaminho;
        $ret['caminhoredeacbr'] = $this->acbrnfemonitorcaminhorede;
        $ret['acbrmonitorip'] = $this->acbrnfemonitorip;
        $ret['acbrmonitorporta'] = $this->acbrnfemonitorporta;
        unset(
            $ret['nfcetoken'],
            $ret['nfcetokenid'],
            $ret['acbrnfemonitorcaminho'],
            $ret['acbrnfemonitorcaminhorede'],
            $ret['acbrnfemonitorip'],
            $ret['acbrnfemonitorporta'],
        );

        unset($ret['pessoa'], $ret['empresa']);
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
