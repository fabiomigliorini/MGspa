<?php

namespace Mg\Pessoa;

use Mg\MgModel;

/**
 * Stub minimal do model Pessoa — só pra hasMany() dos models de
 * referência (Etnia, EstadoCivil, GrauInstrucao, etc.) resolver o autoload.
 * Quando o domínio Pessoa for migrado integralmente, sobrescrever.
 */
class Pessoa extends MgModel
{
    protected $table = 'tblpessoa';
    protected $primaryKey = 'codpessoa';

    protected $casts = [
        'codpessoa' => 'integer',
    ];
}
