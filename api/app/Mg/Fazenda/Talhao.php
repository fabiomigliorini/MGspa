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

    protected $appends = ['usuariocriacao', 'usuarioalteracao'];


    protected $fillable = [
        'area',
        'codfazenda',
        'cor',
        'geometria',
        'inativo',
        'latitude',
        'longitude',
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
        'geometria' => 'array',
        'inativo' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float'
    ];


    // Mantém tblfazenda.areatotal em dia: qualquer gravação/exclusão de talhão
    // recalcula a soma da fazenda (área não é digitada, deriva dos talhões).
    protected static function booted()
    {
        static::saved(fn (Talhao $t) => FazendaService::recalcularAreaTotal($t->codfazenda));
        static::deleted(fn (Talhao $t) => FazendaService::recalcularAreaTotal($t->codfazenda));
    }


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
