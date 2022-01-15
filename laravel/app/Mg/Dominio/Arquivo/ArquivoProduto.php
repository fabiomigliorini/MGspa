<?php

namespace Mg\Dominio\Arquivo;

use Carbon\Carbon;
use DB;

use Mg\Dominio\Arquivo\Arquivo;
use Mg\Filial\Filial;
use Mg\Produto\ProdutoEmbalagem;

/**
 *
 * Geração de arquivos textos com o cadastro de Produtos para integracao
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
              -- and tblNotaFiscal.codoperacao = 1
              -- limit 10
            )
            SELECT distinct
            	  tblproduto.codProduto
            	, tblproduto.produto
            	, tblproduto.referencia
            	, tblproduto.codunidademedida
            	, tblUnidadeMedida.sigla
            	, tblproduto.preco
            	, tblproduto.importado
            	, tblncm.ncm
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
            // $reg->unidade = $produto->sigla;
            $reg->valorUnitario = $produto->preco;
            $reg->codigoNcm = $produto->ncm;
            $reg->dataSaldoFinal = $this->fim;
            $reg->valorFinalEstoque = 0;
            $this->registros[] = $reg;

            $embalagens = ProdutoEmbalagem::with('unidadeMedida')->where('codproduto', $produto->codproduto)->orderBY('quantidade')->get();
            foreach ($embalagens as $emb) {
                $reg = (clone $reg);
                $quant = round($emb->quantidade, 0);
                $cod = str_pad($produto->codproduto, 6, '0', STR_PAD_LEFT);
                $reg->codigoProduto =  "{$cod}-{$quant}";
                $reg->descricaoProduto = "{$produto->produto} C/{$quant}";
                $reg->unidadeMedida = $emb->unidadeMedida->sigla;
                // $reg->unidade = $emb->unidadeMedida->sigla;
                $reg->valorUnitario = $emb->preco??($emb->quantidade * $produto->preco);
                $this->unidadeInventariadaDiferente = 'S';
                $this->registros[] = $reg;
            }

        }

        return parent::processa();
    }
}
