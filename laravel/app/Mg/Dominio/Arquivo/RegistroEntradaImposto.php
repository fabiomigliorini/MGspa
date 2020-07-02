<?php

namespace Mg\Dominio\Arquivo;

class RegistroEntradaImposto extends Registro
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
            'codigoImposto' => [
                'tamanho' => 7,
                'tipo' => 'numeric'
            ],
            'percentualReducaoBc' => [
                'tamanho' => 5,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'bc' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'aliquota' => [
                'tamanho' => 5,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorImposto' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorIsento' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorOutras' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorIpi' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorSt' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorContabil' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'codigoRecolhimento' => [
                'tamanho' => 6,
                'tipo' => 'char',
            ],
            'valorNaoTributada' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'valorParcelaReduzida' => [
                'tamanho' => 13,
                'tipo' => 'decimal',
                'casas' => 2
            ],
            'brancosA' => [
                'tamanho' => 74,
                'tipo' => 'char',
            ],
        ];

        $this->identificador = '03';
        $this->percentualReducaoBc = 0;
        $this->valorIsento = 0;
        $this->valorOutras = 0;
        $this->valorIpi = 0;
        $this->valorSt = 0;
        $this->valorNaoTributada = 0;
        $this->valorParcelaReduzida = 0;

    }
}
