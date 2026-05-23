<?php

namespace Mg\NotaFiscal;

use Mg\MgModel;

/**
 * Stub minimal — usado por NaturezaOperacao::NotaFiscalS hasMany e por
 * outras dependências. Substituir quando o domínio NotaFiscal for migrado.
 */
class NotaFiscal extends MgModel
{
    protected $table = 'tblnotafiscal';
    protected $primaryKey = 'codnotafiscal';

    public $timestamps = false;

    protected $casts = [
        'codnotafiscal' => 'integer',
    ];
}
