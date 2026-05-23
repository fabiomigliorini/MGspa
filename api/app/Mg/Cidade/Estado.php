<?php

namespace Mg\Cidade;

use Mg\MgModel;

/**
 * Stub minimal do Estado — usado por Veiculo::Estado() etc. Substituir
 * quando o domínio Cidade for migrado integralmente.
 */
class Estado extends MgModel
{
    protected $table = 'tblestado';
    protected $primaryKey = 'codestado';

    public $timestamps = false;

    protected $casts = [
        'codestado' => 'integer',
        'codpais' => 'integer',
    ];
}
