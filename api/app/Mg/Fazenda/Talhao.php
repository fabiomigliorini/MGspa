<?php
/**
 * Created by php artisan gerador:model.
 * Date: 03/Jun/2026 19:33:37
 */

namespace Mg\Fazenda;

use Mg\MgModel;
use Mg\Fazenda\Plantio;
use Mg\Fazenda\Fazenda;

class Talhao extends MgModel
{
    protected $table = 'tbltalhao';
    protected $primaryKey = 'codtalhao';


    protected $fillable = [
        'area',
        'codfazenda',
        'inativo',
        'talhao'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'area' => 'float',
        'codfazenda' => 'integer',
        'codtalhao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime'
    ];


    // Chaves Estrangeiras
    public function Fazenda()
    {
        return $this->belongsTo(Fazenda::class, 'codfazenda', 'codfazenda');
    }


    // Tabelas Filhas
    public function PlantioS()
    {
        return $this->hasMany(Plantio::class, 'codtalhao', 'codtalhao');
    }

}
