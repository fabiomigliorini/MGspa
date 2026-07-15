<?php

namespace Mg\Classificacao;

use Mg\MgModel;

/**
 * Catálogo (global) de parâmetros de classificação de grãos: Umidade, Impureza,
 * Avariados... Guarda o que o parâmetro É — intrínseco e usado pela FÓRMULA:
 *  - metodo    = FATOR (desconto por ponto) | NORMALIZADO ((x-tol)/(100-tol))
 *  - reduzbase = o desconto deste reduz a base (peso) dos parâmetros seguintes
 * A ordem da cascata e os números (tolerância/fator/deságio) vivem por tabela em
 * tbltabelaclassificacaoitem — este cadastro é só a definição do parâmetro.
 */
class ParametroClassificacao extends MgModel
{
    protected $table = 'tblparametroclassificacao';
    protected $primaryKey = 'codparametroclassificacao';

    const METODOS = ['FATOR', 'NORMALIZADO'];

    protected $fillable = [
        'parametroclassificacao',
        'metodo',
        'reduzbase',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codparametroclassificacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'reduzbase' => 'boolean',
    ];
}
