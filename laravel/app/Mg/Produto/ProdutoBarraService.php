<?php

namespace Mg\Produto;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DB;
use Carbon\Carbon;

use Mg\Estoque\EstoqueMesService;
use Mg\Estoque\EstoqueMovimento;
use Mg\Estoque\EstoqueSaldoConferencia;

/**
 * Description of ProdutoRepository
 *
 */
class ProdutoBarraService
{
    public static function unificaBarras ($codprodutobarraorigem, $codprodutobarradestino)
    {

        $pb_origem = ProdutoBarra::findOrFail($codprodutobarraorigem);
        $pb_destino = ProdutoBarra::findOrFail($codprodutobarradestino);

        if ($pb_origem->codprodutovariacao != $pb_destino->codprodutovariacao) {
            dd('Barras não são da mesma Variacao!');
        }
        $regs = $pb_origem->NegocioProdutoBarraS()->where('codprodutobarra', $codprodutobarraorigem)->update([
            'codprodutobarra' => $codprodutobarradestino
        ]);
        $regs = $pb_origem->NotaFiscalProdutoBarraS()->where('codprodutobarra', $codprodutobarraorigem)->update([
            'codprodutobarra' => $codprodutobarradestino
        ]);
        $regs = $pb_origem->CupomFiscalProdutoBarraS()->where('codprodutobarra', $codprodutobarraorigem)->update([
            'codprodutobarra' => $codprodutobarradestino
        ]);
        $regs = $pb_origem->NfeTerceiroItemS()->where('codprodutobarra', $codprodutobarraorigem)->update([
            'codprodutobarra' => $codprodutobarradestino
        ]);
        $regs = $pb_origem->ValeCompraModeloProdutoBarraS()->where('codprodutobarra', $codprodutobarraorigem)->update([
            'codprodutobarra' => $codprodutobarradestino
        ]);
        $regs = $pb_origem->ValeCompraProdutoBarraS()->where('codprodutobarra', $codprodutobarraorigem)->update([
            'codprodutobarra' => $codprodutobarradestino
        ]);
        $pb_origem->delete();

        return true;

    }
}
