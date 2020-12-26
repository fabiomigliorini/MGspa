<?php

namespace Mg\Pix;

use Illuminate\Http\Resources\Json\Resource;

class PixCobResource extends Resource
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
        $ret['status'] = $this->PixStatus->pixstatus;
        $ret['brcode'] = BrCodeService::montar($this);
        return $ret;
    }
}
