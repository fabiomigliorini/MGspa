<?php

namespace Mg\ContaContabil;

use App\Models\Usuario;
use Mg\MgModel;

class ContaContabil extends MgModel
{
    protected $table = 'tblcontacontabil';
    protected $primaryKey = 'codcontacontabil';

    protected $fillable = ['contacontabil', 'numero', 'inativo'];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'codcontacontabil' => 'integer',
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
