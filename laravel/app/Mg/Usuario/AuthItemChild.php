<?php
/**
 * Created by php artisan gerador:model.
 * Date: 29/Oct/2024 12:23:40
 */

namespace Mg\Usuario;

use Mg\MgModel;
use Mg\Usuario\AuthItem;

class AuthItemChild extends MgModel
{
    protected $table = 'tblauthitemchild';
    protected $primaryKey = 'parent';


    protected $fillable = [
        'child'
    ];

    protected $dates = [
        
    ];

    protected $casts = [
        
    ];


    // Chaves Estrangeiras
    public function AuthItemChild()
    {
        return $this->belongsTo(AuthItem::class, 'child', 'name');
    }

    public function AuthItemParent()
    {
        return $this->belongsTo(AuthItem::class, 'parent', 'name');
    }

}