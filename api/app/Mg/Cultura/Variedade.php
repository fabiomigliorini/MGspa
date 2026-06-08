<?php
/**
 * Created by php artisan gerador:model.
 * Date: 03/Jun/2026 19:33:26
 */

namespace Mg\Cultura;

use Mg\MgModel;
use Mg\Fazenda\Plantio;
use Mg\Cultura\Cultura;

class Variedade extends MgModel
{
    protected $table = 'tblvariedade';
    protected $primaryKey = 'codvariedade';


    protected $fillable = [
        'codcultura',
        'inativo',
        'variedade'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcultura' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codvariedade' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime'
    ];


    // Chaves Estrangeiras
    public function Cultura()
    {
        return $this->belongsTo(Cultura::class, 'codcultura', 'codcultura');
    }


    // Tabelas Filhas
    public function PlantioS()
    {
        return $this->hasMany(Plantio::class, 'codvariedade', 'codvariedade');
    }

}
