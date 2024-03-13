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

        unset($ret['senhacertificado']);
        unset($ret['pagarmesk']);
        unset($ret['pagarmeid']);
        unset($ret['tokenibpt']);
        unset($ret['nfcetokenid']);
        unset($ret['nfcetoken']);
        unset($ret['odbcnumeronotafiscal']);
        unset($ret['acbrnfemonitorcaminho']);
        unset($ret['acbrnfemonitorcaminhorede']);
        unset($ret['acbrnfemonitorbloqueado']);
        unset($ret['acbrnfemonitorcodusuario']);
        unset($ret['acbrnfemonitorip']);
        unset($ret['acbrnfemonitorporta']);

        
        return $ret;
    }
}
