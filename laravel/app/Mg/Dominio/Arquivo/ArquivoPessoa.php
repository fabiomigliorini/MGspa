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
class ArquivoPessoa extends Arquivo
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
        $this->inicio = (clone $this->mes)->startOfMonth();
        $this->fim = (clone $this->mes)->endOfMonth();
        $this->filial = $Filial;
        $this->arquivo = $mes->format('Ym') . '-' . str_pad($Filial->empresadominio, 4, '0', STR_PAD_LEFT) . '-Pessoas.txt';
    }

    function processa()
    {

    	$sql = "
            with ent as (
            	select distinct nf.codpessoa
            	from tblnotafiscal nf
            	where nf.codfilial = :codfilial
            	and nf.codoperacao = 1
            	and nf.saida between :inicio and :fim
            	and nf.codpessoa != 1
            )
            SELECT tblpessoa.codPessoa
                , tblpessoa.pessoa
                , tblpessoa.fantasia
                , tblpessoa.cliente
                , tblpessoa.fornecedor
                , tblpessoa.fisica
                , tblpessoa.cnpj
                , tblpessoa.ie
                , tblpessoa.endereco
                , tblpessoa.numero
                , tblpessoa.complemento
                , tblpessoa.codcidade
                , tblcidade.cidade
                , tblcidade.codigooficial
                , tblestado.sigla
                , tblpessoa.bairro
                , tblpessoa.cep
                , tblpessoa.telefone1
                , tblpessoa.telefone2
                , tblpessoa.rg
            FROM tblpessoa
            inner join ent on (ent.codpessoa = tblpessoa.codpessoa)
            left join tblCidade on tblCidade.codCidade = tblPessoa.codCidade
            left join tblEstado on tblEstado.codestado = tblcidade.codestado
            order by tblpessoa.codpessoa
        ";

    	$params = [
            'codfilial' => $this->filial->codfilial,
            'inicio' => $this->inicio,
            'fim' => $this->fim,
        ];

    	$pessoas = DB::select($sql, $params);

    	foreach ($pessoas as $pessoa) {

            $reg = new RegistroPessoa();
            $reg->codigoEmpresa = $this->filial->empresadominio;
            $reg->siglaEstado = $pessoa->sigla;
            $reg->codigoMunicipio = $pessoa->codigooficial;
            $reg->nomeReduzido = $pessoa->fantasia;
            $reg->nome = $pessoa->pessoa;
            $reg->endereco = $pessoa->endereco;
            $reg->enderecoNumero = $pessoa->numero;
            $reg->cep = $pessoa->cep;
            if ($pessoa->fisica) {
                $reg->inscricao = str_pad($pessoa->cnpj, 11, '0', STR_PAD_LEFT);
            } else {
                $reg->inscricao = str_pad($pessoa->cnpj, 14, '0', STR_PAD_LEFT);
            }
            $reg->inscricaoEstadual = $pessoa->ie;
            $reg->fone = preg_replace('/[^0-9]/', '', $pessoa->telefone1);
            $reg->fax = preg_replace('/[^0-9]/', '', $pessoa->telefone2);
            $reg->tipoInscricao = (!$pessoa->fisica)?'1':'2';
            $reg->bairro = $pessoa->bairro;


            // dd($pessoa);
            // $reg->codigoProduto = str_pad($pessoa->codproduto, 6, '0', STR_PAD_LEFT);
            //
            // switch ($pessoa->codtipoproduto)
            // {
            //     case 8: // Imobilizado
            //         $reg->codigoGrupo = 3;
            //         break;
            //
            //     case 7: // Uso e Consumo
            //         $reg->codigoGrupo = 2;
            //         break;
            //
            //     default: // Imobilizado
            //         $reg->codigoGrupo = 1;
            //         break;
            // }
            //
            // $reg->codigoNbm = null;
            // $reg->descricaoProduto = $pessoa->produto;
            // $reg->tipoItem = $pessoa->codtipoproduto;
            // $reg->unidadeMedida = $pessoa->sigla;
            // $reg->valorUnitario = $pessoa->preco;
            // $reg->codigoNcm = $pessoa->ncm;
            // // $reg->dataSaldoFinal = $dataSaldo;
            // // $reg->valorFinalEstoque = $pessoa->saldovalor;
            // // $reg->quantidadeFinalEstoque = $pessoa->saldoquantidade;
            //
            $this->registros[] = $reg;

        }

        return parent::processa();
    }
}
