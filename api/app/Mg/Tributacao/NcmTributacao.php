<?php

namespace Mg\Tributacao;

use Mg\MgModel;

/**
 * Stub minimal pro hasMany do Tributacao. Substituir quando migrar
 * NCM/NotaFiscal.
 */
class NcmTributacao extends MgModel
{
    protected $table = 'tblncmtributacao';
    protected $primaryKey = 'codncmtributacao';

    public $timestamps = false;
}
