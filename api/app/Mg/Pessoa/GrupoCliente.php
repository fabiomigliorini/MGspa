<?php

namespace Mg\Pessoa;

use App\Models\Usuario;
use Mg\MgModel;

class GrupoCliente extends MgModel
{
    protected $table = 'tblgrupocliente';
    protected $primaryKey = 'codgrupocliente';

    protected $fillable = [
        'grupocliente',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'codgrupocliente' => 'integer',
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
        return $this->hasMany(Pessoa::class, 'codgrupocliente', 'codgrupocliente');
    }
}
