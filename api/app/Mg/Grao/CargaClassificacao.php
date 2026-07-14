<?php

namespace Mg\Grao;

use Mg\MgModel;
use Mg\Classificacao\ParametroClassificacao;

/**
 * Classificação da carga: a leitura (%) de um parâmetro e o desconto (kg)
 * derivado por ele. Uma linha por parâmetro medido. O desconto é recalculado
 * pelo CargaService a partir da tabela resolvida (ver calcular()).
 */
class CargaClassificacao extends MgModel
{
    protected $table = 'tblcargaclassificacao';
    protected $primaryKey = 'codcargaclassificacao';

    protected $fillable = [
        'codcarga',
        'codparametroclassificacao',
        'leitura',
        'desconto',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcarga' => 'integer',
        'codcargaclassificacao' => 'integer',
        'codparametroclassificacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'desconto' => 'float',
        'leitura' => 'float',
    ];

    // Chaves Estrangeiras
    public function Carga()
    {
        return $this->belongsTo(Carga::class, 'codcarga', 'codcarga');
    }

    public function ParametroClassificacao()
    {
        return $this->belongsTo(ParametroClassificacao::class, 'codparametroclassificacao', 'codparametroclassificacao');
    }
}
