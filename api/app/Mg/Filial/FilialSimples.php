<?php

namespace Mg\Filial;

use Mg\MgModel;

// Percentuais do Simples de um mes da filial (TASK-280): a nota de servico puxa
// daqui pela filial e pelo mes da competencia. competencia = 1o dia do mes.
class FilialSimples extends MgModel
{
    protected $table = 'tblfilialsimples';
    protected $primaryKey = 'codfilialsimples';

    protected $fillable = [
        'codfilial',
        'competencia',
        'tributospercentual',
        'issaliquota',
    ];

    protected $casts = [
        'codfilialsimples' => 'integer',
        'codfilial' => 'integer',
        'competencia' => 'date',
        'tributospercentual' => 'float',
        'issaliquota' => 'float',
        'criacao' => 'datetime',
        'alteracao' => 'datetime',
        'codusuariocriacao' => 'integer',
        'codusuarioalteracao' => 'integer',
    ];

    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }
}
