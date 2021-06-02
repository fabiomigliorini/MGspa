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


    /*
    public function store(Request $request)
    {
        $request->validate([
          'codfilial' => ['required', 'integer'],
          'stonecode' => ['required', 'integer'],
          'chaveprivada' => ['required', 'string'],
        ]);
        $stoneFilial = StoneFilialService::create($request->codfilial, $request->stonecode, $request->chaveprivada);
        return new StoneFilialResource($stoneFilial);
    }

    public function showWebhook(Request $request, $codstonefilial)
    {
        $stoneFilial = StoneFilial::findOrFail($codstonefilial);
        $weebhooks = StoneFilialService::consultaWebhook($stoneFilial);
        return $weebhooks;
    }

    public function index(Request $request)
    {
        $stoneFiliais = StoneFilial::orderBy('codfilial')->get();
        return StoneFilialResource::collection($stoneFiliais);
    }
    */
}
