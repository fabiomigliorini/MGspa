<?php

namespace Mg\Pessoa;
use Carbon\Carbon;

class PessoaEnderecoService
{

    public static function create($data)
    {

        if (empty($data['ordem'])) {
            $data['ordem'] = PessoaEndereco::where('codpessoa', $data['codpessoa'])->max('ordem') + 1;
        }

        $endereco = new PessoaEndereco($data);
        $endereco->save();
        return $endereco->refresh();
    }

    public static function createOrUpdate($data)
    {
        $end = PessoaEndereco::where('codpessoa', $data['codpessoa'])
            ->whereNull('inativo')->orderBy('ordem')
            ->first();
        if ($end) {
            return static::update($end, $data);
        } else {
            return static::create($data);
        }
    }

    public static function update($pessoa, $data)
    {
        $pessoa->fill($data);
        $pessoa->save();
        return $pessoa;
    }


    public static function delete($pessoa)
    {
        return $pessoa->delete();
    }


    public static function cima(PessoaEndereco $pe)
    {
        $anterior = $pe->ordem - 1;
        $ret = PessoaEndereco::where('codpessoa', $pe->codpessoa)
            ->where('ordem', $anterior)
            ->update(['ordem' => $pe->ordem]);
        if ($ret > 0) {
            $pe->update(['ordem' => $anterior]);
        }
        return $pe;
    }

    public static function baixo(PessoaEndereco $pe)
    {
        $posterior = $pe->ordem + 1;
        $ret = PessoaEndereco::where('codpessoa', $pe->codpessoa)
            ->where('ordem', $posterior)
            ->update(['ordem' => $pe->ordem]);
        if ($ret > 0) {
            $pe->update(['ordem' => $posterior]);
        }
        return $pe;
    }

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
}