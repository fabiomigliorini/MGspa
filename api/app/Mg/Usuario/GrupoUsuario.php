<?php

namespace Mg\Usuario;

use Mg\Usuario\Usuario;
use Mg\MgModel;
use Mg\Permissao\GrupoUsuarioPermissao;

class GrupoUsuario extends MgModel
{
    protected $table = 'tblgrupousuario';
    protected $primaryKey = 'codgrupousuario';

    protected $fillable = [
        'grupousuario',
        'inativo',
        'observacoes',
    ];

    protected $casts = [
        'codgrupousuario' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
    ];

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function GrupoUsuarioPermissaoS()
    {
        return $this->hasMany(GrupoUsuarioPermissao::class, 'codgrupousuario', 'codgrupousuario');
    }

    public function GrupoUsuarioUsuarioS()
    {
        return $this->hasMany(GrupoUsuarioUsuario::class, 'codgrupousuario', 'codgrupousuario');
    }
}
