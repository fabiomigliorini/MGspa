<?php

namespace Mg\Estoque;

use Mg\MgModel;

/**
 * Stub minimal — usado por NaturezaOperacao::EstoqueMovimentoTipo belongsTo.
 */
class EstoqueMovimentoTipo extends MgModel
{
    protected $table = 'tblestoquemovimentotipo';
    protected $primaryKey = 'codestoquemovimentotipo';

    public $timestamps = false;

    protected $casts = [
        'codestoquemovimentotipo' => 'integer',
    ];
}
