<?php

namespace Mg\Certidao;

use Mg\MgModel;

use Mg\Pessoa\PessoaCertidao;

class CertidaoTipo extends MGModel
{
    protected $table = 'tblcertidaotipo';
    protected $primaryKey = 'codcertidaotipo';
    protected $fillable = [
        'certidaotipo',
        'sigla',
    ];
    protected $dates = [
        'inativo',
        'alteracao',
        'criacao',
    ];

    // Tabelas Filhas
    public function PessoaCertidaoS()
    {
        return $this->hasMany(PessoaCertidao::class, 'codcertidaotipo', 'codcertidaotipo');
    }
}
