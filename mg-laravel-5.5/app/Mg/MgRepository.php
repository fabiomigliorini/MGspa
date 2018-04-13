<?php

namespace App\Mg;

use Carbon\Carbon;

class MgRepository
{
    public static function ativar ($model) {
        $model->inativo = null;
        $model->update();
        return $model;
    }

    public static function inativar ($model, $date = null) {
        if (empty($date)) {
            $date = Carbon::now();
        }
        $model->inativo = $date;
        $model->update();
        return $model;
    }

    public static function qryColunas($qry, array $fields = null)
    {
        if (empty($fields)) {
            return $qry;
        }
        return $qry->select($fields);
    }

    public static function qryOrdem($qry, array $sort = null)
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
