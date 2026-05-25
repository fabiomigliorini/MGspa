<?php

namespace Mg\Pessoa;

use Carbon\Carbon;

class PessoaContaService
{
    public static function create($data): PessoaConta
    {
        $reg = new PessoaConta($data);
        $reg->save();
        return $reg->refresh();
    }

    public static function update($reg, $data)
    {
        $reg->fill($data);
        $reg->save();
        return $reg;
    }

    public static function createOrUpdate($data)
    {
        $conta = PessoaConta::where('codpessoa', $data['codpessoa'])
            ->whereNull('inativo')
            ->orderBy('alteracao')
            ->first();
        return $conta ? static::update($conta, $data) : static::create($data);
    }

    public static function delete($reg)
    {
        return $reg->delete();
    }

    public static function ativar($reg)
    {
        $reg->inativo = null;
        $reg->update();
        return $reg;
    }

    public static function inativar($reg, $date = null)
    {
        if (empty($date)) {
            $date = Carbon::now();
        }
        $reg->inativo = $date;
        $reg->update();
        return $reg;
    }
}
