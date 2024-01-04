<?php

namespace Mg\Pessoa;

use Carbon\Carbon;

class PessoaCertidaoService
{

    public static function create ($data)
    {
        
        $pessoaCertidao = new PessoaCertidao($data);
        $pessoaCertidao->save();
        return $pessoaCertidao->refresh();
        
    }


    public static function update ($pessoaCertidao, $data)
    {
        $pessoaCertidao->fill($data);
        $pessoaCertidao->save();
        return $pessoaCertidao;
    }

    
    public static function delete ($pessoaCertidao)
    {
        return $pessoaCertidao->delete();
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
