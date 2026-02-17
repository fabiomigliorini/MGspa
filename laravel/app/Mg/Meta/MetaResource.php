<?php

namespace Mg\Meta;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class MetaResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        $ret['unidades'] = $this->MetaUnidadeNegocioS->map(function ($unidade) {
            $unidadeArr = $unidade->toArray();
            $unidadeArr['descricao'] = $unidade->UnidadeNegocio->descricao ?? null;
            $unidadeArr['pessoas'] = MetaUnidadeNegocioPessoa::where('codmeta', $this->codmeta)
                ->where('codunidadenegocio', $unidade->codunidadenegocio)
                ->get()
                ->toArray();
            return $unidadeArr;
        });

        $ret['filiais'] = MetaService::vendasFilial($this->resource);
        $ret['vendedores'] = MetaService::vendasVendedor($this->resource);

        return $ret;
    }
}
