<?php

namespace Mg\NfeTerceiro;

use Mg\MgModel;

/**
 * Stub minimal — usado por NaturezaOperacao::NfeTerceiroS hasMany.
 */
class NfeTerceiro extends MgModel
{
    protected $table = 'tblnfeterceiro';
    protected $primaryKey = 'codnfeterceiro';

    public $timestamps = false;

    protected $casts = [
        'codnfeterceiro' => 'integer',
    ];
}
