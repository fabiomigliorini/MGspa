<?php

namespace Mg\Pdv;

use Mg\Titulo\LiquidacaoTituloResource;

class PdvLiquidacaoController
{
    public function getLiquidacoes(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        $filtros = $request->all();
        unset($filtros['pdv']);
        unset($filtros['page']);
        unset($filtros['pesquisar']);
        $qry = PdvLiquidacaoService::queryPorLiquidacao($filtros);
        return LiquidacaoTituloResource::collection($qry->paginate(100));
    }
/*
    public function getLiquidacoesB(PdvRequest $request)
    {


    }
    */
}
