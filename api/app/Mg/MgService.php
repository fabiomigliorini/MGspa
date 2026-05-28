<?php

namespace Mg;

use Carbon\Carbon;

/**
 * Base service do domínio MG — helpers comuns de ativar/inativar
 * e parsing de sort/fields. Porta literal do legado.
 */
class MgService
{
    public static function ativar($model)
    {
        $model->inativo = null;
        $model->update();
        return $model;
    }

    public static function inativar($model, $date = null)
    {
        if (empty($date)) {
            $date = Carbon::now();
        }
        $model->inativo = $date;
        $model->update();
        return $model;
    }

    public static function qryColunas($qry, ?array $fields = null)
    {
        if (empty($fields)) {
            return $qry;
        }
        return $qry->select($fields);
    }

    public static function qryOrdem($qry, ?array $sort = null)
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
