<?php

namespace Mg\Negocio;

use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Mg\Pdv\PdvService;

class NegocioFormaPagamentoResource extends Resource
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
        $ret['formapagamento'] = $this->FormaPagamento->formapagamento;
        $ret['parceiro'] = $this->Pessoa->fantasia??null;
        $ret['nomebandeira'] = NegocioFormaPagamentoService::BANDEIRAS[$ret['bandeira']]?? null;
        $ret['nometipo'] = NegocioFormaPagamentoService::TIPOS[$ret['tipo']]?? null;
        return $ret;
    }
}
