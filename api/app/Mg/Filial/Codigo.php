<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:38:55
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

    protected $casts = [
        'alteracao' => 'datetime',
        'codproximo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime'
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
