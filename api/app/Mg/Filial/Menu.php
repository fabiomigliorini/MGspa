<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:39:46
 */

namespace Mg\Filial;

use Mg\MgModel;
use Mg\Usuario\Usuario;

class Menu extends MgModel
{
    protected $table = 'tblmenu';
    protected $primaryKey = 'codmenu';


    protected $fillable = [
        'form',
        'nome'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codmenu' => 'integer',
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
