<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Feb/2026 10:49:10
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

    protected $dates = [
        'alteracao',
        'criacao',
        'data',
        'inativo'
    ];

    protected $casts = [
        'codferiado' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
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
