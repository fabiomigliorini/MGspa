<?php

namespace Mg\Pessoa;

use Mg\Usuario\Usuario;
use Mg\MgModel;

class EstadoCivil extends MgModel
{
    protected $table = 'tblestadocivil';
    protected $primaryKey = 'codestadocivil';

    protected $fillable = [
        'estadocivil',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'codestadocivil' => 'integer',
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

    public function PessoaS()
    {
        return $this->hasMany(Pessoa::class, 'codestadocivil', 'codestadocivil');
    }
}
