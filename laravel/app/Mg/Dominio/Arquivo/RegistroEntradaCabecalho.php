<?php

namespace Mg\Dominio\Arquivo;

class RegistroEntradaCabecalho extends Registro
{
    function __construct()
    {
        $this->campos = [
            'identificador' => [
                'tamanho' => 2,
                'tipo' => 'char'
            ],
            'codigoEmpresa' => [
                'tamanho' => 7,
                'tipo' => 'numeric'
            ],
            'cnpj' => [
                'tamanho' => 14,
                'tipo' => 'numeric'
            ],
            'dataInicial' => [
                'tamanho' => 10,
                'tipo' => 'date',
                'formato' => 'd/m/Y'
            ],
            'dataFinal' => [
                'tamanho' => 10,
                'tipo' => 'date',
                'formato' => 'd/m/Y'
            ],
            'fixoN' => [
                'tamanho' => 1,
                'tipo' => 'char',
            ],
            'tipo' => [
                'tamanho' => 2,
                'tipo' => 'char',
            ],
            'zerosA' => [
                'tamanho' => 5,
                'tipo' => 'numeric',
            ],
            'sistema' => [
                'tamanho' => 1,
                'tipo' => 'numeric',
            ],

        ];

        $this->identificador = '01';
        $this->fixoN = 'N';
        $this->tipo = '02';
        $this->zerosA = 0;
        $this->sistema = 0;
    }
}
