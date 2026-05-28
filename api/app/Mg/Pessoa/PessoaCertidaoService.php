<?php

namespace Mg\Pessoa;

use Carbon\Carbon;

class PessoaCertidaoService
{
    public static function create($data): PessoaCertidao
    {
        $reg = new PessoaCertidao($data);
        $reg->save();
        return $reg->refresh();
    }

    public static function update($reg, $data)
    {
        $reg->fill($data);
        $reg->save();
        return $reg;
    }

    public static function delete($reg)
    {
        return $reg->delete();
    }

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
}
