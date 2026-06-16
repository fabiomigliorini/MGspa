<?php

namespace Mg\Cultura;

use Mg\MgModel;
use Mg\Tributacao\Tributo;
use Mg\UnidadeReferencia\UnidadeReferencia;

class CulturaTributo extends MgModel
{
    protected $table = 'tblculturatributo';
    protected $primaryKey = 'codculturatributo';

    protected $appends = ['usuariocriacao', 'usuarioalteracao'];

    protected $fillable = [
        'codcultura',
        'codtributo',
        'base',
        'codunidadereferencia',
        'percentual',
        'grupofethab',
        'funrural',
        'ordem',
        'inativo',
    ];

    protected $casts = [
        'codculturatributo' => 'integer',
        'codcultura' => 'integer',
        'codtributo' => 'integer',
        'codunidadereferencia' => 'integer',
        'percentual' => 'float',
        'grupofethab' => 'boolean',
        'funrural' => 'boolean',
        'ordem' => 'integer',
        'inativo' => 'datetime',
        'criacao' => 'datetime',
        'alteracao' => 'datetime',
        'codusuariocriacao' => 'integer',
        'codusuarioalteracao' => 'integer',
    ];

    // Chaves Estrangeiras
    public function Cultura()
    {
        return $this->belongsTo(Cultura::class, 'codcultura', 'codcultura');
    }

    public function Tributo()
    {
        return $this->belongsTo(Tributo::class, 'codtributo', 'codtributo');
    }

    public function UnidadeReferencia()
    {
        return $this->belongsTo(UnidadeReferencia::class, 'codunidadereferencia', 'codunidadereferencia');
    }
}
