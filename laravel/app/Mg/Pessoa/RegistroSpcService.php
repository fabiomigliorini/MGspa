<?php

namespace Mg\Pessoa;


class RegistroSpcService
{
    /**
     * Busca Autocomplete Quasar
     */
   
    public static function create ($data)
    {
        $registro = new RegistroSpc($data);
        $registro->save();
        return $registro->refresh();
        
    }

    public static function update ($registro, $data)
    {
        $registro->fill($data);
        $registro->save();
        return $registro;
    }

    
    public static function delete ($registro)
    {
        return $registro->delete();
    }
  

}
