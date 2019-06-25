<?php

namespace Mg\Pessoa;

use Mg\MgModel;

use Mg\Certidao\CertidaoEmissor;
use Mg\Certidao\CertidaoTipo;

class PessoaCertidao extends MGModel
{
    protected $table = 'tblpessoacertidao';
    protected $primaryKey = 'codpessoacertidao';
    protected $fillable = [
        'codpessoa',
        'codcertidaoemissor',
        'codcertidaotipo',
        'numero',
        'autenticacao',
        'validade',
    ];
    protected $dates = [
        'inativo',
        'alteracao',
        'criacao',
        'validade',
    ];

    // Chaves Estrangeiras
    public function CertidaoEmissor()
    {
        return $this->belongsTo(CertidaoEmissor::class, 'codcertidaoemissor', 'codcertidaoemissor');
    }

    public function CertidaoTipo()
    {
        return $this->belongsTo(CertidaoTipo::class, 'codcertidaotipo', 'codcertidaotipo');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

}
