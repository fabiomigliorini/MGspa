<?php

namespace Usuario;

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
use App\Mg\MgModel;
use Permissao\Permissao;

class GrupoUsuarioPermissao extends MGModel
{
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
