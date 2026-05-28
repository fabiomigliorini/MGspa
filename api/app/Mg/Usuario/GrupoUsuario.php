<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:39:33
 */

namespace Mg\Usuario;

use Mg\MgModel;
use Mg\Permissao\GrupoUsuarioPermissao;
use Mg\Usuario\GrupoUsuarioUsuario;
use Mg\Usuario\Usuario;

class GrupoUsuario extends MgModel
{
    protected $table = 'tblgrupousuario';
    protected $primaryKey = 'codgrupousuario';


    protected $fillable = [
        'grupousuario',
        'inativo',
        'observacoes'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codgrupousuario' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime'
    ];


    // Chaves Estrangeiras
    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }


    // Tabelas Filhas
    public function GrupoUsuarioPermissaoS()
    {
        return $this->hasMany(GrupoUsuarioPermissao::class, 'codgrupousuario', 'codgrupousuario');
    }

    public function GrupoUsuarioUsuarioS()
    {
        return $this->hasMany(GrupoUsuarioUsuario::class, 'codgrupousuario', 'codgrupousuario');
    }

}
