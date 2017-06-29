<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Marca;

/**
 * Description of MarcaRepository
 *
 */
class MarcaRepository extends MGRepositoryStatic {

    public static $modelClass = '\\App\\Models\\Marca';

    public static function validationRules ($model = null)
    {
        $rules = [
            'marca' => [
                'required',
                Rule::unique('tblmarca')->ignore($data['id']??null, 'codmarca')
            ],
        ];

        return $rules;
    }

    public static function validationMessages ($model = null)
    {
        $messages = [
            'marca.required' => 'O campo marca não pode ser vazio',
            'marca.unique' => 'Esta marca já esta cadastrada',
        ];

        return $messages;
    }
}
