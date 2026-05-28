<?php

namespace Mg\Pix;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class PixResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $retPix = parent::toArray($request);
        $retPix['PixDevolucaoS'] = [];
        foreach ($this->PixDevolucaoS()->get() as $dev) {
            $retDev = $dev->toArray();
            $retDev['status'] = $dev->PixDevolucaoStatus->pixdevolucaostatus;
            $retPix['PixDevolucaoS'][] = $retDev;
        }
        return $retPix;
    }
}
