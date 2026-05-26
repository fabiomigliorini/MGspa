<?php

namespace Mg\Dominio\Arquivo;

class RegistroPessoa extends Registro
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
            'siglaEstado' => [
                'tamanho' => 2,
                'tipo' => 'char'
            ],
            'codigoConta' => [
                'tamanho' => 7,
                'tipo' => 'char'
            ],
            'codigoMunicipio' => [
                'tamanho' => 7,
                'tipo' => 'numeric'
            ],
            'nomeReduzido' => [
                'tamanho' => 10,
                'tipo' => 'char'
            ],
            'nome' => [
                'tamanho' => 40,
                'tipo' => 'char'
            ],
            'endereco' => [
                'tamanho' => 40,
                'tipo' => 'char'
            ],
            'enderecoNumero' => [
                'tamanho' => 7,
                'tipo' => 'numeric'
            ],
            'brancos1' => [
                'tamanho' => 30,
                'tipo' => 'char'
            ],
            'cep' => [
                'tamanho' => 8,
                'tipo' => 'numeric'
            ],
            'inscricao' => [
                'tamanho' => 14,
                'tipo' => 'char'
            ],
            'inscricaoEstadual' => [
                'tamanho' => 20,
                'tipo' => 'char'
            ],
            'fone' => [
                'tamanho' => 14,
                'tipo' => 'char'
            ],
            'fax' => [
                'tamanho' => 14,
                'tipo' => 'char'
            ],
            'agropecuario' => [
                'tamanho' => 1,
                'tipo' => 'char'
            ],
            'icms' => [
                'tamanho' => 1,
                'tipo' => 'char'
            ],
            'tipoInscricao' => [
                'tamanho' => 1,
                'tipo' => 'numeric'
            ],
            'inscricaoMunicipal' => [
                'tamanho' => 20,
                'tipo' => 'char'
            ],
            'bairro' => [
                'tamanho' => 20,
                'tipo' => 'char'
            ],
            'ddd' => [
                'tamanho' => 4,
                'tipo' => 'numeric'
            ],
            'codigoPais' => [
                'tamanho' => 7,
                'tipo' => 'numeric'
            ],
            'inscricaoSuframa' => [
                'tamanho' => 9,
                'tipo' => 'char'
            ],
            'branco2' => [
                'tamanho' => 100,
                'tipo' => 'char'
            ],

        ];

        $this->identificador = 11;
        $this->agropecuario = 'N';
        $this->icms = 'S';
        $this->codigoPais = 30;
    }
}
