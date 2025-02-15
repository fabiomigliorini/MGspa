<?php

namespace Mg\Saurus;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class SaurusPedidoResource extends Resource
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
        $ret['pos'] = SaurusPinPad::select('serial')->where('codsauruspdv', $this->codsauruspdv)->first()->serial??null;
        $ret['apelido'] = $this->SaurusPdv->apelido??null;
        $ret['statusdescricao'] = SaurusService::STATUS_DESCRIPTION[$this->status]??null;
        $ret['tipodescricao'] = SaurusService::TYPE_DESCRIPTION[$this->modpagamento]??null;
        $ret['SaurusPagamentoS'] = SaurusPagamentoResource::collection(
            $this->SaurusPagamentoS()->orderBy('criacao', 'desc')->get()
        );
        return $ret;
    }
}
