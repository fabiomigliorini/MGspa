<?php

namespace App\Policies;

use App\Models\Usuario;

class ProdutoPolicy extends MGPolicy
{
    
    /**
     * Determine whether the user can list the model.
     *
     * @param  \App\Models\Usuario  $user
     * @return mixed
     */
    public function unificarBarras(Usuario $user) {
        return $user->can('ProdutoPolicy.unificarBarras');
    }

    public function site(Usuario $user) {
        return $user->can('ProdutoPolicy.site');
    }
}
