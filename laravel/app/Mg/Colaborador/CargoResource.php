<?php


namespace Mg\Colaborador;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class CargoResource extends JsonResource
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
        
        return $ret;
    }
}
