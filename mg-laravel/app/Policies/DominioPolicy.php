<?php

namespace App\Policies;

use App\Models\Usuario;
use Illuminate\Auth\Access\HandlesAuthorization;

class DominioPolicy
{
    use HandlesAuthorization;

    public function before (Usuario $user, $ability) {
        if (!empty($user->inativo)) {
            return false;
        }
    }
    
    /**
     * Determine whether the user can list the model.
     *
     * @param  \App\Models\Usuario  $user
     * @return mixed
     */
    public function listing(Usuario $user) {
        return $user->can(class_basename($this) . '.' . __FUNCTION__);
    }

    
    /**
     * Determine whether the user can Exportar Estoque
     *
     * @param  \App\Models\Usuario  $user
     * @return mixed
     */
    public function exportaEstoque(Usuario $user) {
        return $user->can(class_basename($this) . '.' . __FUNCTION__);
    }
}
