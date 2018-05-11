<?php
namespace Mg\Estoque;
use Mg\MgRepository;

class EstoqueLocalProdutoVariacaoRepository extends MgRepository
{

    public static function buscaOuCria($codprodutovariacao, $codestoquelocal)
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
