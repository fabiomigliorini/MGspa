<?php
/**
 * Created by php artisan gerador:model.
 * Date: 29/Oct/2024 12:19:25
 */

namespace Mg\Authitem;

use Mg\MgModel;
use Mg\Usuario\AuthAssignment;
use Mg\Usuario\AuthItemChild;

class Authitem extends MgModel
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

    public function AuthItemChildS()
    {
        return $this->hasMany(AuthItemChild::class, 'parent', 'name');
    }

}