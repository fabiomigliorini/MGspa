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
class ArquivoProduto extends Arquivo
{
    public $mes;
    public $filial;

    public $inicio;
    public $fim;

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
        $this->arquivo = $mes->format('Ym') . '-' . str_pad($Filial->empresadominio, 4, '0', STR_PAD_LEFT) . '-Produtos.txt';
        $this->inicio = (clone $this->mes)->startOfMonth();
        $this->fim = (clone $this->mes)->endOfMonth();
    }

    function processa()
    {
    	$sql = "
            with mov as (
            	SELECT DISTINCT codProduto
            	FROM tblNotaFiscal
            	inner join tblNotaFiscalProdutoBarra on (tblNotaFiscalProdutoBarra.codNotaFiscal = tblNotaFiscal.codNotaFiscal)
            	inner join tblProdutoBarra on (tblProdutoBarra.codProdutoBarra = tblNotaFiscalProdutoBarra.codProdutoBarra)
            	WHERE tblNotaFiscal.codfilial =  :codfilial
            	and tblNotaFiscal.saida >= :inicio
            	and tblNotaFiscal.saida <= :fim
            )
            SELECT distinct
            	  tblproduto.codProduto
            	, tblproduto.produto
            	, tblproduto.referencia
            	, tblproduto.codunidademedida
            	, tblUnidadeMedida.sigla
            	--, tblproduto.codsubgrupoproduto
            	--, tblproduto.codmarca
            	, tblproduto.preco
            	, tblproduto.importado
            	, tblncm.ncm
            	--, tblproduto.codtributacao
            	, tblproduto.inativo
            	, tblproduto.codtipoproduto
            FROM tblproduto
            left join tblUnidadeMedida on (tblUnidadeMedida.codUnidadeMedida = tblProduto.codUnidadeMedida)
            left join tblNcm on (tblNcm.codncm = tblProduto.codncm)
            inner join mov on (mov.codproduto = tblproduto.codproduto)
            order by codproduto
    	";

    	$params = [
            'codfilial' => $this->filial->codfilial,
            'inicio' => $this->inicio,
            'fim' => $this->fim,
        ];

    	$produtos = DB::select($sql, $params);

    	foreach ($produtos as $produto) {

            $reg = new RegistroProduto4();
            $reg->codigoProduto = str_pad($produto->codproduto, 6, '0', STR_PAD_LEFT);
            $reg->codigoEmpresa = $this->filial->empresadominio;

            switch ($produto->codtipoproduto)
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
            $reg->descricaoProduto = $produto->produto;
            $reg->tipoItem = $produto->codtipoproduto;
            $reg->unidadeMedida = $produto->sigla;
            $reg->valorUnitario = $produto->preco;
            $reg->codigoNcm = $produto->ncm;
            // $reg->dataSaldoFinal = $dataSaldo;
            // $reg->valorFinalEstoque = $produto->saldovalor;
            // $reg->quantidadeFinalEstoque = $produto->saldoquantidade;

            $this->registros[] = $reg;

        }

        return parent::processa();
    }
}
