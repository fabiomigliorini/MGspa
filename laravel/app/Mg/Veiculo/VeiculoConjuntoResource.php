<?php

namespace Mg\Veiculo;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class VeiculoConjuntoResource extends Resource
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
        $ret['veiculos'] = [];
        foreach ($this->VeiculoConjuntoVeiculoS as $vcv) {
            $ret['veiculos'][] = [
                'codveiculoconjuntoveiculo' => $vcv->codveiculoconjuntoveiculo,
                'codveiculo' => $vcv->codveiculo,
                'placa' => $vcv->Veiculo->placa,
                'tracao' => $vcv->Veiculo->VeiculoTipo->tracao,
                'reboque' => $vcv->Veiculo->VeiculoTipo->reboque,
            ];
        }
        return $ret;
    }
}
