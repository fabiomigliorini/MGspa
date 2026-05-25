<?php

namespace Mg\NaturezaOperacao;

use Mg\MgModel;

/**
 * Operacao (Entrada/Saida) — model simples, FK do NaturezaOperacao.
 */
class Operacao extends MgModel
{
    protected $table = 'tbloperacao';
    protected $primaryKey = 'codoperacao';

    public $timestamps = false;

    protected $casts = [
        'codoperacao' => 'integer',
    ];
}
