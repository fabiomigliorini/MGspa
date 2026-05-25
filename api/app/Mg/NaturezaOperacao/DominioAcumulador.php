<?php

namespace Mg\NaturezaOperacao;

use Mg\MgModel;

/**
 * Stub minimal — usado por Cfop::DominioAcumuladorS hasMany.
 */
class DominioAcumulador extends MgModel
{
    protected $table = 'tbldominioacumulador';
    protected $primaryKey = 'coddominioacumulador';

    public $timestamps = false;
}
