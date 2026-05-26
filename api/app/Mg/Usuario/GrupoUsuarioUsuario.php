<?php

namespace Mg\Usuario;

use Mg\Usuario\Usuario;
use Mg\Filial\Filial;
use Mg\MgModel;

/**
 * Vínculo Usuário × Grupo × Filial — necessário pro UsuarioResource
 * montar `data.permissoes[]` no endpoint v1/auth/user.
 *
 * `Usuario()` aponta para `Mg\Usuario\Usuario` (model do Passport).
 */
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
}
