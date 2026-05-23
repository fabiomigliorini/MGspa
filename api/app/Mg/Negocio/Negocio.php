<?php

namespace Mg\Negocio;

use Mg\MgModel;

/**
 * Stub minimal — usado por NaturezaOperacao::NegocioS hasMany (validação destroy).
 */
class Negocio extends MgModel
{
    protected $table = 'tblnegocio';
    protected $primaryKey = 'codnegocio';

    public $timestamps = false;

    protected $casts = [
        'codnegocio' => 'integer',
    ];
}
