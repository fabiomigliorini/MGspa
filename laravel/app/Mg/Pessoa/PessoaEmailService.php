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
        static::descobreEmailNfeCobranca($emails->Pessoa, $emails);
        PessoaService::atualizaCamposLegado($emails->Pessoa);
        return $emails->refresh();
    }

    public static function createOrUpdate($data)
    {

        $emails = PessoaEmail::where('codpessoa', $data['codpessoa'])
            ->whereNull('inativo')->orderBy('ordem')
            ->first();

        if ($emails) {
            $emails = static::update($emails, $data);
        } else {
            $emails = static::create($data);
        }
        PessoaService::atualizaCamposLegado($emails->Pessoa);
        return $emails;
    }

    public static function update($email, $data)
    {

        if ($email->email != $data['email']) {
            $data['verificacao'] = null;
        }

        $email->fill($data);
        $email->save();
        static::descobreEmailNfeCobranca($email->Pessoa, $email);
        PessoaService::atualizaCamposLegado($email->Pessoa);
        return $email;
    }


    public static function delete(PessoaEmail $email)
    {
        if ($email->Pessoa->PessoaEmailS()->count() <= 1) {
            throw new Exception("Não é possivel excluir todos os emails!", 1);
        }
        $pessoa = $email->Pessoa;
        $ret = $email->delete();
        static::descobreEmailNfeCobranca($pessoa);
        PessoaService::atualizaCamposLegado($pessoa);
        return $ret;
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
        PessoaService::atualizaCamposLegado($pe->Pessoa);
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
        PessoaService::atualizaCamposLegado($pe->Pessoa);
        return $pe;
    }

    public static function ativar($model)
    {
        $model->inativo = null;
        $model->update();
        PessoaService::atualizaCamposLegado($model->Pessoa);
        return $model;
    }

    public static function inativar($model, $date = null)
    {
        if (empty($date)) {
            $date = Carbon::now();
        }
        $model->inativo = $date;
        $model->update();
        PessoaService::atualizaCamposLegado($model->Pessoa);
        return $model;
    }

    public static function descobreEmailNfeCobranca(Pessoa $pessoa, PessoaEmail $email = null)
    {

        $codpessoaemailnfe = null;
        $codpessoaemailcobranca = null;
        if ($email) {
            if ($email->nfe && empty($email->inativo)) {
                $codpessoaemailnfe =  $email->codpessoatelefone;
            }
            if ($email->cobranca && empty($email->inativo)) {
                $codpessoaemailcobranca =  $email->codpessoatelefone;
            }
        }
        if ($codpessoaemailnfe) {
            $pessoa->PessoaEmailS()
                ->where('nfe', true)
                ->where('codpessoatelefone', '!=', $codpessoaemailnfe)
                ->whereNull('inativo')
                ->update(['nfe' => false]);
        }
        if ($codpessoaemailcobranca) {
            $pessoa->PessoaEmailS()
                ->where('cobranca', true)
                ->where('codpessoatelefone', '!=', $codpessoaemailcobranca)
                ->whereNull('inativo')
                ->update(['cobranca' => false]);
        }

        // caso nao tenha nenhum email de nfe
        $nfe = $pessoa->PessoaEmailS()
            ->where('nfe', true)
            ->whereNull('inativo')
            ->count();
        if ($nfe == 0) {
            $end = $pessoa->PessoaEmailS()
                ->whereNull('inativo')
                ->first()
                ->update(['nfe' => true]);
        }

        // caso nao tenha nenhum email de cobranca
        $cobranca = $pessoa->PessoaEmailS()
            ->where('cobranca', true)
            ->whereNull('inativo')
            ->count();
        if ($cobranca == 0) {
            $pessoa->PessoaEmailS()
                ->whereNull('inativo')
                ->first()
                ->update(['cobranca' => true]);
        }
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
