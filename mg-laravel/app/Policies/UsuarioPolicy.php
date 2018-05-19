<?php

namespace App\Policies;
use App\Models\Usuario;

class UsuarioPolicy extends MGPolicy
{
    public function gruposCreate(Usuario $user) {
        return $user->can('UsuarioPolicy.gruposCreate');
    }	
    
    public function gruposDestroy(Usuario $user) {
        return $user->can('UsuarioPolicy.gruposDestroy');
    }	
}
