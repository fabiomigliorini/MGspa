<?php

namespace Mg\Pessoa;

class RegistroSpcService
{
    public static function create($data): RegistroSpc
    {
        $reg = new RegistroSpc($data);
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
}
