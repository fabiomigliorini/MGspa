<?php

namespace Mg\Estoque;

use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Marca\MarcaRepository;
use Mg\Produto\ProdutoRepository;
use Mg\Estoque\EstoqueSaldoConferencia;

class EstoqueConferenciaController extends MgController
{
    /**
     * Traz a Listagem dos Produtos com os detalhes das conferências
     * para uma determinada Marca, num determinado Local, na dimensão
     * Físico ou Fiscal
     * @return \Illuminate\Http\Response
     */
    public function buscaListagem(Request $request)
    {
        $res = EstoqueSaldoConferenciaRepository::buscaListagem(
            $request->codmarca,
            $request->codestoquelocal,
            $request->fiscal
        );

        return response()->json($res, 206);
    }

    /**
     * Traz os detalhes do Produto e a listagem das conferências
     * que já foram feitas para o Produto.
     * Pode buscar o Produto pelo codvariacao ou pelo código de barras
     */
    public function buscaProduto(Request $request)
    {
        $codprodutovariacao = $request->codprodutovariacao;
        if (empty($codprodutovariacao)) {
            $pb  = \Mg\Produto\ProdutoRepository::buscaPorBarras($request->barras);
            $codprodutovariacao = $pb->codprodutovariacao;
        }

        $res = EstoqueSaldoConferenciaRepository::buscaProduto($codprodutovariacao, $request->codestoquelocal, $request->fiscal);

        return response()->json($res, 206);
    }


    public function store(Request $request)
    {

        $request->validate([
            'codprodutovariacao' => 'required|numeric',
            'codestoquelocal' => 'required|numeric',
            'fiscal' => 'required|boolean',
            'quantidadeinformada' => 'required|numeric',
            'customedioinformado' => 'required|numeric',
            'data' => 'required|date',
        ]);

        $data = new \Carbon\Carbon($request->data);
        $vencimento = new \Carbon\Carbon($request->data);
        $fiscal = boolval($request->fiscal);
        $quantidadeinformada = floatval($request->quantidadeinformada);
        $customedioinformado = floatval($request->customedioinformado);


        $model = EstoqueSaldoConferenciaRepository::criaConferencia(
            $request->codprodutovariacao,
            $request->codestoquelocal,
            $fiscal,
            $quantidadeinformada,
            $customedioinformado,
            $data,
            $request->observacoes,
            $request->corredor,
            $request->prateleira,
            $request->coluna,
            $request->bloco,
            $vencimento,
            $request->$estoquemaximo,
            $request->$estoqueminimo
        );

        return response()->json($model, 201);
    }

    public function inativar(Request $request, $id) {
        $model = EstoqueSaldoConferencia::findOrFail($id);
        $model = EstoqueSaldoConferenciaRepository::inativar($model);

        return response()->json($model, 200);
    }

}
