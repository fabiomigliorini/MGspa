<?php

namespace Mg\Produto;

use Illuminate\Http\Request;

use Mg\MgController;

class ProdutoController extends MgController
{

    public function show(Request $request, $codproduto)
    {
        $produto = Produto::findOrFail($codproduto);
        return new ProdutoResource($produto);
    }

    public function unificaVariacoes(Request $request)
    {
        $request->validate([
            'codprodutovariacaoorigem' => 'int|required',
            'codprodutovariacaodestino' => 'int|required',
        ]);
        $pv = ProdutoVariacaoService::unificaVariacoes(
            $request->codprodutovariacaoorigem,
            $request->codprodutovariacaodestino
        );
        return new ProdutoVariacaoResource($pv);
    }

    public function unificaBarras(Request $request)
    {
        $request->validate([
            'codprodutobarraorigem' => 'int|required',
            'codprodutobarradestino' => 'int|required',
        ]);
        $pb = ProdutoBarraService::unificaBarras(
            $request->codprodutobarraorigem,
            $request->codprodutobarradestino
        );
        return new ProdutoBarraResource($pb);
    }

    public function embalagemParaUnidade(Request $request)
    {
        $request->validate([
            'codprodutoembalagem' => 'int|required',
        ]);
        $pe = ProdutoEmbalagemService::embalagemParaUnidade(
            $request->codprodutoembalagem
        );
        return new ProdutoEmbalagemResource($pe);
    }

    public function listagemPdv (Request $request)
    {
        $codprodutobarra = $request->codprodutobarra??0;
        $limite = $request->limite??10000;
        return ProdutoService::listagemPdv($codprodutobarra, $limite);
    }


}
