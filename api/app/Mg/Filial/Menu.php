<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Feb/2026 10:50:14
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

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codmenu' => 'integer',
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