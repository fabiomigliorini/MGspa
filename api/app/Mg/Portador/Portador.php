<?php

namespace Mg\Portador;

use Mg\MgModel;

/**
 * Stub minimal — usado por Usuario::Portador() etc. para acessar
 * a coluna `portador`. Substituir quando o domínio Portador for
 * migrado integralmente.
 */
class Portador extends MgModel
{
    protected $table = 'tblportador';
    protected $primaryKey = 'codportador';

    public $timestamps = false;

    protected $casts = [
        'codportador' => 'integer',
    ];
}
