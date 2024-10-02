<?php

namespace Mg\Meta;

use Illuminate\Http\Resources\Json\JsonResource as Resource;


class MetaResource extends Resource
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
        $ret['filiais'] = MetaService::vendasFilial($this->resource);
        $ret['vendedores'] = MetaService::vendasVendedor($this->resource);

        // $ret['filiais'] = [];
        // foreach ($this->MetaFilialS as $fil) {
        //     $retFil = $fil->toArray();
        //     $retFil['filial'] = $fil->Filial->filial;
        //     $vendas = MetaService::vendasFilial($this->periodoinicial, $this->periodofinal);
        //     $retFil['vendas'] = $vendas;
        //     $retFil['valorvenda'] = $vendas->sum('valorvenda');
        //     $ret['filiais'][$fil->codfilial] = $retFil;
        // }

        return $ret;
    }

}
