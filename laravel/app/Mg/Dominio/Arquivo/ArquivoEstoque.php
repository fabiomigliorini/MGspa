<?php

namespace Mg\Dominio\Arquivo;

use Carbon\Carbon;
use DB;

use Mg\Dominio\Arquivo\Arquivo;
use Mg\Filial\Filial;
use Mg\Estoque\EstoqueLocalProdutoVariacao;
use Mg\Estoque\EstoqueSaldo;
use Mg\Estoque\EstoqueMes;

/**
 *
 * Geração de arquivos textos com o Estoque para integracao
 * com o Dominio Sistemas
 *
 * @property Carbon $mes
 * @property Filial $Filial
 */
class ArquivoEstoque extends Arquivo
{
    public $mes;
    public $filial;

    /**
     *
     * Inicializa Classe
     *
     * @param \Carbon\Carbon $mes
     * @param \MGLara\Models\Filial $Filial
     */
    function __construct(Carbon $mes, Filial $Filial)
    {
        $this->mes = $mes;
        $this->filial = $Filial;
        $this->arquivo = $mes->format('Ym') . '-' . str_pad($Filial->empresadominio, 4, '0', STR_PAD_LEFT) . '-Estoque.txt';
    }

    function processa()
    {

        $dataSaldo = $this->mes->modify('last day of this month');

        $sql = "
            select
	        p.codproduto
	        , p.produto
	        , p.inativo
                , p.codtipoproduto
	        , p.preco
                , um.sigla
                , n.ncm
	        , fiscal.saldoquantidade
	        , fiscal.customedio
	        , fiscal.saldovalor
	        , p.preco / case when fiscal.customedio != 0 then fiscal.customedio else null end as markup
            from tblproduto p
            left join tblunidademedida um on (um.codunidademedida = p.codunidademedida)
            left join (
	        select
		        pv.codproduto
		        , sum(em.saldoquantidade) as saldoquantidade
		        , sum(em.saldovalor) as saldovalor
		        , sum(em.saldovalor) / case when sum(em.saldoquantidade) !=0 then sum(em.saldoquantidade) else null end as customedio
	        from tblestoquelocalprodutovariacao elpv
	        inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao)
	        inner join tblestoquelocal el on (el.codestoquelocal = elpv.codestoquelocal)
	        inner join tblfilial f on (f.codfilial = el.codfilial)
	        inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = true)
	        inner join tblestoquemes em on (em.codestoquemes = (select em2.codestoquemes from tblestoquemes em2 where em2.codestoquesaldo = es.codestoquesaldo and em2.mes <= :mes order by mes desc limit 1))
	        where f.codfilial = :codfilial
              group by pv.codproduto
	        ) fiscal on (fiscal.codproduto = p.codproduto)
            left join tblncm n on (n.codncm = p.codncm)
            where p.codtipoproduto = 0
            AND fiscal.saldoquantidade != 0
            order by 7 desc, p.produto, p.codproduto
        ";

    	$params = [
            'mes' => $dataSaldo,
            'codfilial' => $this->filial->codfilial
        ];

    	$saldos = DB::select($sql, $params);

    	foreach ($saldos as $saldo) {

            $reg = new RegistroProduto4();
            $reg->codigoProduto = str_pad($saldo->codproduto, 6, '0', STR_PAD_LEFT);
            $reg->codigoEmpresa = $this->filial->empresadominio;

            switch ($saldo->codtipoproduto)
            {
                case 8: // Imobilizado
                    $reg->codigoGrupo = 3;
                    break;

                case 7: // Uso e Consumo
                    $reg->codigoGrupo = 2;
                    break;

                default: // Imobilizado
                    $reg->codigoGrupo = 1;
                    break;
            }

            $reg->codigoNbm = null;
            $reg->descricaoProduto = $saldo->produto;
            $reg->tipoItem = $saldo->codtipoproduto;
            $reg->unidadeMedida = $saldo->sigla;
            $reg->valorUnitario = $saldo->preco;
            $reg->codigoNcm = $saldo->ncm;
            $reg->dataSaldoFinal = $dataSaldo;
            $reg->valorFinalEstoque = $saldo->saldovalor;
            $reg->quantidadeFinalEstoque = $saldo->saldoquantidade;

            $this->registros[] = $reg;

        }

        return parent::processa();
    }
}
