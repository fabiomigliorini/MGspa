<?php

namespace Mg\Certidao;

use Mg\Usuario\Usuario;
use Mg\MgModel;
use Mg\Pessoa\PessoaCertidao;

class CertidaoEmissor extends MgModel
{
    const SEFAZ_MT = 1;

    protected $table = 'tblcertidaoemissor';
    protected $primaryKey = 'codcertidaoemissor';

    protected $fillable = [
        'certidaoemissor',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'codcertidaoemissor' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
    ];

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function PessoaCertidaoS()
    {
        return $this->hasMany(PessoaCertidao::class, 'codcertidaoemissor', 'codcertidaoemissor');
    }
}
