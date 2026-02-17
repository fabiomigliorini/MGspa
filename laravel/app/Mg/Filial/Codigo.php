<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Feb/2026 10:49:01
 */

namespace Mg\Filial;

use Mg\MgModel;
use Mg\Usuario\Usuario;

class Codigo extends MgModel
{
    protected $table = 'tblcodigo';
    protected $primaryKey = 'tabela';


    protected $fillable = [
        'codproximo'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codproximo' => 'integer',
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