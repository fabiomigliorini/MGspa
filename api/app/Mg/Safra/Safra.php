<?php
/**
 * Created by php artisan gerador:model.
 * Date: 03/Jun/2026 19:33:44
 */

namespace Mg\Safra;

use Mg\MgModel;
use Mg\Fazenda\Plantio;
use Mg\Cultura\Cultura;

class Safra extends MgModel
{
    protected $table = 'tblsafra';
    protected $primaryKey = 'codsafra';

    protected $appends = ['usuariocriacao', 'usuarioalteracao'];


    protected $fillable = [
        'codcultura',
        'datafim',
        'datainicio',
        'inativo',
        'safra'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcultura' => 'integer',
        'codsafra' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'datafim' => 'date',
        'datainicio' => 'date',
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
        return $this->hasMany(Plantio::class, 'codsafra', 'codsafra');
    }

}
