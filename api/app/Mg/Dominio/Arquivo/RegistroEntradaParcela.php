<?php

namespace Mg\Dominio\Arquivo;

class RegistroEntradaParcela extends Registro
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
            'vencimento' => [
                'tamanho' => 10,
                'tipo' => 'date',
                'formato' => 'd/m/Y',
            ],
            'tipo' => [
                'tamanho' => 1,
                'tipo' => 'char'
            ],
            'valorParcela' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'aliquotaCrf' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorCrf' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorIrrf' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorIss' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorInss' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
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
            'valorCsll' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorIrrfpf' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'brancos' => [
                'tamanho' => 87,
                'tipo' => 'char',
            ],
        ];

        $this->identificador = '09';
        $this->tipo = '0';
        $this->aliquotaCrf = 0;
        $this->valorCrf = 0;
        $this->valorIrrf = 0;
        $this->valorIss = 0;
        $this->valorInss = 0;
        $this->valorPis = 0;
        $this->valorCofins = 0;
        $this->valorCsll = 0;
        $this->valorIrrfpf = 0;

    }
}
