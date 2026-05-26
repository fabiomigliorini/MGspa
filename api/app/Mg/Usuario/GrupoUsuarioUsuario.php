<?php

namespace Mg\Usuario;

use App\Usuario\Usuario;
use Mg\Filial\Filial;
use Mg\MgModel;

class GrupoUsuarioUsuario extends MgModel
{
    protected $table = 'tblgrupousuariousuario';
    protected $primaryKey = 'codgrupousuariousuario';

    protected $fillable = [
        'codfilial',
        'codgrupousuario',
        'codusuario',
    ];

    protected $casts = [
        'codfilial' => 'integer',
        'codgrupousuario' => 'integer',
        'codgrupousuariousuario' => 'integer',
        'codusuario' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
    ];

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function GrupoUsuario()
    {
        return $this->belongsTo(GrupoUsuario::class, 'codgrupousuario', 'codgrupousuario');
    }

    public function Usuario()
    {
        return $this->belongsTo(Usuario::class, 'codusuario', 'codusuario');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }
}
