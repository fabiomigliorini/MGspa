<?php

namespace Mg;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Mg\Usuario\Usuario;

/**
 * Base model do domínio MG.
 *
 * Audit trail (criacao/alteracao + codusuariocriacao/alteracao) e helpers
 * comuns (scopes ativo/inativo, palavras). Porta do MgModel legado em
 * /opt/www/MGspa/laravel/app/Mg/MgModel.php — comportamento idêntico,
 * exceto o stamp de auditoria, que resolve o usuário pelo guard autenticado
 * (api/Passport) em vez de só `Auth::user()` (guard padrão web). Ver
 * usuarioAutenticado().
 */
abstract class MgModel extends Model
{
    protected $perPage = 50;

    const CREATED_AT = 'criacao';
    const UPDATED_AT = 'alteracao';

    public $timestamps = true;

    /**
     * Compat L13: `protected $dates` foi removido do Eloquent moderno.
     * Os models do bulk-import do legacy ainda declaram esse array — aqui
     * intercepta-mos `getCasts()` e mergiamos os campos como `datetime`,
     * para manter comportamento (Carbon nas datas) sem precisar editar
     * cada um dos ~70 models.
     */
    public function getCasts()
    {
        $casts = parent::getCasts();
        // @phpstan-ignore-next-line: $dates é removido no L13 mas existe nos models legacy
        $dates = $this->dates ?? [];
        foreach ($dates as $field) {
            if (!isset($casts[$field])) {
                $casts[$field] = 'datetime';
            }
        }
        return $casts;
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (($usuario = static::usuarioAutenticado()) !== null) {
                $model->attributes['codusuariocriacao'] = $usuario->codusuario;
                $model->attributes['codusuarioalteracao'] = $usuario->codusuario;
            }
        });

        static::updating(function ($model) {
            if (($usuario = static::usuarioAutenticado()) !== null) {
                $model->attributes['codusuarioalteracao'] = $usuario->codusuario;
            }
        });

        static::saving(function ($model) {
            foreach ($model->toArray() as $fieldName => $fieldValue) {
                if ($fieldValue === '') {
                    $model->attributes[$fieldName] = null;
                }
            }
            return true;
        });
    }

    /**
     * Usuário autenticado para o stamp de auditoria.
     *
     * Auth::user() usa o guard padrão ('web'/sessão), que nunca está autenticado
     * em requisições de token Bearer (que usam o guard 'api'/Passport). Por isso
     * resolvemos o usuário a partir do guard que estiver de fato autenticado.
     */
    protected static function usuarioAutenticado()
    {
        foreach (['api', 'web'] as $guard) {
            if (Auth::guard($guard)->check()) {
                return Auth::guard($guard)->user();
            }
        }
        return Auth::user();
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

    // Audit trail — usuário que criou/alterou o registro. Presente em todos os
    // models (a coluna codusuario* existe em todas as tabelas tbl*). Os models
    // que precisam expor o nome no JSON declaram `$appends` com
    // 'usuariocriacao'/'usuarioalteracao'.
    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function getUsuariocriacaoAttribute()
    {
        return optional($this->getRelationValue('UsuarioCriacao'))->usuario;
    }

    public function getUsuarioalteracaoAttribute()
    {
        return optional($this->getRelationValue('UsuarioAlteracao'))->usuario;
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
