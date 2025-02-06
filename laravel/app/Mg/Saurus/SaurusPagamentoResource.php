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
        $ret['bandeira'] = $this->SaurusBandeira->bandeira??null;
        $ret['pos'] = $this->SaurusPinPad->serial??null;
        $ret['apelido'] = $this->SaurusPinPad->apelido??null;
        $ret['tipodescricao'] = SaurusService::TYPE_DESCRIPTION[$this->modpagamento]??null;
      
        return $ret;
    }
}
