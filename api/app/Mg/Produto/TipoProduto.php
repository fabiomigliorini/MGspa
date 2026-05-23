<?php

namespace Mg\Produto;

use Mg\MgModel;

/**
 * Stub minimal — usado por TributacaoRegra::TipoProduto(). Substituir
 * quando o domínio Produto for migrado.
 */
class TipoProduto extends MgModel
{
    protected $table = 'tbltipoproduto';
    protected $primaryKey = 'codtipoproduto';

    public $timestamps = false;

    protected $casts = [
        'codtipoproduto' => 'integer',
    ];
}
