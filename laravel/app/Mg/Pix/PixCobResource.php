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
        $ret['status'] = $this->PixCobStatus->pixcobstatus;
        $ret['pessoa'] = null;
        if (!empty($this->codnegocio)) {
            if ($this->Negocio->codpessoa != 1) {
                $ret['pessoa'] = $this->Negocio->Pessoa->fantasia;
            }
        }
        $ret['PixS'] = [];
        foreach ($this->PixS as $pix) {
            $retPix = $pix->toArray();
            $retPix['PixDevolucaoS'] = [];
            foreach ($pix->PixDevolucaoS()->get() as $dev) {
                $retDev = $dev->toArray();
                $retDev['status'] = $dev->PixDevolucaoStatus->pixdevolucaostatus;
                $retPix['PixDevolucaoS'][] = $retDev;
            }
            $ret['PixS'][] = $retPix;
        }
        return $ret;
    }
}
