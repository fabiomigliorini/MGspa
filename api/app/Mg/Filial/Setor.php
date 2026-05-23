<?php

namespace Mg\Filial;

use Mg\MgModel;

class Setor extends MgModel
{
    protected $table = 'tblsetor';
    protected $primaryKey = 'codsetor';

    protected $fillable = [
        'codtiposetor',
        'codunidadenegocio',
        'inativo',
        'indicadorcaixa',
        'indicadorcoletivo',
        'indicadorvendedor',
        'setor',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'codsetor' => 'integer',
        'codtiposetor' => 'integer',
        'codunidadenegocio' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'indicadorcaixa' => 'boolean',
        'indicadorcoletivo' => 'boolean',
        'indicadorvendedor' => 'boolean',
    ];

    public function TipoSetor()
    {
        return $this->belongsTo(TipoSetor::class, 'codtiposetor', 'codtiposetor');
    }

    public function UnidadeNegocio()
    {
        return $this->belongsTo(UnidadeNegocio::class, 'codunidadenegocio', 'codunidadenegocio');
    }
}
