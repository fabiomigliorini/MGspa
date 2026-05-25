<?php

namespace Mg\NotaFiscal;

use Mg\MgModel;

/**
 * Stub minimal — usado por Cfop::NotaFiscalProdutoBarraS hasMany.
 */
class NotaFiscalProdutoBarra extends MgModel
{
    protected $table = 'tblnotafiscalprodutobarra';
    protected $primaryKey = 'codnotafiscalprodutobarra';

    public $timestamps = false;
}
