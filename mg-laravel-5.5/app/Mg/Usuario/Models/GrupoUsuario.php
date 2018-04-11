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
use App\Mg\Model\MGModel;
use Carbon\Carbon;

class GrupoUsuario extends MGModel
{
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

    public function activate () {
        $this->inativo = null;
        $this->update();
        return $this;
    }

    public function inactivate ($date = null) {
        if (empty($date)) {
            $date = Carbon::now();
        }
        $this->inativo = $date;
        $this->update();
        return $this;
    }

    public static function search(array $filter = null, array $sort = null, array $fields = null)
    {
        $qry = GrupoUsuario::query();

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        if (!empty($filter['usuario'])) {
            $qry->palavras('usuario', $filter['usuario']);
        }

        $qry = self::querySort($qry, $sort);
        $qry = self::queryFields($qry, $fields);
        return $qry;
    }

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
