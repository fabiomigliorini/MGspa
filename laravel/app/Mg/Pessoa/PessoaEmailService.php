<?php

namespace Mg\Pessoa;

use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class PessoaEmailService
{
    public static function create($data)
    {

        if (empty($data['ordem'])) {
            $data['ordem'] = PessoaEmail::where('codpessoa', $data['codpessoa'])->max('ordem') + 1;
        }

        $emails = new PessoaEmail($data);
        $emails->save();

        return $emails->refresh();
    }

    public static function createOrUpdate($data)
    {

        $emails = PessoaEmail::where('codpessoa', $data['codpessoa'])
            ->whereNull('inativo')->orderBy('ordem')
            ->first();

        if ($emails) {
            return static::update($emails, $data);
        } else {
            return static::create($data);
        }
    }

    public static function update($pessoa, $data)
    {

        if ($pessoa->email != $data['email']) {
            $data ['verificacao'] = null;
        }
       
        $pessoa->fill($data);
        $pessoa->save();
        return $pessoa;
    }


    public static function delete($pessoa)
    {
        return $pessoa->delete();
    }


    public static function cima(PessoaEmail $pe)
    {
        $anterior = $pe->ordem - 1;
        $ret = PessoaEmail::where('codpessoa', $pe->codpessoa)
            ->where('ordem', $anterior)
            ->update(['ordem' => $pe->ordem]);
        if ($ret > 0) {
            $pe->update(['ordem' => $anterior]);
        }
        return $pe;
    }

    public static function baixo(PessoaEmail $pe)
    {
        $posterior = $pe->ordem + 1;
        $ret = PessoaEmail::where('codpessoa', $pe->codpessoa)
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


    public static function verificaEmail($email)
    {
        $random = rand(1000, 9999);
        $email->codverificacao = $random;
        $email->update();

         Mail::to($email)->queue(new EmailVerificacao($email->email, $random));
                    
        return [
          'mensagem' => 'Email enviado'
        ];
    }

    public static function confirmaVerificacao($email, $codverificacao) 
    {

        if ($email->codverificacao != $codverificacao) {
            throw new Exception("Código informado incorreto!", 1);
        }
        $email->verificacao = Carbon::now();
        $email->update();
        return $email;
    }

}

