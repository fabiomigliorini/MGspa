<?php

namespace Mg\Estoque;

use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Marca\MarcaRepository;
use Mg\Produto\ProdutoRepository;
use Mg\Estoque\EstoqueSaldoConferencia;

use DB;

class EstoqueSaldoConferenciaController extends MgController
{
    /**
     * Traz a Listagem dos Produtos com os detalhes das conferências
     * para uma determinada Marca, num determinado Local, na dimensão
     * Físico ou Fiscal
     * @return \Illuminate\Http\Response
     */
    public function buscaListagem(Request $request)
    {

        $request->validate([
            'codmarca' => 'required|integer',
            'codestoquelocal' => 'required|integer',
            'fiscal' => 'required|boolean',
            'inativo' => 'required|integer',
            'conferidos' => 'required|boolean',
            'dataCorte' => 'required|date',
        ]);

        $codmarca =intval($request->codmarca);
        $codestoquelocal =intval($request->codestoquelocal);
        $fiscal = boolval($request->fiscal);
        $inativo = intval($request->inativo);
        $conferidos = boolval($request->conferidos);
        $dataCorte = new \Carbon\Carbon($request->dataCorte);

        $res = EstoqueSaldoConferenciaRepository::buscaListagem(
            $codmarca,
            $codestoquelocal,
            $fiscal,
            $inativo,
            $dataCorte,
            $conferidos,
            $request->page??1
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
            if (!$pb  = \Mg\Produto\ProdutoRepository::buscaPorBarras($request->barras)) {
                return response()->json([
                    'erro' => true,
                    'mensagem' => 'Código de Barras não localizado!'
                ], 206);
                abort(404);
            }
            $codprodutovariacao = $pb->codprodutovariacao;
        }

        $res = EstoqueSaldoConferenciaRepository::buscaProduto($codprodutovariacao, $request->codestoquelocal, $request->fiscal);

        return response()->json($res, 206);
    }

    /**
     * Cria o registro de Conferencia do Estoque e faz a movimentação
     * na tabela de tblestoquemovimento para que o saldo do ssitema seja
     * ajustado para o saldo informado pelo usuario
     */
    public function store(Request $request)
    {

        //dd($request->all());

        $request->validate([
            'codprodutovariacao' => 'required|numeric',
            'codestoquelocal' => 'required|numeric',
            'fiscal' => 'required|boolean',
            'quantidadeinformada' => 'required|numeric',
            'customedioinformado' => 'required|numeric',
            'data' => 'required|date',

            'corredor' => 'nullable|integer',
            'prateleira' => 'nullable|integer',
            'coluna' => 'nullable|integer',
            'bloco' => 'nullable|integer',

            'vencimento' => 'nullable|date',
        ]);

        $data = new \Carbon\Carbon($request->data);
        $vencimento = null;
        if (!empty($request->vencimento)) {
            $vencimento = new \Carbon\Carbon($request->vencimento);
        }

        $fiscal = boolval($request->fiscal);
        $quantidadeinformada = floatval($request->quantidadeinformada);
        $customedioinformado = floatval($request->customedioinformado);

        DB::beginTransaction();

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
            $vencimento
        );

        DB::commit();

        $res = EstoqueSaldoConferenciaRepository::buscaProduto($request->codprodutovariacao, $request->codestoquelocal, $fiscal);

        return response()->json($res, 201);
    }

    public function zerarProduto(Request $request)
    {

        $request->validate([
            'codprodutovariacao' => 'required|numeric',
            'codestoquelocal' => 'required|numeric',
            'fiscal' => 'required|boolean',
            'data' => 'required|date',
        ]);

        $data = new \Carbon\Carbon($request->data);

        $fiscal = boolval($request->fiscal);
        $quantidadeinformada = floatval($request->quantidadeinformada);
        $customedioinformado = floatval($request->customedioinformado);

        DB::beginTransaction();

        $model = EstoqueSaldoConferenciaRepository::zerarProduto(
            $request->codprodutovariacao,
            $request->codestoquelocal,
            $fiscal,
            $data
        );

        DB::commit();

        $res = EstoqueSaldoConferenciaRepository::buscaProduto($request->codprodutovariacao, $request->codestoquelocal, $fiscal);

        return response()->json($res, 201);
    }

    public function inativar(Request $request, $id)
    {
        $model = EstoqueSaldoConferencia::findOrFail($id);

        DB::beginTransaction();
        EstoqueSaldoConferenciaRepository::inativar($model);
        DB:: commit();

        $res = EstoqueSaldoConferenciaRepository::buscaProduto(
            $model->EstoqueSaldo->EstoqueLocalProdutoVariacao->codprodutovariacao,
            $model->EstoqueSaldo->EstoqueLocalProdutoVariacao->codestoquelocal,
            $model->EstoqueSaldo->fiscal);

        return response()->json($res, 200);
    }

}
