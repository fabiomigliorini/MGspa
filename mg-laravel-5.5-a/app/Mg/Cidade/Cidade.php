<?php

namespace Mg\Cidade;

use Mg\MgModel;
use Mg\Pessoa\Pessoa;

class Cidade extends MGModel
{
    protected $table = 'tblcidade';
    protected $primaryKey = 'codcidade';
    protected $fillable = [
        'codestado',
        'cidade',
        'sigla',
        'codigooficial',
    ];
    protected $dates = [
        'alteracao',
        'criacao',
    ];


    // Chaves Estrangeiras
    public function Estado()
    {
        return $this->belongsTo(Estado::class, 'codestado', 'codestado');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }


    // Tabelas Filhas
    public function PessoaS()
    {
        return $this->hasMany(Pessoa::class, 'codcidade', 'codcidade');
    }

    public function PessoaCobrancaS()
    {
        return $this->hasMany(Pessoa::class, 'codcidadecobranca', 'codcidade');
    }

}
