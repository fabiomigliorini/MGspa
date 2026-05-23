<?php

namespace Mg;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Base model do domínio MG.
 *
 * Audit trail (criacao/alteracao + codusuariocriacao/alteracao) e helpers
 * comuns (scopes ativo/inativo, palavras). Porta literal do MgModel
 * legado em /opt/www/MGspa/laravel/app/Mg/MgModel.php — comportamento
 * idêntico para preservar o contrato dos models já migrados.
 */
abstract class MgModel extends Model
{
    protected $perPage = 50;

    const CREATED_AT = 'criacao';
    const UPDATED_AT = 'alteracao';

    public $timestamps = true;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::user() !== null) {
                $model->attributes['codusuariocriacao'] = Auth::user()->codusuario;
                $model->attributes['codusuarioalteracao'] = Auth::user()->codusuario;
            }
        });

        static::updating(function ($model) {
            if (Auth::user() !== null) {
                $model->attributes['codusuarioalteracao'] = Auth::user()->codusuario;
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

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
