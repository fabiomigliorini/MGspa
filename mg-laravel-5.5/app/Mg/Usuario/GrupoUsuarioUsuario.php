<?php

namespace Usuario;

/**
 * Campos
 * @property  bigint                         $codgrupousuariousuario             NOT NULL DEFAULT nextval('tblgrupousuariousuario_codgrupousuariousuario_seq'::regclass)
 * @property  bigint                         $codgrupousuario                    NOT NULL
 * @property  bigint                         $codusuario                         NOT NULL
 * @property  bigint                         $codfilial                          NOT NULL
 * @property  timestamp                      $alteracao
 * @property  bigint                         $codusuarioalteracao
 * @property  timestamp                      $criacao
 * @property  bigint                         $codusuariocriacao
 *
 * Chaves Estrangeiras
 * @property  GrupoUsuario                   $GrupoUsuario
 * @property  Usuario                        $Usuario
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 * @property  Filial                         $Filial
 *
 * Tabelas Filhas
 */
use App\Mg\MGModel;
use Filial\Filial;

class GrupoUsuarioUsuario extends MGModel
{
    protected $table = 'tblgrupousuariousuario';
    protected $primaryKey = 'codgrupousuariousuario';
    protected $fillable = [
        'codgrupousuario',
        'codusuario',
        'codfilial',
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

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }


    // Tabelas Filhas

}
