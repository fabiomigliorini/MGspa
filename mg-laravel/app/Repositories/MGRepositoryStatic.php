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
        return app('App\\Models\\' . static::$modelClass)::find($id);
    }

    public static function findOrFail(int $id)
    {
        return app('App\\Models\\' . static::$modelClass)::findOrFail($id);
    }

    public static function new()
    {
        $class = "App\\Models\\" . static::$modelClass;
        $model = new $class();
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
            if (empty($data)) {
                return false;
            }
            $model = static::new();
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

    public static function delete($model = null)
    {
        return $model->delete();
    }


    public static function validate($model = null, &$errors)
    {
        return true;
    }

    public static function used($model)
    {
        return false;
    }

    public static function query(array $filter = null, array $sort = null, array $fields = null)
    {
        $qry = app('App\\Models\\' . static::$modelClass)::query();
        foreach ($filter as $field => $value) {
            if (is_numeric($value) || ($value instanceof Carbon)) {
                $qry->where($field, $value);
            } else {
                $qry->palavras($field, $value);
            }
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

}
