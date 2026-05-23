<?php

namespace Mg\Filial;

use Mg\MgModel;

/**
 * Stub do model Filial — só o suficiente pros relacionamentos belongsTo
 * dos outros models do domínio (UnidadeNegocio etc). Quando o domínio
 * Filial for migrado integralmente, sobrescrever com a versão completa.
 */
class Filial extends MgModel
{
    protected $table = 'tblfilial';
    protected $primaryKey = 'codfilial';

    public $timestamps = false;

    protected $casts = [
        'codfilial' => 'integer',
    ];
}
