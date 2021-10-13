<?php

namespace Mg\Portador;

use Illuminate\Http\Resources\Json\Resource;

class PortadorResource extends Resource
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
        unset($ret['bbdevappkey']);
        unset($ret['bbclientid']);
        unset($ret['bbclientsecret']);
        unset($ret['bbtoken']);
        unset($ret['bbtokenexpiracao']);
        $ret['extratoconciliar'] = $this->ExtratoBancarioS()->where('conciliado', false)->count();
        $ret['movimentoconciliar'] = $this->PortadorMovimentoS()->where('conciliado', false)->count();
        return $ret;
    }
}
