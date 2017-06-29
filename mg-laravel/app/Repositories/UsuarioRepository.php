<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Usuario;

/**
 * Description of UsuarioRepository
 *
 */
class UsuarioRepository extends MGRepositoryStatic
{
    public static $modelClass = '\\App\\Models\\Usuario';

    public static function validationRules ($model = null)
    {

        $rules = [
        ];

        return $rules;
    }

    public static function validationMessages ($model = null)
    {
        $messages = [
        ];
        
        return $messages;
    }
}
