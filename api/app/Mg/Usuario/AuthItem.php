<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Feb/2026 10:43:17
 */

namespace Mg\Usuario;

use Mg\MgModel;
use Mg\Usuario\AuthAssignment;
use Mg\Usuario\AuthItemChild;

class AuthItem extends MgModel
{
    protected $table = 'tblauthitem';
    protected $primaryKey = 'name';


    protected $fillable = [
        'bizrule',
        'data',
        'description',
        'type'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'type' => 'integer'
    ];


    // Tabelas Filhas
    public function AuthAssignmentS()
    {
        return $this->hasMany(AuthAssignment::class, 'itemname', 'name');
    }

    public function AuthItemChildS()
    {
        return $this->hasMany(AuthItemChild::class, 'child', 'name');
    }

    public function AuthItemParentS()
    {
        return $this->hasMany(AuthItemChild::class, 'parent', 'name');
    }

}