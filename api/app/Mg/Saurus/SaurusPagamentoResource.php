<?php

namespace Mg\Saurus;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class SaurusPagamentoResource extends Resource
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
        // pinpad e pedido carregados antes levam o PDV Saurus, com as chaves dele: nao saem
        unset($ret['saurus_pin_pad'], $ret['saurus_pedido']);
        $ret['bandeira'] = $this->SaurusBandeira->bandeira??null;
        // nome e serial sao da maquineta do PDV Saurus do pedido (TASK-264)
        $maquineta = $this->SaurusPedido->SaurusPdv->Maquineta ?? null;
        $ret['pos'] = $maquineta->serial ?? null;
        $ret['apelido'] = $maquineta->apelido ?? null;
        $ret['tipodescricao'] = SaurusService::TYPE_DESCRIPTION[$this->modpagamento]??null;
      
        return $ret;
    }
}
