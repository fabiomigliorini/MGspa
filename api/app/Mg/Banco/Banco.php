<?php

namespace Mg\Banco;

use Mg\Usuario\Usuario;
use Mg\MgModel;

class Banco extends MgModel
{
    protected $table = 'tblbanco';
    protected $primaryKey = 'codbanco';

    protected $fillable = ['banco', 'inativo', 'numerobanco', 'sigla'];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'codbanco' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'numerobanco' => 'integer',
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
