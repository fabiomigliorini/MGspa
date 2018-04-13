<?php

namespace Permissao;

/**
 * Campos
 * @property  bigint                         $codpermissao                       NOT NULL
 * @property  varchar(100)                   $permissao                          NOT NULL
 * @property  varchar(600)                   $observacoes
 * @property  timestamp                      $alteracao
 * @property  bigint                         $codusuarioalteracao
 * @property  timestamp                      $criacao
 * @property  bigint                         $codusuariocriacao
 *
 * Chaves Estrangeiras
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 *
 * Tabelas Filhas
 * @property  GrupoUsuario[]        $GrupoUsuario
 */

use App\Mg\MgModel;
use Usuario\Usuario;
use Usuario\GrupoUsuario;

class Permissao extends MGModel
{
    protected $table = 'tblpermissao';
    protected $primaryKey = 'codpermissao';
    protected $fillable = [
        'codpermissao',
        'permissao',
        'observacoes',
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
    public function GrupoUsuario()
    {
        return $this->belongsToMany(GrupoUsuario::class, 'tblgrupousuariopermissao', 'codpermissao', 'codgrupousuario');
    }

}
