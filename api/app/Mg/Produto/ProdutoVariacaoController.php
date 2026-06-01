<?php

namespace Mg\Produto;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mg\MgController;

class ProdutoVariacaoController extends MgController
{
    public function store(Request $request, $codproduto)
    {
        $produto = Produto::findOrFail($codproduto);
        $dados = $request->all();
        $dados['codproduto'] = $produto->codproduto;

        $this->validar($request, $produto, $dados);

        $pv = ProdutoVariacaoService::criar($dados);
        return new ProdutoVariacaoResource($pv->load('ProdutoBarraS'));
    }

    public function update(Request $request, $codproduto, $codprodutovariacao)
    {
        $produto = Produto::findOrFail($codproduto);
        $pv = ProdutoVariacao::where('codproduto', $codproduto)->findOrFail($codprodutovariacao);

        $this->validar($request, $produto, $request->all(), $pv->codprodutovariacao);

        $pv->fill($request->only(['variacao', 'codmarca', 'referencia']));
        $pv->save();
        return new ProdutoVariacaoResource($pv->load('ProdutoBarraS'));
    }

    public function destroy($codproduto, $codprodutovariacao)
    {
        $pv = ProdutoVariacao::where('codproduto', $codproduto)->findOrFail($codprodutovariacao);
        $pv->delete();
        return response()->noContent();
    }

    public function descontinuar(Request $request, $codproduto, $codprodutovariacao)
    {
        $pv = ProdutoVariacao::where('codproduto', $codproduto)->findOrFail($codprodutovariacao);
        $pv = ProdutoVariacaoService::descontinuar($pv);
        return new ProdutoVariacaoResource($pv->load('ProdutoBarraS'));
    }

    public function reativar(Request $request, $codproduto, $codprodutovariacao)
    {
        $pv = ProdutoVariacao::where('codproduto', $codproduto)->findOrFail($codprodutovariacao);
        $pv = ProdutoVariacaoService::reativar($pv);
        return new ProdutoVariacaoResource($pv->load('ProdutoBarraS'));
    }

    private function validar(Request $request, Produto $produto, array $dados, $codprodutovariacao = null)
    {
        $unico = Rule::unique('tblprodutovariacao', 'variacao')
            ->where('codproduto', $produto->codproduto);
        if ($codprodutovariacao) {
            $unico->ignore($codprodutovariacao, 'codprodutovariacao');
        }

        $request->validate([
            'variacao' => ['nullable', 'max:100', $unico],
            'codmarca' => ['nullable', 'numeric', Rule::notIn([$produto->codmarca])],
            'referencia' => ['nullable', 'max:50'],
        ], [
            'variacao.unique' => 'Já existe esta variação neste produto',
            'codmarca.not_in' => 'A marca da variação deve ser diferente da marca do produto',
        ]);
    }
}
