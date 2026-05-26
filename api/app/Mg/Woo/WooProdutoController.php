<?php

namespace Mg\Woo;

use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Mg\Produto\Produto;

// use Mg\Woo\WooProdutoRequest;
// use Mg\Woo\WooProdutoResource;
// use Mg\Woo\WooProduto; 
// use Illuminate\Http\Request;

class WooProdutoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    // public function index(Request $request)
    // {
    //     // Add basic filtering/pagination here if needed
    //     $produtos = WooProduto::all();
    //     // Or with pagination: $produtos = WooProduto::paginate(10);

    //     return WooProdutoResource::collection($produtos);
    // }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\Mg\Woo\WooProdutoRequest  $request
     * @return \App\Http\Resources\Mg\Woo\WooProdutoResource
     */
    public function store(WooProdutoRequest $request)
    {
        $data = $request->validated();
        $produto = WooProduto::create($data);
        return new WooProdutoResource($produto);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \App\Http\Resources\Mg\Woo\WooProdutoResource
     */
    // public function show($id)
    // {
    //     $produto = WooProduto::findOrFail($id);

    //     return new WooProdutoResource($produto);
    // }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\Mg\Woo\WooProdutoRequest  $request
     * @param  int  $id
     * @return \App\Http\Resources\Mg\Woo\WooProdutoResource
     */
    public function update(WooProdutoRequest $request, $id)
    {
        $produto = WooProduto::findOrFail($id);
        $data = $request->validated();
        $produto->update($data);
        return new WooProdutoResource($produto);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    // public function destroy($id)
    // {
    //     $produto = WooProduto::findOrFail($id);
    //     $produto->delete();

    //     return response()->json(null, 204); // HTTP 204 No Content
    // }

    /**
     * Ativa o recurso, limpando o campo 'inativo'.
     *
     * @param  int  $id
     * @return \App\Http\Resources\Mg\Woo\WooProdutoResource
     */
    public function ativar($id)
    {
        $produto = WooProduto::findOrFail($id);

        // Define 'inativo' como NULL para ativar o produto
        if (!empty($produto->inativo)) {
            $produto->update(['inativo' => null]);
        }
        return new WooProdutoResource($produto);
    }

    /**
     * Inativa o recurso, preenchendo o campo 'inativo' com o timestamp atual.
     *
     * @param  int  $id
     * @return \App\Http\Resources\Mg\Woo\WooProdutoResource
     */
    public function inativar($id)
    {
        $produto = WooProduto::findOrFail($id);

        // Define 'inativo' como o timestamp atual
        if (empty($produto->inativo)) {
            $produto->update(['inativo' => Carbon::now()]);
        }

        return new WooProdutoResource($produto);
    }

    public function exportar($codproduto)
    {
        $produto = Produto::findOrFail($codproduto);
        $wps = new WooProdutoService($produto);
        $wps->exportar();
        $list = $produto->WooProdutoS;
        return WooProdutoResource::collection($list);
    }
}
