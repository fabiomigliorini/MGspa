<?php

namespace Mg\Colaborador;

class ColaboradorService
{

    public static function create($data)
    {
        $colaborador = new Colaborador($data);
        $colaborador->save();
        return $colaborador->refresh();
    }

    public static function update($colaborador, $data)
    {
        $colaborador->fill($data);
        $colaborador->save();
        return $colaborador;
    }


    public static function delete($colaborador)
    {
        return $colaborador->delete();
    }

    // public static function inativar(GrupoEconomico $grupo)
    // {
    //     $grupo->update(['inativo' => Carbon::now()]);
    //     return $grupo->refresh();
    // }

    public static function ativar($grupo)
    {
        $grupo->inativo = null;
        $grupo->update();
        return $grupo;
    }

  
}
