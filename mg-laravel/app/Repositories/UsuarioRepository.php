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

    public static function validate($model = null, &$errors = null, $throwsException = true)
    {
        $data = $model->getAttributes();

        $rules = [
        ];

        $messages = [
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($throwsException) {
            $validator->validate();
            return true;
        }

        if (!$validator->passes()) {
            $errors = $validator->errors();
            return false;
        }

        return true;
    }
}
