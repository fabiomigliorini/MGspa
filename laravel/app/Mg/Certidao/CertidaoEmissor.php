<?php

namespace Mg\Certidao;

use Mg\MgModel;

use Mg\Pessoa\PessoaCertidao;

class CertidaoEmissor extends MGModel
{
    const SEFAZ_MT = 1;

    protected $table = 'tblcertidaoemissor';
    protected $primaryKey = 'codcertidaoemissor';
    protected $fillable = [
        'certidaoemissor',
    ];
    protected $dates = [
        'inativo',
        'alteracao',
        'criacao',
    ];

    // Tabelas Filhas
    public function PessoaCertidaoS()
    {
        return $this->hasMany(PessoaCertidao::class, 'codcertidaoemissor', 'codcertidaoemissor');
    }
}
