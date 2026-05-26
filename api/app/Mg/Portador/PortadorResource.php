<?php

namespace Mg\Portador;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

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
        $ret['extratoconciliar'] = $this->ExtratoBancarioS()->where('conciliado', false)->count();
        $ret['movimentoconciliar'] = $this->PortadorMovimentoS()->where('conciliado', false)->count();
        $ret['banco'] = $this->whenLoaded('Banco', function () {
            return optional($this->Banco)->banco;
        });
        $ret['filial'] = $this->whenLoaded('Filial', function () {
            return optional($this->Filial)->filial;
        });
        return $ret;
    }
}
