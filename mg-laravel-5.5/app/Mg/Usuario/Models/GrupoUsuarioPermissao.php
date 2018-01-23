<?php

namespace App\Mg\Usuario\Models;

/**
 * Campos
 * @property  bigint                         $codgrupousuariopermissao           NOT NULL DEFAULT nextval('tblgrupousuariopermissao_codgrupousuariopermissao_seq'::regclass)
 * @property  bigint                         $codgrupousuario                    NOT NULL
 * @property  bigint                         $codpermissao                       NOT NULL
 * @property  timestamp                      $alteracao
 * @property  bigint                         $codusuarioalteracao
 * @property  timestamp                      $criacao
 * @property  bigint                         $codusuariocriacao
 *
 * Chaves Estrangeiras
 * @property  GrupoUsuario                   $GrupoUsuario
 * @property  Permissao                      $Permissao
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 *
 * Tabelas Filhas
 */
use Illuminate\Database\Eloquent\Model; // <-- Trocar por MGModel
class GrupoUsuarioPermissao extends Model
{
    /* Limpar depois que estender de MGModel*/
    const CREATED_AT = 'criacao';
    const UPDATED_AT = 'alteracao';
    public $timestamps = true;
    /* -- */

    protected $table = 'tblgrupousuariopermissao';
    protected $primaryKey = 'codgrupousuariopermissao';
    protected $fillable = [
        'codgrupousuario',
        'codpermissao',
    ];
    protected $dates = [
        'alteracao',
        'criacao',
    ];


    // Chaves Estrangeiras
    public function GrupoUsuario()
    {
        return $this->belongsTo(GrupoUsuario::class, 'codgrupousuario', 'codgrupousuario');
    }

    public function Permissao()
    {
        return $this->belongsTo(Permissao::class, 'codpermissao', 'codpermissao');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }


    // Tabelas Filhas

}
