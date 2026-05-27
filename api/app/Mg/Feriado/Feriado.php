<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 12:04:35
 */

namespace Mg\Feriado;

use Mg\MgModel;
use Mg\Usuario\Usuario;

class Feriado extends MgModel
{
    protected $table = 'tblferiado';
    protected $primaryKey = 'codferiado';


    protected $fillable = [
        'data',
        'feriado',
        'inativo'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codferiado' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'data' => 'date',
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

}
