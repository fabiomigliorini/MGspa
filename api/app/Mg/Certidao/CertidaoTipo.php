<?php

namespace Mg\Certidao;

use Mg\MgModel;

/**
 * Stub minimal — Controller do legacy é código quebrado (`dd()` em
 * todos os métodos), mas o model existe e é usado pelo PessoaCertidao
 * via relacionamento belongsTo.
 */
class CertidaoTipo extends MgModel
{
    protected $table = 'tblcertidaotipo';
    protected $primaryKey = 'codcertidaotipo';

    protected $casts = [
        'codcertidaotipo' => 'integer',
    ];
}
