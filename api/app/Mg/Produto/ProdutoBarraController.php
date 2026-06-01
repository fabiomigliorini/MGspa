<?php

namespace Mg\Produto;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mg\MgController;

class ProdutoBarraController extends MgController
{
    public function store(Request $request, $codproduto)
    {
        $produto = Produto::findOrFail($codproduto);

        $request->validate([
            'codprodutovariacao' => [
                'required',
                'numeric',
                Rule::exists('tblprodutovariacao', 'codprodutovariacao')->where('codproduto', $produto->codproduto),
            ],
            'codprodutoembalagem' => [
                'nullable',
                'numeric',
                Rule::exists('tblprodutoembalagem', 'codprodutoembalagem')->where('codproduto', $produto->codproduto),
            ],
            'barras' => ['nullable', 'unique:tblprodutobarra,barras', 'max:50'],
            'referencia' => ['nullable', 'max:50'],
        ], [
            'barras.unique' => 'Este código de barras já existe!',
        ]);

        $dados = $request->only(['codprodutovariacao', 'codprodutoembalagem', 'barras', 'referencia']);
        $dados['codproduto'] = $produto->codproduto;

        $pb = ProdutoBarraService::criar($dados);
        return new ProdutoBarraResource($pb);
    }

    public function update(Request $request, $codproduto, $codprodutobarra)
    {
        $produto = Produto::findOrFail($codproduto);
        $pb = ProdutoBarra::where('codproduto', $codproduto)->findOrFail($codprodutobarra);

        $request->validate([
            'codprodutovariacao' => [
                'required',
                'numeric',
                Rule::exists('tblprodutovariacao', 'codprodutovariacao')->where('codproduto', $produto->codproduto),
            ],
            'codprodutoembalagem' => [
                'nullable',
                'numeric',
                Rule::exists('tblprodutoembalagem', 'codprodutoembalagem')->where('codproduto', $produto->codproduto),
            ],
            'barras' => [
                'required',
                'max:50',
                Rule::unique('tblprodutobarra', 'barras')->ignore($pb->codprodutobarra, 'codprodutobarra'),
            ],
            'referencia' => ['nullable', 'max:50'],
        ], [
            'barras.unique' => 'Este código de barras já existe!',
        ]);

        $dados = $request->only(['codprodutovariacao', 'codprodutoembalagem', 'barras', 'referencia']);
        if (empty($dados['codprodutoembalagem'])) {
            $dados['codprodutoembalagem'] = null;
        }
        $pb->fill($dados);
        $pb->save();
        return new ProdutoBarraResource($pb);
    }

    public function destroy($codproduto, $codprodutobarra)
    {
        $pb = ProdutoBarra::where('codproduto', $codproduto)->findOrFail($codprodutobarra);
        $pb->delete();
        return response()->noContent();
    }
}
