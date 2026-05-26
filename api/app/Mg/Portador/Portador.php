<?php

namespace Mg\Portador;

use Mg\MgModel;

/**
 * Stub minimal — só o suficiente pro belongsTo do model Usuario
 * resolver e o UsuarioResource pegar `$usuario->Portador->portador`.
 * Quando o domínio Portador for migrado, sobrescrever.
 */
class Portador extends MgModel
{
    protected $table = 'tblportador';
    protected $primaryKey = 'codportador';

    protected $casts = [
        'codportador' => 'integer',
        'codfilial' => 'integer',
    ];
}
