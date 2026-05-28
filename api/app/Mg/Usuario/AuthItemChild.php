<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:40:24
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

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime'
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
