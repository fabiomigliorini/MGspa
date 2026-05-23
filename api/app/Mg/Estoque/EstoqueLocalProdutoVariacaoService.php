<?php
namespace Mg\Estoque;
use Mg\MgService;

class EstoqueLocalProdutoVariacaoService extends MgService
{

    public static function buscaOuCria($codestoquelocal, $codprodutovariacao)
        {
            $elpv = EstoqueLocalProdutoVariacao::where('codprodutovariacao', $codprodutovariacao)->where('codestoquelocal', $codestoquelocal)->first();
            if ($elpv == false)
            {
                $elpv = new EstoqueLocalProdutoVariacao();
                $elpv->codprodutovariacao = $codprodutovariacao;
                $elpv->codestoquelocal = $codestoquelocal;
                $elpv->save();
            }
            return $elpv;
        }
}
