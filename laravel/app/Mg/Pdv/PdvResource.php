<?php

namespace Mg\Pdv;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class PdvResource extends JsonResource
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

        // Chave Extrangeira
        $ret['filial'] = @$this->Filial->filial;

        return $ret;
    }
}
