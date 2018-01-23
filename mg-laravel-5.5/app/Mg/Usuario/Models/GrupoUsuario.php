<?php

namespace App\Mg\Usuario\Models;

/**
 * Campos
 * @property  bigint                         $codgrupousuario                    NOT NULL DEFAULT nextval('tblgrupousuario_codgrupousuario_seq'::regclass)
 * @property  varchar(50)                    $grupousuario                       NOT NULL
 * @property  varchar(600)                   $observacoes
 * @property  timestamp                      $alteracao
 * @property  bigint                         $codusuarioalteracao
 * @property  timestamp                      $criacao
 * @property  bigint                         $codusuariocriacao
 * @property  timestamp                      $inativo
 *
 * Chaves Estrangeiras
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 *
 * Tabelas Filhas
 * @property  GrupoUsuarioPermissao[]        $GrupoUsuarioPermissaoS
 * @property  GrupoUsuarioUsuario[]          $GrupoUsuarioUsuarioS
 */
 use Illuminate\Database\Eloquent\Model; // <-- Trocar por MGModel

class GrupoUsuario extends Model
{
    /* Limpar depois que estender de MGModel*/
    const CREATED_AT = 'criacao';
    const UPDATED_AT = 'alteracao';
    public $timestamps = true;
    /* -- */

    protected $table = 'tblgrupousuario';
    protected $primaryKey = 'codgrupousuario';
    protected $fillable = [
        'grupousuario',
        'observacoes',
        'inativo',
    ];
    protected $dates = [
        'alteracao',
        'criacao',
        'inativo',
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
