<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;

use Carbon\Carbon;

/**
 * Description of MGRepositoryStatic
 *
 */
class MGRepositoryStatic
{
    public static $modelClass = null;

    public static function find(int $id)
    {
        return app(static::$modelClass)::find($id);
    }

    public static function findOrFail(int $id)
    {
        return app(static::$modelClass)::findOrFail($id);
    }

    public static function details($model)
    {
        return $model->getAttributes();
    }

    public static function new(array $data = null)
    {
        $model = new static::$modelClass();
        if (!empty($data)) {
            $model = static::fill($model, $data);
        }
        return $model;
    }

    public static function fill($model, array $data)
    {
        $model->fill($data);
        return $model;
    }

    public static function create($model = null, array $data = null)
    {
        if (empty($model)) {
            $model = static::new();
        }
        if (!empty($data)) {
            $model = static::fill($model, $data);
        }
        if (!$model->save()) {
            return false;
        }
        return $model;
    }

    public static function update($model, array $data = null)
    {
        if (!empty($data)) {
            static::fill($model, $data);
        }
        if (!$model->save()) {
            return false;
        }
        return $model;
    }

    public static function save($model)
    {
        if ($model->exists) {
            return static::update($model);
        } else {
            return static::create($model);
        }
    }

    public static function delete($model)
    {
        return $model->delete();
    }

    public static function validationRules ($model = null)
    {
        return [];
    }

    public static function validationMessages ($model = null)
    {
        return [];
    }

    public static function validate ($model = null, &$validator = null, $throwsException = true)
    {
        $rules = static::validationRules($model);

        $messages = static::validationMessages($model);

        $validator = Validator::make($model->getAttributes(), $rules, $messages);

        if ($throwsException) {
            $validator->validate();
            return true;
        }

        if (!$validator->passes()) {
            return false;
        }

        return true;
    }

    public static function query(array $filter = null, array $sort = null, array $fields = null)
    {
        $qry = app(static::$modelClass)::query();
        foreach ($filter as $field => $value) {

            if ($field == 'inativo') {
                $qry->AtivoInativo($value);
                continue;
            }

            if (is_numeric($value) || ($value instanceof Carbon)) {
                $qry->where($field, $value);
                continue;
            }

            $qry->palavras($field, $value);
        }
        $qry = static::querySort($qry, $sort);
        $qry = static::queryFields($qry, $fields);
        return $qry;
    }

    public static function queryFields($qry, array $fields = null)
    {
        if (empty($fields)) {
            return $qry;
        }
        return $qry->select($fields);
    }

    public static function querySort($qry, array $sort = null)
    {
        if (empty($sort)) {
            return $qry;
        }
        foreach ($sort as $field) {
            $dir = 'ASC';
            if (substr($field, 0, 1) == '-') {
                $dir = 'DESC';
                $field = substr($field, 1);
            }
            $qry->orderBy($field, $dir);
        }
        return $qry;
    }

    public static function activate ($model) {
        $model->inativo = null;
        return static::update($model);
    }

    public static function inactivate ($model, $date = null) {
        if (empty($date)) {
            $date = Carbon::now();
        }
        $model->inativo = $date;
        return static::update($model);
    }

}
