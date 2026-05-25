<?php

namespace Mg\Cobranca;

class CobrancaHistoricoService
{
    public static function create($data): CobrancaHistorico
    {
        $reg = new CobrancaHistorico($data);
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
