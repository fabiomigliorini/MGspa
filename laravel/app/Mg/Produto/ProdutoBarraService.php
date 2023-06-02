<?php

namespace Mg\Produto;

class ProdutoBarraService
{
    public static function unificaBarras ($codprodutobarraorigem, $codprodutobarradestino)
    {

        $pb_origem = ProdutoBarra::findOrFail($codprodutobarraorigem);
        $pb_destino = ProdutoBarra::findOrFail($codprodutobarradestino);

        if ($pb_origem->codprodutovariacao != $pb_destino->codprodutovariacao) {
            throw new \Exception('Barras não são da mesma Variacao!');
        }
        if ($pb_origem->codprodutoembalagem != $pb_destino->codprodutoembalagem) {
            throw new \Exception('Barras não são da mesma Embalagem!');
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

        return $pb_destino;

    }
}
