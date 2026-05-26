<?php

namespace Mg\Usuario;

use Mg\MgModel;

/**
 * Grupo de usuário (perfil/role) — versão enxuta com só o necessário
 * pro UsuarioResource (v1/auth/user) montar a lista de permissões.
 */
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
        'inativo' => 'datetime',
    ];

    public function GrupoUsuarioUsuarioS()
    {
        return $this->hasMany(GrupoUsuarioUsuario::class, 'codgrupousuario', 'codgrupousuario');
    }
}
