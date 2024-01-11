<?php

namespace Mg\PagarMe;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class PagarMePagamentoResource extends Resource
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
        $ret['bandeira'] = $this->PagarMeBandeira->bandeira??null;
        $ret['pos'] = $this->PagarMePos->serial??null;
        $ret['apelido'] = $this->PagarMePos->apelido??null;
        $ret['tipodescricao'] = PagarMeService::TYPE_DESCRIPTION[$this->tipo]??null;
        // $ret['PagarMePagamentoS'] = PagarMePagamentoResource::collection($this->PagarMePagamentoS()->orderBy('criacao', 'desc')->get());
        return $ret;
    }
}
