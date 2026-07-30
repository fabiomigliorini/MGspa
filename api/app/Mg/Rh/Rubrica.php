<?php

namespace Mg\Rh;

use Mg\MgModel;
use Mg\Rh\ColaboradorRubrica;

class Rubrica extends MgModel
{
    protected $table = 'tblrubrica';
    protected $primaryKey = 'codrubrica';

    protected $fillable = [
        'descricao',
        'tipovalor',
        'tipocondicao',
        'valorpadrao',
        'valorunitariopadrao',
        'recorrente',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codrubrica' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'recorrente' => 'boolean',
        'valorpadrao' => 'float',
        'valorunitariopadrao' => 'float',
    ];

    // Tabelas Filhas
    public function ColaboradorRubricaS()
    {
        return $this->hasMany(ColaboradorRubrica::class, 'codrubrica', 'codrubrica');
    }
}
