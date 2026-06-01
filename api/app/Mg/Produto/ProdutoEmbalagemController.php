<?php

namespace Mg\Produto;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mg\MgController;

class ProdutoEmbalagemController extends MgController
{
    public function store(Request $request, $codproduto)
    {
        $produto = Produto::findOrFail($codproduto);

        $this->validar($request, $produto);

        $dados = $request->only([
            'codunidademedida', 'quantidade', 'preco',
            'altura', 'largura', 'profundidade', 'peso',
        ]);
        $dados['codproduto'] = $produto->codproduto;

        $pe = ProdutoEmbalagemService::criar($dados);
        return new ProdutoEmbalagemResource($pe);
    }

    public function update(Request $request, $codproduto, $codprodutoembalagem)
    {
        $produto = Produto::findOrFail($codproduto);
        $pe = ProdutoEmbalagem::where('codproduto', $codproduto)->findOrFail($codprodutoembalagem);

        $this->validar($request, $produto, $pe->codprodutoembalagem);

        $dados = $request->only([
            'codunidademedida', 'quantidade', 'preco',
            'altura', 'largura', 'profundidade', 'peso',
        ]);
        $pe = ProdutoEmbalagemService::atualizar($pe, $dados);
        return new ProdutoEmbalagemResource($pe);
    }

    public function destroy($codproduto, $codprodutoembalagem)
    {
        $pe = ProdutoEmbalagem::where('codproduto', $codproduto)->findOrFail($codprodutoembalagem);
        $pe->delete();
        return response()->noContent();
    }

    private function validar(Request $request, Produto $produto, $codprodutoembalagem = null)
    {
        $unico = Rule::unique('tblprodutoembalagem', 'quantidade')
            ->where('codproduto', $produto->codproduto);
        if ($codprodutoembalagem) {
            $unico->ignore($codprodutoembalagem, 'codprodutoembalagem');
        }

        $quantidade = (float) $request->quantidade;
        $base = (float) $produto->preco * $quantidade;

        $request->validate([
            'codunidademedida' => ['required', 'numeric', 'exists:tblunidademedida,codunidademedida'],
            'quantidade' => ['required', 'numeric', 'gt:0', $unico],
            'preco' => [
                'nullable',
                'numeric',
                function ($attribute, $value, $fail) use ($base) {
                    if (empty($value) || $base <= 0) {
                        return;
                    }
                    if ($value > $base) {
                        $fail('O preço da embalagem não pode ser maior que o preço unitário × quantidade.');
                    }
                    if ($value < $base * 0.5) {
                        $fail('O preço da embalagem não pode ser menor que 50% do preço unitário × quantidade.');
                    }
                },
            ],
            'altura' => ['nullable', 'numeric'],
            'largura' => ['nullable', 'numeric'],
            'profundidade' => ['nullable', 'numeric'],
            'peso' => ['nullable', 'numeric'],
        ], [
            'quantidade.unique' => 'Já existe uma embalagem com esta quantidade neste produto',
            'quantidade.gt' => 'A quantidade deve ser maior que zero',
        ]);
    }
}
