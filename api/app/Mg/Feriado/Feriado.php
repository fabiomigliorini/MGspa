<?php

namespace Mg\Feriado;

use Mg\Usuario\Usuario;
use Mg\MgModel;

class Feriado extends MgModel
{
    protected $table = 'tblferiado';
    protected $primaryKey = 'codferiado';

    protected $fillable = [
        'data',
        'feriado',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'data' => 'datetime',
        'inativo' => 'datetime',
        'codferiado' => 'integer',
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
}
