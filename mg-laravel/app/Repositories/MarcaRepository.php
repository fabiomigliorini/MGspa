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

    public static function validate($model = null, &$errors = null, $throwsException = true)
    {
        $data = $model->getAttributes();

        $rules = [
            'marca' => [
                'required',
                Rule::unique('tblmarca')->ignore($data['id']??null, 'codmarca')
            ],
        ];

        $messages = [
            'marca.required' => 'O campo marca não pode ser vazio',
            'marca.unique' => 'Esta marca já esta cadastrada',
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
