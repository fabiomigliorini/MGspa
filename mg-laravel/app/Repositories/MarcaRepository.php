<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Marca;

/**
 * Description of MarcaRepository
 *
 * @property Validator $validator
 * @property Marca $model
 */
class MarcaRepository extends MGRepositoryStatic {

    public static $modelClass = 'Marca';

    public static function validate($model = null, &$errors)
    {
        if (empty($data)) {
            if (empty($model)) {
                return false;
            }
            $data = $model->getAttributes();
        }

        $id = $data['codmarca']??$model->codmarca??null;

        $validator = Validator::make($data, [
            'marca' => [
                'required',
                Rule::unique('tblmarca')->ignore($id, 'codmarca')
            ],
        ], [
            'marca.required' => 'O campo marca não pode ser vazio',
            'marca.unique' => 'Esta marca já esta cadastrada',
        ]);

        if (!$validator->passes()) {
            $errors = $validator->errors()->all();
            return false;
        }

        return true;
    }

    public static function used($model) {
        if ($model->ProdutoS->count() > 0) {
            return 'Marca sendo utilizada em Produtos!';
        }
        return false;
    }



}
