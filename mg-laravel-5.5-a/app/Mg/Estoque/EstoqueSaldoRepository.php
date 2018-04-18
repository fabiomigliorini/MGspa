<?php
namespace Mg\Estoque;
use Mg\MgRepository;

class EstoqueSaldoRepository extends MgRepository
{
    public static function buscaOuCria($codprodutovariacao, $codestoquelocal, $fiscal)
    {
        $elpv = EstoqueLocalProdutoVariacaoRepository::buscaOuCria($codprodutovariacao, $codestoquelocal);

        $es = EstoqueSaldo::where('codestoquelocalprodutovariacao', $elpv->codestoquelocalprodutovariacao)->where('fiscal', $fiscal)->first();
        if ($es == false)
        {
            $es = new EstoqueSaldo();
            $es->codestoquelocalprodutovariacao = $elpv->codestoquelocalprodutovariacao;
            $es->fiscal = $fiscal;
            $es->save();
        }
        return $es;
    }
}
