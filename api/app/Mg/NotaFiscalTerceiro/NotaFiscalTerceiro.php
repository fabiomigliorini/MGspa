<?php

namespace Mg\NotaFiscalTerceiro;

use Mg\MgModel;

/**
 * Stub minimal — usado por NaturezaOperacao::NotaFiscalTerceiroS hasMany.
 */
class NotaFiscalTerceiro extends MgModel
{
    protected $table = 'tblnotafiscalterceiro';
    protected $primaryKey = 'codnotafiscalterceiro';

    public $timestamps = false;
}
