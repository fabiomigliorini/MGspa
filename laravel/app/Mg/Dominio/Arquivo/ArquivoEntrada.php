<?php

namespace Mg\Dominio\Arquivo;

use Carbon\Carbon;
use DB;

use Mg\Filial\Filial;
use Mg\NaturezaOperacao\DominioAcumulador;

/**
 *
 * Geração de arquivos textos com o Estoque para integracao
 * com o Dominio Sistemas
 *
 * @property Carbon $mes
 * @property Filial $Filial
 */
class ArquivoEntrada extends Arquivo
{
    public $mes;
    public $filial;
    public $inicio;
    public $fim;
    public $registrosCabelho;

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
        $this->arquivo = $mes->format('Ym') . '-' . str_pad($Filial->empresadominio, 4, '0', STR_PAD_LEFT) . '-Entradas.txt';
    }

    function processa()
    {

        $reg = new RegistroEntradaCabecalho();
        $reg->codigoEmpresa = $this->filial->empresadominio;
        $reg->cnpj = $this->filial->Pessoa->cnpj;
        $reg->dataInicial = $this->inicio;
        $reg->dataFinal = $this->fim;
        $this->registros[] = $reg;

    	$sql = "
            select
                  tblnotafiscal.codnotafiscal
                , (SELECT COUNT(codNotaFiscalDuplicatas) FROM tblNotaFiscalDuplicatas WHERE tblNotaFiscalDuplicatas.codNotaFiscal = tblNotaFiscal.codNotaFiscal) as duplicatas
                , tblnotafiscal.numero
                , tblnotafiscal.numero as numerofinal
                , tblnotafiscal.modelo
                , tblnotafiscal.serie
                , tblnotafiscal.codfilial
                , tblfilial.empresadominio
                , tblpessoa_filial.cnpj as cnpjfilial
                , tblnotafiscal.codoperacao
                , tblnotafiscal.codnaturezaoperacao
                , tblnotafiscal.codpessoa
                , tblpessoa.pessoa
                , tblpessoa.ie
                , tblpessoa.cnpj
                , tblpessoa.fisica
                , tblcidade.codestado
                , tblcidade.codigooficial
                , tblestado.sigla
                , tblnotafiscal.emissao
                , tblnotafiscal.saida
                , tblnotafiscal.emitida
                , tblnotafiscal.nfechave
                , tblnotafiscal.nfecancelamento
                , tblnotafiscal.nfeinutilizacao
                , tblnotafiscal.valorfrete
                , tblnotafiscal.valorseguro
                , tblnotafiscal.valoroutras
                , tblnotafiscal.valordesconto
                , tblnotafiscal.valortotal
            from tblnotafiscal
            inner join tblfilial on tblfilial.codfilial = tblnotafiscal.codfilial
            inner join tblpessoa as tblpessoa_filial on tblpessoa_filial.codpessoa = tblfilial.codpessoa
            inner join tblpessoa on tblpessoa.codpessoa = tblnotafiscal.codpessoa
            inner join tblcidade on tblcidade.codcidade = tblpessoa.codcidade
            inner join tblestado on tblestado.codestado = tblcidade.codestado
            where tblnotafiscal.codfilial = :codfilial
            and tblnotafiscal.modelo != 65
            and tblnotafiscal.saida between :inicio and :fim
            and tblnotafiscal.codoperacao = 1
            --and tblnotafiscal.emitida = false -- ignora notas de entrada emitidas pela filial
            --and tblnotafiscal.codnotafiscal = 2007569
            --limit 5
        ";

    	$params = [
            'codfilial' => $this->filial->codfilial,
            'inicio' => $this->inicio,
            'fim' => $this->fim,
        ];

    	$docs = DB::select($sql, $params);

        $nSeq = 0;
        foreach ($docs as $doc) {
            $nSeq++;
            $this->processaDocumento($doc, $nSeq);
        }

        return parent::processa();
    }

    public function processaDocumento ($doc, $nSeq)
    {

        /*
        // ignora notas de entrada emitida por uma outra filial
        $sql = "
            select count(nf.codnotafiscal) as quantidade
            from tblnotafiscal nf
            where nfechave = :nfechave
            and emitida = true
        ";
        $params = [
            'nfechave' => $doc->nfechave,
        ];
        $ret = DB::select($sql, $params);
        if ($ret[0]->quantidade > 0) {
            return;
	}
        */

        // busca segmentos da nota fiscal
        $sql = "
            select
                tblnotafiscalprodutobarra.codcfop
                , tblnotafiscalprodutobarra.csosn
                , tblnotafiscalprodutobarra.icmscst
                , tblnotafiscalprodutobarra.icmspercentual
                , tblproduto.codtipoproduto
                , tblproduto.codtributacao
                , tblncm.bit
                , sum(tblnotafiscalprodutobarra.valortotal) as valortotal
                , sum(tblnotafiscalprodutobarra.icmsbase) as icmsbase
                , sum(tblnotafiscalprodutobarra.icmsvalor) as icmsvalor
                , sum(tblnotafiscalprodutobarra.ipibase) as ipibase
                , sum(tblnotafiscalprodutobarra.ipivalor) as ipivalor
                , sum(tblnotafiscalprodutobarra.icmsstbase) as icmsstbase
                , sum(tblnotafiscalprodutobarra.icmsstvalor) as icmsstvalor
                , sum(tblnotafiscalprodutobarra.valordesconto) as valordesconto
                , sum(tblnotafiscalprodutobarra.valorfrete) as valorfrete
                , sum(tblnotafiscalprodutobarra.valorseguro) as valorseguro
                , sum(tblnotafiscalprodutobarra.valoroutras) as valoroutras
            from tblnotafiscal
            left join tblnotafiscalprodutobarra on (tblnotafiscalprodutobarra.codnotafiscal = tblnotafiscal.codnotafiscal)
            left join tblprodutobarra on (tblprodutobarra.codprodutobarra = tblnotafiscalprodutobarra.codprodutobarra)
            left join tblproduto on (tblproduto.codproduto = tblprodutobarra.codproduto)
            left join tblncm on (tblncm.codncm = tblproduto.codncm)
            where tblnotafiscal.codnotafiscal = :codnotafiscal
            group by tblnotafiscalprodutobarra.codcfop
                , tblnotafiscalprodutobarra.csosn
                , tblnotafiscalprodutobarra.icmscst
                , tblnotafiscalprodutobarra.icmspercentual
                , tblproduto.codtipoproduto
                , tblproduto.codtributacao
                , tblncm.bit
        ";

        $params = [
            'codnotafiscal' => $doc->codnotafiscal,
        ];
        $segs = DB::select($sql, $params);

        $nSeg = 0;
        foreach ($segs as $seg) {

            $nSeg++;

            // se segmento zerado (notas inutilizadas), pega qualquer tributacao
            // e zera valor total segmento
            if ($seg->valortotal == 0) {
                $valorTotalSegmento = 0;
                $acum = DominioAcumulador::where('codcfop', $seg->codcfop)->first();

            // se nao totaliza o segmento e busca tributacao
            } else {

                // Totaliza o segmento
                $valorTotalSegmento = 0;
                if (empty($doc->nfeinutilizacao) && empty($doc->nfecancelamento)) {
                    $valorTotalSegmento =
                        $seg->valortotal
                        + $seg->icmsstvalor
                        + $seg->ipivalor
                        + $seg->valorfrete
                        + $seg->valorseguro
                        + $seg->valoroutras
                        - $seg->valordesconto;
                }

                // busca dados da tributacao
                $acum = DominioAcumulador::
                    where('codcfop', $seg->codcfop)
                    ->where('icmscst', $seg->icmscst)
                    ->where('codfilial', $this->filial->codfilial)
                    ->first();

            }

            // se mesmo assim nao achou, nao pode continuar
            if (!$acum) {
                throw new \Exception("Sem Acumulador para CFOP '{$seg->codcfop}' CST '{$seg->icmscst}' NF '{$doc->numero}' Pessoa '{$doc->pessoa}' #NF '{$doc->codnotafiscal}'!", 1);
            }

            // se for nota inutilizada ou cancelada utiliza valor 0
            if (!empty($doc->nfeinutilizacao) || !empty($doc->nfecancelamento)) {
                $valorTotalSegmento = 0;
            }

            $this->criaRegistroSegmento($doc, $nSeq, $seg, $nSeg, $acum, $valorTotalSegmento);

            if (!empty($doc->nfeinutilizacao) || !empty($doc->nfecancelamento)) {
                continue;
            }

            $this->criaRegistroIcms($doc, $nSeq, $seg, $nSeg, $acum, $valorTotalSegmento);

            if ($seg->icmsstbase > 0) {
                $this->criaRegistroIcmsSt($doc, $nSeq, $seg, $nSeg, $acum, $valorTotalSegmento);
            }

            // busca produtos do segmento
            $sql = '
                select
                   	 tblprodutobarra.codProduto
                   	 , tblprodutoembalagem.quantidade as quantidadeembalagem
                   	 , tblnotafiscalprodutobarra.quantidade
                   	 , tblnotafiscalprodutobarra.valorunitario
                   	 , tblnotafiscalprodutobarra.valortotal
                   	 , tblnotafiscalprodutobarra.icmsbase
                   	 , tblnotafiscalprodutobarra.icmspercentual
                   	 , tblnotafiscalprodutobarra.icmsvalor
                   	 , tblnotafiscalprodutobarra.ipibase
                   	 , tblnotafiscalprodutobarra.ipipercentual
                   	 , tblnotafiscalprodutobarra.ipivalor
                   	 , tblnotafiscalprodutobarra.icmsstbase
                   	 , tblnotafiscalprodutobarra.icmsstpercentual
                   	 , tblnotafiscalprodutobarra.icmsstvalor
                   	 , tblnotafiscalprodutobarra.valordesconto
                   	 , tblnotafiscalprodutobarra.valorfrete
                   	 , tblnotafiscalprodutobarra.valorseguro
                   	 , tblnotafiscalprodutobarra.valoroutras
                from tblnotafiscalprodutobarra
                left join tblprodutobarra on (tblprodutobarra.codprodutobarra = tblnotafiscalprodutobarra.codprodutobarra)
                left join tblprodutoembalagem on (tblprodutoembalagem.codprodutoembalagem = tblprodutobarra.codprodutoembalagem)
                left join tblproduto on (tblproduto.codproduto = tblprodutobarra.codproduto)
                left join tblncm on (tblncm.codncm = tblproduto.codncm)
                where tblnotafiscalprodutobarra.codnotafiscal = :codnotafiscal
                and tblnotafiscalprodutobarra.codcfop = :codcfop
                and (tblnotafiscalprodutobarra.csosn = :csosn OR tblnotafiscalprodutobarra.icmscst = :icmscst)
                and coalesce(tblnotafiscalprodutobarra.icmspercentual, 0) = :icmspercentual
                and tblproduto.codtipoproduto = :codtipoproduto
                and tblproduto.codtributacao = :codtributacao
                and tblncm.bit = :bit
            ';

            $params = [
                'codnotafiscal' => $doc->codnotafiscal,
                'codcfop' => $seg->codcfop,
                'csosn' => $seg->csosn,
                'icmscst' => $seg->icmscst,
                'icmspercentual' => floatval($seg->icmspercentual),
                'codtipoproduto' => $seg->codtipoproduto,
                'codtributacao' => $seg->codtributacao,
                'bit' => $seg->bit,
            ];
            $prods = DB::select($sql, $params);

            // percorre os produtos
            foreach ($prods as $prod) {
                $valorTotalProduto = $prod->valortotal + $prod->icmsstvalor + $prod->ipivalor;
                $this->criaRegistroProduto ($doc, $nSeq, $seg, $nSeg, $acum, $prod, $valorTotalProduto);
            }

            if ($nSeg == 1 && $doc->duplicatas > 0) {
                // busca produtos do segmento
                $sql = '
                    SELECT *
                    FROM tblNotaFiscalDuplicatas
                    WHERE codnotafiscal = :codnotafiscal
                    order by codnotafiscalduplicatas
                ';

                $params = [
                    'codnotafiscal' => $doc->codnotafiscal,
                ];
                $parcs = DB::select($sql, $params);

                // percorre as parcelas
                foreach ($parcs as $parc) {
                    $this->criaRegistroParcela ($doc, $nSeq, $parc);
                }

            }

        }
    }

    public function criaRegistroParcela ($doc, $nSeq, $parc)
    {
        $reg = new RegistroEntradaParcela();
        $reg->sequencial = $nSeq;
        $reg->vencimento = Carbon::parse($parc->vencimento??$doc->saida);
        $reg->valorParcela = $parc->valor;
        $this->registros[] = $reg;
    }

    public function criaRegistroProduto ($doc, $nSeq, $seg, $nSeg, $acum, $prod, $valorTotalProduto)
    {
        $reg = new RegistroEntradaProduto();
        $reg->sequencial = $nSeq;
        $reg->codigoProduto = $prod->codproduto;

        // codigo do produto com quantidade embalagem concatenada
        $quant = '';
        if ($prod->quantidadeembalagem > 0) {
            $quant = '-' . round($prod->quantidadeembalagem, 0);
        }
        $cod = str_pad($prod->codproduto, 6, '0', STR_PAD_LEFT);
        $reg->codigoProduto =  "{$cod}{$quant}";

        $reg->quantidade = $prod->quantidade;
        $reg->valorTotal = round($valorTotalProduto, 2);
        $reg->valorIpi = (float) $prod->ipivalor;
        $reg->valorBcIcms = (float)(($prod->icmsstvalor>0)?0:$prod->icmsbase);
        $reg->data = Carbon::parse($doc->saida);
        $reg->cstIcms = $seg->csosn??$seg->icmscst;
        $reg->valorBrutoProduto = round($valorTotalProduto, 2);
        $reg->valorDesconto = (float)$prod->valordesconto;
        $reg->valorBcIcmsB = (float)(($prod->icmsstvalor>0)?0:$prod->icmsbase);
        $reg->valorBcIcmsSt = 0;
        $reg->aliquotaIcms = (float)(($prod->icmsstvalor>0)?0:$prod->icmspercentual);
        $reg->valorFrete = (float) $prod->valorfrete;
        $reg->valorSeguro = (float) $prod->valorseguro;
        $reg->valorOutras = (float) $prod->valoroutras;
        $reg->valorIcms = (float)(($prod->icmsstvalor>0)?0:$prod->icmsvalor);
        if ($prod->valortotal > $prod->ipibase) {
            $reg->valorOutrasIpi = (float) ($prod->valortotal - $prod->ipibase);
        } else {
            $reg->valorOutrasIpi = (float) $prod->valortotal;
        }
        // $reg->valorUnitario = (float) $prod->valorunitario;
        if ($prod->quantidade > 0) {
            $reg->valorUnitario = round($valorTotalProduto / (float)$prod->quantidade, 3);
        } else {
            $reg->valorUnitario = $valorTotalProduto;
        }
        $reg->aliquotaIcmsSt = 0;
        $reg->aliquotaIpi = (float) $prod->ipipercentual;
        $reg->cfop = $seg->codcfop;
        $reg->custoTotal = round($valorTotalProduto, 2);
        $reg->quantidadeProduto = $prod->quantidade;
        $reg->movimentacaoFisica = $acum->movimentacaofisica?'S':'N';
        $reg->valorContabil =
            round(
                $valorTotalProduto
                - $reg->valorDesconto
                + $reg->valorFrete
                + $reg->valorSeguro
                + $reg->valorOutras
            , 2);
        if ($seg->codtipoproduto == 8) {
            $reg->baseCredito = 10;
        } else {
            $reg->baseCredito = 0;
        }

        $this->registros[] = $reg;

    }

    public function criaRegistroIcmsSt ($doc, $nSeq, $seg, $nSeg, $acum, $valorTotalSegmento)
    {
        $reg = new RegistroEntradaImposto();
        $reg->sequencial = $nSeq;
        $reg->codigoImposto = 9; // ICMS ST
        $reg->bc = $seg->icmsstbase;
        $reg->aliquota = round((($seg->icmsstvalor + $seg->icmsvalor) / $seg->icmsstbase) * 100, 2);
        $reg->valorImposto = (float) $seg->icmsstvalor;
        $reg->valorContabil = $valorTotalSegmento;
        $this->registros[] = $reg;
    }

    public function criaRegistroIcms ($doc, $nSeq, $seg, $nSeg, $acum, $valorTotalSegmento)
    {
        $reg = new RegistroEntradaImposto();
        $reg->codigoImposto = 1; // ICMS
        $reg->sequencial = $nSeq;
        $reg->bc = 0;
        $reg->aliquota = 0;
        $reg->valorImposto = 0;
        if (empty($seg->icmsstvalor) && !empty($seg->icmsbase)) {
            $reg->bc = (float) $seg->icmsbase;
            $reg->aliquota = round(($seg->icmsvalor / $seg->icmsbase) * 100, 2);
            $reg->valorImposto = (float) $seg->icmsvalor;
        }
        if ($seg->codtributacao == 2) {
            $reg->valorIsento = $valorTotalSegmento - $reg->bc;
        } else {
            $reg->valorOutras = $valorTotalSegmento - $reg->bc;
        }
        $reg->valorContabil = $valorTotalSegmento;
        $this->registros[] = $reg;
    }

    public function criaRegistroSegmento ($doc, $nSeq, $seg, $nSeg, $acum, $valorTotalSegmento)
    {
        // cria registro do segmento
        $reg = new RegistroEntradaSegmento();
        $reg->sequencial = $nSeq;
        $reg->codigoEmpresa = $doc->empresadominio;

        if (empty($doc->cnpj)) {
            $reg->inscricao = '';
        } else {
            if ($doc->fisica) {
                $reg->inscricao = str_pad($doc->cnpj, 11, '0', STR_PAD_LEFT);
            } else {
                $reg->inscricao = str_pad($doc->cnpj, 14, '0', STR_PAD_LEFT);
            }
        }

        if (empty($doc->nfechave) && empty($doc->nfeinutilizacao)) {
            $reg->codigoEspecie = 1; // Nota Fiscal em Fornulario - Modelo Antigo
        } else {
            if ($doc->modelo == 55) {
                if ($doc->serie == 890) {
                    $reg->codigoEspecie = 102; // Nota Fiscal Eletronica Avulsa
                } else {
                    $reg->codigoEspecie = 36; // NFe
                }
            } else {
                $reg->codigoEspecie = 103; // Nota Fsical Eletronica Consumidor NFCe
            }
        }

        if ($doc->duplicatas > 0) {
            $reg->codigoAcumulador = $acum->acumuladorprazo;
        } else {
            $reg->codigoAcumulador = $acum->acumuladoravista;
        }
        $reg->cfop = $seg->codcfop;
        $reg->segmento = $nSeg;
        $reg->numeroDocumento = $doc->numero;
        $reg->serie = $doc->serie;
        $reg->documentoFinal = $doc->numerofinal;
        $reg->dataEntrada = Carbon::parse($doc->saida);
        $reg->dataEmissao = Carbon::parse($doc->emissao);
        $reg->valorContabil = $valorTotalSegmento;
        $reg->estadoFornecedor = $doc->sigla;
        $reg->emitenteNota = ($doc->emitida == 1)?'P':'T';
        $reg->codigoMunicipio = $doc->codigooficial;
        $reg->valorFrete = $seg->valorfrete??0;
        $reg->valorSeguro = $seg->valorseguro??0;
        $reg->valorOutras = $seg->valoroutras??0;
        $reg->valorPis = 0;
        $reg->valorCofins = 0;
        $reg->valorProdutos =
            $seg->valortotal
            + $seg->icmsstvalor
            + $seg->ipivalor;
        $reg->valorBcIcmsSt =
            $seg->valortotal
            + $seg->icmsstvalor
            + $seg->ipivalor;
        $reg->codigoModelo = empty($doc->nfecancelamento)?0:2;
        $reg->codigoSituacaoTributaria = $seg->csosn??$seg->icmscst;
        $reg->inscricaoEstadual = $doc->ie;
        $reg->chaveNFe = $doc->nfechave;
        $this->registros[] = $reg;

    }
}
