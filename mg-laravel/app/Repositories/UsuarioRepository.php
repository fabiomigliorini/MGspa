<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Usuario;
use App\Models\Permissao;

/**
 * Description of UsuarioRepository
 *
 * @property Validator $validator
 * @property Usuario $model
 */
class UsuarioRepository extends MGRepositoryStatic
{
    const PERMISSAO_CONSULTA = 100;
    const PERMISSAO_MANUTENCAO = 200;
    const PERMISSAO_ADMINISTRACAO = 300;

    public static $modelClass = 'Usuario';

}
