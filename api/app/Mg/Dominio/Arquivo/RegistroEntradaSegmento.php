<?php

namespace Mg\Dominio\Arquivo;

class RegistroEntradaSegmento extends Registro
{
    function __construct()
    {
        $this->campos = [
            'identificador' => [
                'tamanho' => 2,
                'tipo' => 'char'
            ],
            'sequencial' => [
                'tamanho' => 7,
                'tipo' => 'numeric'
            ],
            'codigoEmpresa' => [
                'tamanho' => 7,
                'tipo' => 'numeric'
            ],
            'inscricao' => [
                'tamanho' => 14,
                'tipo' => 'char'
            ],
            'codigoEspecie' => [
                'tamanho' => 7,
                'tipo' => 'numeric',
            ],
            'codigoExclusaoDief' => [
                'tamanho' => 2,
                'tipo' => 'char',
            ],
            'codigoAcumulador' => [
                'tamanho' => 7,
                'tipo' => 'numeric',
            ],
            'cfop' => [
                'tamanho' => 7,
                'tipo' => 'numeric',
            ],
            'segmento' => [
                'tamanho' => 2,
                'tipo' => 'numeric',
            ],
            'numeroDocumento' => [
                'tamanho' => 9,
                'tipo' => 'numeric',
            ],
            'serie' => [
                'tamanho' => 7,
                'tipo' => 'texto',
            ],
            'documentoFinal' => [
                'tamanho' => 9,
                'tipo' => 'numeric',
            ],
            'dataEntrada' => [
                'tamanho' => 10,
                'tipo' => 'date',
                'formato' => 'd/m/Y'
            ],
            'dataEmissao' => [
                'tamanho' => 10,
                'tipo' => 'date',
                'formato' => 'd/m/Y'
            ],
            'valorContabil' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorExclusaoDief' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'brancosA' => [
                'tamanho' => 26,
                'tipo' => 'char',
            ],
            'estadoFornecedor' => [
                'tamanho' => 2,
                'tipo' => 'char',
            ],
            'modalidadeFrete' => [
                'tamanho' => 1,
                'tipo' => 'char',
            ],
            'emitenteNota' => [
                'tamanho' => 1,
                'tipo' => 'char',
            ],
            'fatoGeradorCrf' => [
                'tamanho' => 1,
                'tipo' => 'char',
            ],
            'fatoGeradorIrrf' => [
                'tamanho' => 1,
                'tipo' => 'char',
            ],
            'codigoMunicipio' => [
                'tamanho' => 7,
                'tipo' => 'numeric',
            ],
            'cfopEstendido' => [
                'tamanho' => 7,
                'tipo' => 'char',
            ],
            'codigoTransferencia' => [
                'tamanho' => 7,
                'tipo' => 'char',
            ],
            'brancosB' => [
                'tamanho' => 6,
                'tipo' => 'char',
            ],
            'brancosC' => [
                'tamanho' => 6,
                'tipo' => 'char',
            ],
            'codigoObservacao' => [
                'tamanho' => 7,
                'tipo' => 'numeric',
            ],
            'dataVisto' => [
                'tamanho' => 10,
                'tipo' => 'date',
                'formato' => 'd/m/Y'
            ],
            'valorFrete' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorSeguro' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorOutras' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'tipoAntecipacaoTributaria' => [
                'tamanho' => 7,
                'tipo' => 'numeric',
            ],
            'valorPis' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorCofins' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorProdutos' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorBcIcmsStScanc' => [
                'tamanho' => 1,
                'tipo' => 'char',
            ],
            'valorEntradaSaidaIsenta' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2,
            ],
            'valorOutrasIsentas' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2,
            ],
            'valorTransporte' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2,
            ],
            'ressarcimentoMg' => [
                'tamanho' => 1,
                'tipo' => 'char',
            ],
            'codigoModelo' => [
                'tamanho' => 7,
                'tipo' => 'numeric',
            ],
            'codigoSituacaoTributaria' => [
                'tamanho' => 7,
                'tipo' => 'numeric',
            ],
            'subSerie' => [
                'tamanho' => 7,
                'tipo' => 'numeric',
            ],
            'inscricaoEstadual' => [
                'tamanho' => 20,
                'tipo' => 'char',
            ],
            'inscricaoMunicipal' => [
                'tamanho' => 20,
                'tipo' => 'char',
            ],
            'observacao' => [
                'tamanho' => 300,
                'tipo' => 'char',
            ],
            'chaveNFe' => [
                'tamanho' => 44,
                'tipo' => 'numeric',
            ],
            'codigoFethab' => [
                'tamanho' => 6,
                'tipo' => 'char',
            ],
            'responsavelFethab' => [
                'tamanho' => 1,
                'tipo' => 'char',
            ],
            'tipoCTe' => [
                'tamanho' => 1,
                'tipo' => 'char',
            ],
            'referenciaCTe' => [
                'tamanho' => 44,
                'tipo' => 'char',
            ],
            'brancosD' => [
                'tamanho' => 48,
                'tipo' => 'char',
            ],
            'codigoInformacao' => [
                'tamanho' => 7,
                'tipo' => 'numeric',
            ],
            'informacoesComplementares' => [
                'tamanho' => 255,
                'tipo' => 'char',
            ],
            'naturezaFrete' => [
                'tamanho' => 1,
                'tipo' => 'numeric',
            ],
            'cstPisCofins' => [
                'tamanho' => 7,
                'tipo' => 'numeric',
            ],
            'baseCreditoPisCofins' => [
                'tamanho' => 4,
                'tipo' => 'numeric',
            ],
            'valorServicosPisCofins' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2,
            ],
            'valorBcPisCofins' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2,
            ],
            'aliquotaPis' => [
                'tamanho' => 5,
                'tipo' => 'decimal',
                'casas' => 2,
            ],
            'aliquotaCofins' => [
                'tamanho' => 5,
                'tipo' => 'decimal',
                'casas' => 2,
            ],
            'chaveNFSe' => [
                'tamanho' => 8,
                'tipo' => 'char',
            ],
            'numeroProcesso' => [
                'tamanho' => 15,
                'tipo' => 'char',
            ],
            'origemProcesso' => [
                'tamanho' => 1,
                'tipo' => 'char',
            ],
            'brancosE' => [
                'tamanho' => 27,
                'tipo' => 'char',
            ],
        ];

        $this->identificador = '02';
        $this->valorExclusaoDief = 0;
        $this->modalidadeFrete = 'F';
        $this->codigoObservacao = 0;
        $this->tipoAntecipacaoTributaria = 0;
        $this->valorEntradaSaidaIsenta = 0;
        $this->valorOutrasIsentas = 0;
        $this->valorTransporte = 0;
        $this->subSerie = 0;
        $this->observacao = 'Exportacao MGspa @ ' . date('d/m/Y H:i:s');
        $this->codigoInformacao = 0;
        $this->naturezaFrete = 0;
        $this->cstPisCofins = 99;
        $this->baseCreditoPisCofins = 0;
        $this->valorServicosPisCofins = 0;
        $this->valorBcPisCofins = 0;
        $this->aliquotaPis = 0;
        $this->aliquotaCofins = 0;

    }
}
