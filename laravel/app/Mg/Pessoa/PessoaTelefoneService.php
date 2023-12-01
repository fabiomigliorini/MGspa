<?php

namespace Mg\Pessoa;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Exception;

use function PHPUnit\Framework\throwException;

class PessoaTelefoneService
{

    public static function create($data)
    {
        if (empty($data['ordem'])) {
            $data['ordem'] = PessoaTelefone::where('codpessoa', $data['codpessoa'])->max('ordem') + 1;
        }
        $telefone = new PessoaTelefone($data);
        $telefone->save();
        return $telefone->refresh();
    }

    public static function update($telefone, $data)
    {
        if ($telefone->tipo != $data['tipo']) {
            $data ['verificacao'] = null;
        }
        if ($telefone->pais != $data['pais']) {
            $data ['verificacao'] = null;
        }
        if ($telefone->ddd != $data['ddd']) {
            $data ['verificacao'] = null;
        }
        if ($telefone->telefone != $data['telefone']) {
            $data ['verificacao'] = null;
        }
        $telefone->fill($data);
        $telefone->save();
        return $telefone;
    }

    public static function createOrUpdate($data)
    {
        $telefone = PessoaTelefone::where('codpessoa', $data['codpessoa'])
            ->where('ddd', $data['ddd'])
            ->where('telefone', $data['telefone'])
            ->first();
        if ($telefone) {
            return static::update($telefone, $data);
        } else {
            return static::create($data);
        }
    }


    public static function delete($pessoa)
    {
        return $pessoa->delete();
    }


    public static function cima(PessoaTelefone $pe)
    {
        $anterior = $pe->ordem - 1;
        $ret = PessoaTelefone::where('codpessoa', $pe->codpessoa)
            ->where('ordem', $anterior)
            ->update(['ordem' => $pe->ordem]);
        if ($ret > 0) {
            $pe->update(['ordem' => $anterior]);
        }
        return $pe;
    }

    public static function baixo(PessoaTelefone $pe)
    {
        $posterior = $pe->ordem + 1;
        $ret = PessoaTelefone::where('codpessoa', $pe->codpessoa)
            ->where('ordem', $posterior)
            ->update(['ordem' => $pe->ordem]);
        if ($ret > 0) {
            $pe->update(['ordem' => $posterior]);
        }
        return $pe;
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

    public static function verificarsms($tel)
    {
        $random = rand(1000, 9999);
        $tel->codverificacao = $random;
        $tel->update();

        $telefone = "{$tel->pais}{$tel->ddd}{$tel->telefone}";
        $msg = 'Código de verificação MG Papelaria: ';

        $response = Http::withHeaders([
            'Accept' => 'application/json'
        ])->get('https://api.smsdev.com.br/v1/send?key=' . env('CHAVE_API_SMS')
            . '&type=9&number=' . $telefone . '&msg=' . $msg . $random);

        return $response->json();
    }

    public static function confirmaVerificacao(PessoaTelefone $tel, $codverificacao) 
    {
        if ($tel->codverificacao != $codverificacao) {
            throw new Exception("Código informado incorreto!", 1);
        }
        $tel->verificacao = Carbon::now();
        $tel->update();
        return $tel;

    }
}
