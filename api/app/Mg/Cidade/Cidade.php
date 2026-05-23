<?php

namespace Mg\Cidade;

use Mg\MgModel;

/**
 * Stub minimal — usado por TributacaoRegra::CidadeDestino() etc.
 * Substituir quando o domínio Cidade for migrado integralmente.
 */
class Cidade extends MgModel
{
    protected $table = 'tblcidade';
    protected $primaryKey = 'codcidade';

    public $timestamps = false;

    protected $casts = [
        'codcidade' => 'integer',
        'codestado' => 'integer',
    ];

    public function Estado()
    {
        return $this->belongsTo(Estado::class, 'codestado', 'codestado');
    }
}
