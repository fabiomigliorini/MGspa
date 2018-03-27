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

    public function scopeAtivo($query)
    {
        $query->whereNull("{$this->table}.inativo");
    }

    public function scopeInativo($query)
    {
        $query->whereNotNull("{$this->table}.inativo");
    }

    public function scopeAtivoInativo($query, $valor)
    {
        switch ($valor) {
            case 1:
            $query->ativo();
            break;

            case 2:
            $query->inativo();
            break;

            default:
            case 9:
            break;
        }
    }

    public function scopePalavras($query, $campo, $palavras)
    {
        foreach (explode(' ', trim($palavras)) as $palavra) {
            if (!empty($palavra)) {
                $query->where($campo, 'ilike', "%$palavra%");
            }
        }
    }

    public static function queryFields($qry, array $fields = null)
    {
        if (empty($fields)) {
            return $qry;
        }
        return $qry->select($fields);
    }

    public static function querySort($qry, array $sort = null)
    {
        if (empty($sort)) {
            return $qry;
        }
        foreach ($sort as $field) {
            $dir = 'ASC';
            if (substr($field, 0, 1) == '-') {
                $dir = 'DESC';
                $field = substr($field, 1);
            }
            $qry->orderBy($field, $dir);
        }
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
