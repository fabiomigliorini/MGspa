<?php

namespace Mg\Classificacao;

use Mg\MgModel;
use Mg\Cultura\Cultura;

/**
 * Parâmetro de classificação de grãos POR CULTURA (Umidade, Impureza,
 * Avariados...). Guarda o cálculo INTEIRO — não há mais camada de tabela:
 *  - metodo     = NORMALIZADO ((leitura-tol)/(100-tol)) | FATOR ((leitura-tol)*fator/100)
 *  - reduzbase  = o desconto deste reduz a base (peso) dos parâmetros seguintes
 *  - ordem      = posição na cascata (impureza -> umidade -> defeitos)
 *  - tolerancia = o padrão da norma (14% umidade, 1% impureza, ...)
 *  - fator      = usado quando metodo = FATOR (taxa comercial por ponto)
 *  - desagio    = abatimento sobre a quebra, quando metodo = NORMALIZADO
 *
 * NORMALIZADO é a fórmula da IN MAPA 11/2007 (soja) e 60/2011 (milho) — a mesma
 * PDI/PDU da Cartilha de Classificação da Aprosoja e as eq. 02/05 do boletim
 * AGAIS 01/09. FATOR existe para o comprador que cobra taxa de secagem por ponto
 * em vez da fórmula, e nunca é o padrão semeado.
 */
class ParametroClassificacao extends MgModel
{
    protected $table = 'tblparametroclassificacao';
    protected $primaryKey = 'codparametroclassificacao';

    const METODOS = ['FATOR', 'NORMALIZADO'];

    protected $fillable = [
        'codcultura',
        'parametroclassificacao',
        'metodo',
        'reduzbase',
        'ordem',
        'tolerancia',
        'fator',
        'desagio',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcultura' => 'integer',
        'codparametroclassificacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'desagio' => 'float',
        'fator' => 'float',
        'inativo' => 'datetime',
        'ordem' => 'integer',
        'reduzbase' => 'boolean',
        'tolerancia' => 'float',
    ];

    // Chaves Estrangeiras
    public function Cultura()
    {
        return $this->belongsTo(Cultura::class, 'codcultura', 'codcultura');
    }
}
