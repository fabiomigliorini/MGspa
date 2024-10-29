<?php
/**
 * Created by php artisan gerador:model.
 * Date: 29/Oct/2024 12:25:45
 */

namespace Mg\Usuario;

use Mg\MgModel;
use Mg\Usuario\AuthItem;
use Mg\Usuario\Usuario;

class AuthAssignment extends MgModel
{
    protected $table = 'tblauthassignment';
    protected $primaryKey = 'itemname';


    protected $fillable = [
        'bizrule',
        'data',
        'userid'
    ];

    protected $dates = [
        
    ];

    protected $casts = [
        'userid' => 'integer'
    ];


    // Chaves Estrangeiras
    public function AuthitemItem()
    {
        return $this->belongsTo(AuthItem::class, 'itemname', 'name');
    }

    public function UsuarioUserid()
    {
        return $this->belongsTo(Usuario::class, 'userid', 'codusuario');
    }

}