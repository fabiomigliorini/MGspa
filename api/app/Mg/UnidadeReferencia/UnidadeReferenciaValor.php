<?php

namespace Mg\UnidadeReferencia;

use Mg\MgModel;

class UnidadeReferenciaValor extends MgModel
{
    protected $table = 'tblunidadereferenciavalor';
    protected $primaryKey = 'codunidadereferenciavalor';

    protected $fillable = [
        'codunidadereferencia',
        'competencia',
        'valor',
    ];

    protected $casts = [
        'codunidadereferenciavalor' => 'integer',
        'codunidadereferencia' => 'integer',
        'competencia' => 'date',
        'valor' => 'float',
        'criacao' => 'datetime',
        'alteracao' => 'datetime',
        'codusuariocriacao' => 'integer',
        'codusuarioalteracao' => 'integer',
    ];

    // Chaves Estrangeiras
    public function UnidadeReferencia()
    {
        return $this->belongsTo(UnidadeReferencia::class, 'codunidadereferencia', 'codunidadereferencia');
    }
}
