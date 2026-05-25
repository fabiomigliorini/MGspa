<?php

namespace Mg\Filial;

use Mg\MgModel;

class UnidadeNegocio extends MgModel
{
    protected $table = 'tblunidadenegocio';
    protected $primaryKey = 'codunidadenegocio';

    protected $fillable = [
        'codfilial',
        'descricao',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'codfilial' => 'integer',
        'codunidadenegocio' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
    ];

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function SetorS()
    {
        return $this->hasMany(Setor::class, 'codunidadenegocio', 'codunidadenegocio');
    }
}
