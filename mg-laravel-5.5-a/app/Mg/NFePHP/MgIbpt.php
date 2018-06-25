<?php

namespace Mg\NFePHP;

use Carbon\Carbon;

use Mg\Filial\Filial;
use Mg\NotaFiscal\NotaFiscalProdutoBarra;

use NFePHP\Ibpt\Ibpt;

class MgIbpt extends Ibpt
{
    protected $filial;

    public function __construct(
        Filial $filial,
        $proxy = [],
        RestInterface $rest = null
    ) {
        $this->filial = $filial;
        $cnpj = mascarar($filial->Pessoa->cnpj, '##############');
        $token = $filial->tokenibpt;
        parent::__construct($cnpj, $token, $proxy, $rest);
    }

    public function pesquisar(NotaFiscalProdutoBarra $nfpb)
    {

        // Monta Variaveis para Consulta
        $codfilial = $this->filial->codfilial;
        // TODO: Descobrir se e pra passar estado da filial ou do Cliente
        $codestado = $nfpb->NotaFiscal->Pessoa->Cidade->codestado;
        $uf = $nfpb->NotaFiscal->Pessoa->Cidade->Estado->sigla;
        $ncm = $nfpb->ProdutoBarra->Produto->Ncm->ncm;
        $emissao = $nfpb->NotaFiscal->emissao;
        $extarif = 0;

        // Procura na tabela de cache
        $reg = IbptCache::firstOrNew([
            'codfilial' => $codfilial,
            'codestado' => $codestado,
            'ncm' => $ncm,
            'extarif' => $extarif,
        ]);


        // Se estiver com data de vigencia inferior na tabela de cache
        if ($emissao->gt($reg->vigenciafim)) {

            // Consulta no Web Service do IBPT
            $consulta = $this->productTaxes(
                $nfpb->NotaFiscal->Pessoa->Cidade->Estado->sigla,
                $nfpb->ProdutoBarra->Produto->Ncm->ncm,
                0
              );

            // Salva na Tabela de cache
            $reg->descricao = $consulta->Descricao;
            $reg->nacional = $consulta->Nacional;
            $reg->estadual = $consulta->Estadual;
            $reg->importado = $consulta->Importado;
            $reg->municipal = $consulta->Municipal;
            $reg->tipo = $consulta->Tipo;
            $reg->vigenciainicio = Carbon::createFromFormat('d/m/Y', $consulta->VigenciaInicio);
            $reg->vigenciafim = Carbon::createFromFormat('d/m/Y', $consulta->VigenciaFim);
            $reg->chave = $consulta->Chave;
            $reg->versao = $consulta->Versao;
            $reg->fonte = $consulta->Fonte;
            $reg->save();
        }

        // retorna dados do cache
        return $reg;
    }

}
