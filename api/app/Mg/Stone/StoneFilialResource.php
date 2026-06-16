<?php

namespace Mg\Stone;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class StoneFilialResource extends Resource
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
        $ret['filial'] = $this->Filial->filial;
        $ret['cnpj'] = $this->Filial->Pessoa->cnpj;

        $ret['StonePosS'] = [];
        foreach ($this->StonePosS as $stonePos) {
            $retStonePos = $stonePos->toArray();
            $ret['StonePosS'][] = $retStonePos;
        }

        return $ret;
    }
}
