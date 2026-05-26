<?php

namespace Mg\Pessoa;

use Mg\Usuario\Usuario;
use Mg\MgModel;

class Etnia extends MgModel
{
    protected $table = 'tbletnia';
    protected $primaryKey = 'codetnia';

    protected $fillable = [
        'etnia',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'codetnia' => 'integer',
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
        return $this->hasMany(Pessoa::class, 'codetnia', 'codetnia');
    }
}
