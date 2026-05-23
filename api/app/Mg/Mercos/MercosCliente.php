<?php

namespace Mg\Mercos;

use Mg\MgModel;

class MercosCliente extends MgModel
{
    protected $table = 'tblmercoscliente';
    protected $primaryKey = 'codmercoscliente';

    protected $fillable = [
        'clienteid',
        'codpessoa',
    ];

    protected $casts = [
        'codmercoscliente' => 'integer',
        'codpessoa' => 'integer',
        'clienteid' => 'integer',
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
    ];
}
