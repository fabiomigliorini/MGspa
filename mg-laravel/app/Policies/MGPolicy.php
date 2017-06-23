<?php

namespace App\Policies;

use App\Models\Usuario;
use Illuminate\Auth\Access\HandlesAuthorization;

class MGPolicy
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
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\Usuario  $user
     * @param  \App\Models\MGModel $model
     * @return mixed
     */
    public function view(Usuario $user, $model) {
        return $user->can(class_basename($this) . '.' . __FUNCTION__);
    }

    /**
     * Determine whether the user can create the model.
     *
     * @param  \App\Models\Usuario  $user
     * @return mixed
     */
    public function create(Usuario $user) {
        return $user->can(class_basename($this) . '.' . __FUNCTION__);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\Usuario  $user
     * @param  \App\Models\MGModel $model
     * @return mixed
     */
    public function update(Usuario $user, $model) {
        return $user->can(class_basename($this) . '.' . __FUNCTION__);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\Usuario  $user
     * @param  \App\Models\MGModel $model
     * @return mixed
     */
    public function delete(Usuario $user, $model) {
        return $user->can(class_basename($this) . '.' . __FUNCTION__);
    }
}
