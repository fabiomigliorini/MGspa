<?php

namespace Mg\Dominio\Arquivo;

class RegistroEntradaProduto extends Registro
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
            'codigoProduto' => [
                'tamanho' => 14,
                'tipo' => 'char'
            ],
            'quantidade' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 3
            ],
            'valorTotal' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 3
            ],
            'valorIpi' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 3
            ],
            'valorBcIcms' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 3
            ],
            'tipo' => [
                'tamanho' => 1,
                'tipo' => 'numeric',
            ],
            'data' => [
                'tamanho' => 10,
                'tipo' => 'date',
                'formato' => 'd/m/Y',
            ],
            'cstIcms' => [
                'tamanho' => 7,
                'tipo' => 'numeric',
            ],
            'valorBrutoProduto' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorDesconto' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorBcIcmsB' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorBcIcmsSt' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'aliquotaIcms' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'produtoIncentivado' => [
                'tamanho' => 1,
                'tipo' => 'char',
            ],
            'codigoApuracaoPe' => [
                'tamanho' => 7,
                'tipo' => 'numeric',
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
            'quantidadeGasolina' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 3
            ],
            'valorIcms' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorSubtri' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorIsentasIpi' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorOutrasIpi' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorIcmsNfp' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorUnitario' => [
                'tamanho' => 15,
                'tipo' => 'decimal',
                'casas' => 3
            ],
            'aliquotaIcmsSt' => [
                'tamanho' => 5,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'codigoTributacaoIpi' => [
                'tamanho' => 7,
                'tipo' => 'numeric',
            ],
            'aliquotaIpi' => [
                'tamanho' => 5,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'baseIssqn' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'aliquotaIssqn' => [
                'tamanho' => 5,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorIssqn' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'cfop' => [
                'tamanho' => 7,
                'tipo' => 'numeric',
            ],
            'serieEcf' => [
                'tamanho' => 20,
                'tipo' => 'char',
            ],
            'aliquotaPis' => [
                'tamanho' => 5,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorPis' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'aliquotaCofins' => [
                'tamanho' => 5,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorCofins' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'custoTotal' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'cstPis' => [
                'tamanho' => 7,
                'tipo' => 'numeric',
            ],
            'valorBcPis' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'cstCofins' => [
                'tamanho' => 7,
                'tipo' => 'numeric',
            ],
            'valorBcCofins' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'chassi' => [
                'tamanho' => 17,
                'tipo' => 'char',
            ],
            'tipoVeiculo' => [
                'tamanho' => 1,
                'tipo' => 'char',
            ],
            'loteMedicamento' => [
                'tamanho' => 255,
                'tipo' => 'char',
            ],
            'quantidadeLoteMedicamento' => [
                'tamanho' => 7,
                'tipo' => 'numeric',
            ],
            'dataValidade' => [
                'tamanho' => 10,
                'tipo' => 'date',
                'formato' => 'd/m/Y',
            ],
            'dataFabricacao' => [
                'tamanho' => 10,
                'tipo' => 'date',
                'formato' => 'd/m/Y',
            ],
            'referenciaBc' => [
                'tamanho' => 1,
                'tipo' => 'numeric',
            ],
            'valorTabeladoMaximo' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'numeroSerieArma' => [
                'tamanho' => 255,
                'tipo' => 'char',
            ],
            'numeroSerieCano' => [
                'tamanho' => 255,
                'tipo' => 'char',
            ],
            'enquadramentoIpi' => [
                'tamanho' => 3,
                'tipo' => 'char',
            ],
            'quantidadeProduto' => [
                'tamanho' => 16,
                'tipo' => 'decimal',
                'casas' => 5
            ],
            'movimentacaoFisica' => [
                'tamanho' => 1,
                'tipo' => 'char',
            ],
            'unidadeComercializada' => [
                'tamanho' => 6,
                'tipo' => 'char',
            ],
            'valorContabil' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'quantidadeTributadaPis' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 3
            ],
            'valorUnidadePis' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 4
            ],
            'valorUnidadePisB' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'quantidadeTributadaCofins' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 3
            ],
            'valorUnidadeCofins' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 4
            ],
            'valorUnidadeCofinsB' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'baseCredito' => [
                'tamanho' => 4,
                'tipo' => 'numeric',
            ],
            'brancos' => [
                'tamanho' => 93,
                'tipo' => 'char',
            ],
        ];

        $this->identificador = '04';
        $this->tipo = 1;
        $this->codigoApuracaoPe = 0;
        $this->quantidadeGasolina = 0;
        $this->valorSubtri = 0;
        $this->valorIsentasIpi = 0;
        $this->valorIcmsNfp = 0;
        $this->codigoTributacaoIpi = 49;
        $this->baseIssqn = 0;
        $this->aliquotaIssqn = 0;
        $this->valorIssqn = 0;
        $this->aliquotaPis = 0;
        $this->valorPis = 0;
        $this->aliquotaCofins = 0;
        $this->valorCofins = 0;
        $this->cstPis = 99;
        $this->valorBcPis = 0;
        $this->cstCofins = 99;
        $this->valorBcCofins = 0;
        $this->quantidadeLoteMedicamento = 0;
        $this->valorTabeladoMaximo = 0;
        $this->quantidadeTributadaPis = 0;
        $this->valorUnidadePis = 0;
        $this->valorUnidadePisB = 0;
        $this->quantidadeTributadaCofins = 0;
        $this->valorUnidadeCofins = 0;
        $this->valorUnidadeCofinsB = 0;

    }
}
